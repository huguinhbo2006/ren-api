<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $now = Carbon::now()->startOfDay();
        $endDate = $this->end_date ? Carbon::parse($this->end_date)->startOfDay() : null;
        $daysRemaining = $endDate ? (int) $now->diffInDays($endDate, false) : 0;

        $paidAmountCents = $this->relationLoaded('payments')
            ? (int) $this->payments->where('type', 'income')->sum('amount_cents')
            : 0;

        $pendingBalanceCents = max(0, $this->total_amount_cents - $paidAmountCents);

        $statusLabels = [
            'draft' => 'Borrador',
            'pending' => 'Pendiente',
            'active' => 'Activa / En curso',
            'completed' => 'Completada / Devuelto',
            'cancelled' => 'Cancelada',
        ];

        $paymentStatusLabels = [
            'unpaid' => 'No pagado',
            'partial' => 'Pago parcial',
            'paid' => 'Pagado totalmente',
            'refunded' => 'Reembolsado',
        ];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'folio' => $this->folio,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', function () {
                return new CustomerResource($this->customer);
            }),
            'asset_id' => $this->asset_id,
            'asset' => $this->whenLoaded('asset', function () {
                return new AssetResource($this->asset);
            }),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'actual_return_date' => $this->actual_return_date?->toDateString(),
            'rental_days' => $this->rental_days,
            'days_remaining' => $daysRemaining,
            'is_overdue' => $this->status === 'active' && $daysRemaining < 0,
            'base_amount_cents' => $this->base_amount_cents,
            'deposit_cents' => $this->deposit_cents,
            'discount_cents' => $this->discount_cents,
            'tax_cents' => $this->tax_cents,
            'total_amount_cents' => $this->total_amount_cents,
            'paid_amount_cents' => $paidAmountCents,
            'pending_balance_cents' => $pendingBalanceCents,
            'status' => $this->status,
            'status_label' => $statusLabels[$this->status] ?? $this->status,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $paymentStatusLabels[$this->payment_status] ?? $this->payment_status,
            'notes' => $this->notes,
            'extras' => RentalExtraResource::collection($this->whenLoaded('extras')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

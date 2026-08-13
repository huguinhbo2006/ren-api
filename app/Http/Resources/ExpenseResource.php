<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receiptUrl = null;
        if ($this->receipt_url) {
            $receiptUrl = Storage::disk('public')->url($this->receipt_url);
        }

        $typeLabels = [
            'maintenance' => 'Mantenimiento preventivo',
            'repair' => 'Reparación',
            'purchase' => 'Adquisición de insumo/activo',
            'other' => 'Otro gasto operativo',
        ];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'asset_id' => $this->asset_id,
            'asset' => $this->whenLoaded('asset', function () {
                return [
                    'id' => $this->asset->id,
                    'name' => $this->asset->name,
                    'serial_number' => $this->asset->serial_number,
                ];
            }),
            'category' => $this->category,
            'description' => $this->description,
            'amount_cents' => $this->amount_cents,
            'expense_date' => $this->expense_date?->toDateString() ?? $this->expense_date,
            'vendor' => $this->vendor,
            'receipt_url' => $receiptUrl,
            'type' => $this->type,
            'type_label' => $typeLabels[$this->type] ?? ucfirst($this->type),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rentals = $this->relationLoaded('rentals') ? $this->rentals : null;

        $rentalCount = $this->whenCounted('rentals', function () use ($rentals) {
            return $rentals ? $rentals->count() : $this->rentals_count;
        });

        $totalSpentCents = 0;
        $totalOwedCents = 0;

        if ($rentals) {
            foreach ($rentals as $rental) {
                $paid = $rental->relationLoaded('payments')
                    ? $rental->payments->where('type', 'income')->sum('amount_cents')
                    : 0;

                $totalSpentCents += $paid;
                $pending = max(0, $rental->total_amount_cents - $paid);
                $totalOwedCents += $pending;
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'rfc' => $this->rfc,
            'address' => $this->address,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'rental_count' => $rentalCount,
            'total_spent_cents' => $totalSpentCents,
            'total_owed_cents' => $totalOwedCents,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rental_id' => $this->rental_id,
            'rental_folio' => $this->rental?->folio,
            'user_id' => $this->user_id,
            'amount_cents' => $this->amount_cents,
            'payment_date' => $this->payment_date?->toDateString(),
            'method' => $this->method,
            'type' => $this->type,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

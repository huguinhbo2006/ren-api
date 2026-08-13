<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'extra_service_id',
        'name',
        'quantity',
        'unit_price_cents',
        'subtotal_cents',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'subtotal_cents' => 'integer',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function extraService(): BelongsTo
    {
        return $this->belongsTo(ExtraService::class);
    }
}

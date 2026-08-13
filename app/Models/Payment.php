<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'user_id',
        'amount_cents',
        'payment_date',
        'method',
        'reference',
        'notes',
        'type',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'payment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            $payment->rental?->updatePaymentStatus();
            $payment->rental?->saveQuietly();
        });

        static::deleted(function (Payment $payment) {
            $payment->rental?->updatePaymentStatus();
            $payment->rental?->saveQuietly();
        });
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }
}

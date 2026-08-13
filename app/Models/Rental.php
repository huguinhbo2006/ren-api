<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_id',
        'asset_id',
        'folio',
        'start_date',
        'end_date',
        'actual_return_date',
        'rental_days',
        'base_amount_cents',
        'extras_amount_cents',
        'discount_cents',
        'deposit_cents',
        'deposit_returned',
        'total_amount_cents',
        'status',
        'payment_status',
        'notes',
        'terms_text',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_return_date' => 'date',
        'rental_days' => 'integer',
        'base_amount_cents' => 'integer',
        'extras_amount_cents' => 'integer',
        'discount_cents' => 'integer',
        'deposit_cents' => 'integer',
        'deposit_returned' => 'boolean',
        'total_amount_cents' => 'integer',
    ];

    /**
     * Boot model events: Auto-generación de folio RNT-YYYY-XXXXX
     */
    protected static function booted(): void
    {
        static::creating(function (Rental $rental) {
            if (empty($rental->folio)) {
                $year = now()->year;
                $prefix = config('rentame.rental.folio_prefix', 'RNT');
                $digits = config('rentame.rental.folio_digits', 5);

                $lastRental = static::where('folio', 'like', "{$prefix}-{$year}-%")
                    ->orderByDesc('id')
                    ->first();

                $nextNumber = 1;
                if ($lastRental && preg_match("/{$prefix}-{$year}-(\d+)/", $lastRental->folio, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $rental->folio = sprintf("{$prefix}-{$year}-%0{$digits}d", $nextNumber);
            }
        });
    }

    /**
     * Relaciones
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function rentalAssets(): HasMany
    {
        return $this->hasMany(RentalAsset::class);
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'rental_assets')->withPivot('daily_rate_cents', 'subtotal_cents');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(RentalExtra::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Cálculos financieros
     */
    public function calculateTotal(): void
    {
        $this->extras_amount_cents = $this->extras()->sum('subtotal_cents');
        $this->total_amount_cents = max(0, ($this->base_amount_cents + $this->extras_amount_cents) - $this->discount_cents);
        $this->updatePaymentStatus();
    }

    public function updatePaymentStatus(): void
    {
        $paidIncome = $this->payments()
            ->where('type', 'income')
            ->sum('amount_cents');

        if ($paidIncome >= $this->total_amount_cents && $this->total_amount_cents > 0) {
            $this->payment_status = 'paid';
        } elseif ($paidIncome > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['unpaid', 'partial']);
    }

    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'serial_number',
        'daily_rate_cents',
        'weekly_rate_cents',
        'monthly_rate_cents',
        'deposit_cents',
        'initial_investment_cents',
        'status',
        'location',
        'notes',
        'images_json',
    ];

    protected $casts = [
        'images_json' => 'array',
        'daily_rate_cents' => 'integer',
        'weekly_rate_cents' => 'integer',
        'monthly_rate_cents' => 'integer',
        'deposit_cents' => 'integer',
        'initial_investment_cents' => 'integer',
    ];

    /**
     * Usuario propietario del activo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Categoría del activo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    /**
     * Historial de contratos de renta de este activo
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Gastos y mantenimientos registrados para este activo
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeRented($query)
    {
        return $query->where('status', 'rented');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('serial_number', 'like', "%{$term}%")
              ->orWhere('location', 'like', "%{$term}%");
        });
    }

    /**
     * Helper para verificar disponibilidad
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}

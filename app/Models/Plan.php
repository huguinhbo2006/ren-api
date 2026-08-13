<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'duration_days',
        'features_json',
        'is_active',
    ];

    protected $casts = [
        'features_json' => 'array',
        'is_active' => 'boolean',
        'price_cents' => 'integer',
        'duration_days' => 'integer',
    ];

    /**
     * Relación con los usuarios suscritos a este plan
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Verifica si es el plan gratuito
     */
    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    /**
     * Verifica si es el plan Pro
     */
    public function isPro(): bool
    {
        return $this->slug === 'pro';
    }
}

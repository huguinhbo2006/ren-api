<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtiene el valor de una configuración de usuario
     */
    public static function get(int $userId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Guarda o actualiza una configuración de usuario
     */
    public static function set(int $userId, string $key, mixed $value): self
    {
        return static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }

    /**
     * Obtiene todas las configuraciones del usuario como un array asociativo
     */
    public static function getAllForUser(int $userId): array
    {
        $defaults = [
            'business_name' => 'Mi Negocio de Rentas',
            'business_rfc' => '',
            'business_logo' => null,
            'business_address' => '',
            'business_phone' => '',
            'contract_template' => "CONTRATO DE ARRENDAMIENTO\n\nPor una parte el ARRENDADOR entrega en arrendamiento al ARRENDATARIO el bien descrito, comprometiéndose este último a devolverlo en las mismas condiciones en la fecha acordada.",
            'notification_days_before' => '3',
            'currency_symbol' => '$',
            'timezone' => 'America/Mexico_City',
            'invoice_prefix' => 'RNT',
        ];

        $saved = static::where('user_id', $userId)
            ->pluck('value', 'key')
            ->toArray();

        return array_merge($defaults, $saved);
    }
}

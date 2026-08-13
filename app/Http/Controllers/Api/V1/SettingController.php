<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends BaseController
{
    /**
     * Obtener todas las configuraciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $settings = Setting::getAllForUser($userId);

        if (! empty($settings['business_logo'])) {
            $settings['business_logo_url'] = Storage::disk('public')->url($settings['business_logo']);
        } else {
            $settings['business_logo_url'] = null;
        }

        return $this->success($settings, 'Configuraciones obtenidas exitosamente.');
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function update(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $allowedKeys = [
            'business_name',
            'business_rfc',
            'business_address',
            'business_phone',
            'contract_template',
            'notification_days_before',
            'currency_symbol',
            'timezone',
            'invoice_prefix',
        ];

        $data = $request->only($allowedKeys);

        foreach ($data as $key => $value) {
            Setting::set($userId, $key, (string) $value);
        }

        $updated = Setting::getAllForUser($userId);
        if (! empty($updated['business_logo'])) {
            $updated['business_logo_url'] = Storage::disk('public')->url($updated['business_logo']);
        }

        return $this->success($updated, 'Configuración actualizada exitosamente.');
    }

    /**
     * Subir logotipo del negocio
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $userId = $request->user()->id;
        $currentLogo = Setting::get($userId, 'business_logo');

        if ($currentLogo) {
            Storage::disk('public')->delete($currentLogo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        Setting::set($userId, 'business_logo', $path);

        $url = Storage::disk('public')->url($path);

        return $this->success([
            'business_logo' => $path,
            'business_logo_url' => $url,
        ], 'Logotipo actualizado exitosamente.');
    }
}

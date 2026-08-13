<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\AssetCategory;
use App\Models\ExtraService;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Support\PlanHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    /**
     * Registro de nuevo usuario
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $freePlan = Plan::where('slug', 'free')->first();
            $planId = $validated['plan_id'] ?? $freePlan?->id;

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'plan_id' => $planId,
                'is_active' => true,
            ]);

            // Asignar rol default
            $user->assignRole('user');

            // Crear categorías base predeterminadas para el nuevo usuario
            $defaultCategories = [
                ['name' => 'Inmuebles y Locales', 'icon' => 'home-outline', 'color' => '#2563eb', 'description' => 'Casas, departamentos y locales'],
                ['name' => 'Vehículos y Remolques', 'icon' => 'car-outline', 'color' => '#0284c7', 'description' => 'Autos, camionetas y remolques'],
                ['name' => 'Mobiliario y Eventos', 'icon' => 'calendar-outline', 'color' => '#16a34a', 'description' => 'Mesas, sillas e inflables'],
            ];

            foreach ($defaultCategories as $cat) {
                AssetCategory::create(array_merge($cat, ['user_id' => $user->id]));
            }

            // Crear servicios extra iniciales
            ExtraService::create([
                'user_id' => $user->id,
                'name' => 'Flete / Entrega a domicilio',
                'description' => 'Transporte seguro del activo',
                'price_cents' => 35000,
                'unit' => 'por viaje',
                'is_active' => true,
            ]);

            // Inicializar settings
            Setting::set($user->id, 'business_name', $user->name);
            Setting::set($user->id, 'business_phone', $user->phone ?? '');

            return $user;
        });

        // Crear token con abilities y expiración según el plan
        $deviceName = $request->input('device_name', 'web-client');
        $abilities = PlanHelper::getTokenAbilities($user);
        $expiresAt = PlanHelper::getTokenExpiration($user);

        $token = $user->createToken($deviceName, $abilities, $expiresAt);

        return $this->created([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
            'abilities' => $abilities,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 'Usuario registrado exitosamente.');
    }

    /**
     * Inicio de sesión
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('Las credenciales proporcionadas son incorrectas.', 401);
        }

        if (! $user->is_active) {
            return $this->forbidden('Tu cuenta ha sido desactivada. Contacta al soporte.');
        }

        $deviceName = $request->input('device_name', 'web-client');
        $abilities = PlanHelper::getTokenAbilities($user);
        $expiresAt = PlanHelper::getTokenExpiration($user);

        $token = $user->createToken($deviceName, $abilities, $expiresAt);

        return $this->success([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
            'abilities' => $abilities,
            'expires_at' => $expiresAt->toIso8601String(),
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * Cierre de sesión (revoca token actual)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Sesión cerrada exitosamente.');
    }

    /**
     * Información del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user())
        );
    }

    /**
     * Actualización de perfil
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->success(
            new UserResource($user),
            'Perfil actualizado exitosamente.'
        );
    }

    /**
     * Cambio de contraseña
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Contraseña actualizada correctamente.');
    }
}

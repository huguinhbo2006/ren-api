<?php

namespace App\Support;

use App\Models\User;

/**
 * PlanHelper
 *
 * Clase de utilidad para verificar límites y features según el plan
 * de suscripción del usuario. Centraliza toda la lógica de planes
 * del sistema Rentame.
 */
class PlanHelper
{
    /**
     * Verifica si el usuario puede crear más activos.
     */
    public static function canCreateAsset(User $user): bool
    {
        $limit = self::getPlanLimit($user, 'max_assets');
        if ($limit === PHP_INT_MAX) {
            return true;
        }

        return $user->assets()->count() < $limit;
    }

    /**
     * Verifica si el usuario puede crear más clientes.
     */
    public static function canCreateCustomer(User $user): bool
    {
        $limit = self::getPlanLimit($user, 'max_customers');
        if ($limit === PHP_INT_MAX) {
            return true;
        }

        return $user->customers()->count() < $limit;
    }

    /**
     * Verifica si el usuario puede crear más rentas este mes.
     */
    public static function canCreateRental(User $user): bool
    {
        $limit = self::getPlanLimit($user, 'max_rentals_per_month');
        if ($limit === PHP_INT_MAX) {
            return true;
        }

        $monthlyCount = $user->rentals()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $monthlyCount < $limit;
    }

    /**
     * Verifica si el usuario puede crear más servicios extras.
     */
    public static function canCreateExtraService(User $user): bool
    {
        $limit = self::getPlanLimit($user, 'max_extra_services');
        if ($limit === PHP_INT_MAX) {
            return true;
        }

        return $user->extraServices()->count() < $limit;
    }

    /**
     * Verifica si una feature específica está disponible para el usuario.
     *
     * @param  User   $user    Usuario autenticado
     * @param  string $feature Feature a verificar (ej: 'reports', 'export_pdf')
     */
    public static function hasFeature(User $user, string $feature): bool
    {
        $planSlug = $user->plan?->slug ?? 'free';
        $features = config("rentame.plans.{$planSlug}.features", []);

        return (bool) ($features[$feature] ?? false);
    }

    /**
     * Obtiene el límite numérico de un plan para una restricción dada.
     *
     * @param  User   $user  Usuario autenticado
     * @param  string $key   Clave del límite (ej: 'max_assets')
     */
    public static function getPlanLimit(User $user, string $key): int
    {
        $planSlug = $user->plan?->slug ?? 'free';

        return config("rentame.plans.{$planSlug}.{$key}", 0);
    }

    /**
     * Retorna las abilities del token según el plan del usuario.
     *
     * @param  User $user Usuario autenticado
     * @return array<string> Lista de abilities para el token Sanctum
     */
    public static function getTokenAbilities(User $user): array
    {
        $planSlug = $user->plan?->slug ?? 'free';

        return config("rentame.plans.{$planSlug}.abilities", ['basic']);
    }

    /**
     * Calcula la expiración del token según el plan.
     * Plan gratuito: 1 día | Plan Pro: 30 días
     *
     * @param  User $user
     * @return \Carbon\Carbon Fecha/hora de expiración
     */
    public static function getTokenExpiration(User $user): \Carbon\Carbon
    {
        $planSlug = $user->plan?->slug ?? 'free';

        return match ($planSlug) {
            'pro'   => now()->addDays(30),
            default => now()->addDay(),
        };
    }

    /**
     * Obtiene el resumen de uso del plan para el usuario.
     * Útil para mostrar en el dashboard y settings.
     *
     * @param  User $user
     * @return array<string, mixed>
     */
    public static function getUsageSummary(User $user): array
    {
        $planSlug = $user->plan?->slug ?? 'free';

        return [
            'plan_slug'    => $planSlug,
            'plan_name'    => $user->plan?->name ?? 'Gratuito',
            'expires_at'   => $user->plan_expires_at,
            'assets' => [
                'used'  => $user->assets()->count(),
                'limit' => self::getPlanLimit($user, 'max_assets'),
            ],
            'customers' => [
                'used'  => $user->customers()->count(),
                'limit' => self::getPlanLimit($user, 'max_customers'),
            ],
            'rentals_this_month' => [
                'used'  => $user->rentals()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'limit' => self::getPlanLimit($user, 'max_rentals_per_month'),
            ],
            'extra_services' => [
                'used'  => $user->extraServices()->count(),
                'limit' => self::getPlanLimit($user, 'max_extra_services'),
            ],
            'features' => config("rentame.plans.{$planSlug}.features", []),
        ];
    }
}

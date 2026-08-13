<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Support\PlanHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends BaseController
{
    /**
     * Lista de planes disponibles (público)
     */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->get();

        return $this->success(
            PlanResource::collection($plans),
            'Planes obtenidos exitosamente.'
        );
    }

    /**
     * Resumen del plan actual del usuario autenticado
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $usage = PlanHelper::getUsageSummary($user);

        return $this->success($usage, 'Plan actual y métricas de uso.');
    }

    /**
     * Suscripción o cambio de plan (ej. Upgrade a Pro)
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ]);

        $user = $request->user();
        $targetPlan = Plan::where('slug', $request->plan_slug)->firstOrFail();

        $user->update([
            'plan_id' => $targetPlan->id,
            'plan_expires_at' => $targetPlan->duration_days > 0
                ? now()->addDays($targetPlan->duration_days)
                : null,
        ]);

        return $this->success(
            PlanHelper::getUsageSummary($user->fresh()),
            "Te has suscrito exitosamente al {$targetPlan->name}."
        );
    }
}

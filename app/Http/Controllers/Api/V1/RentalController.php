<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Rental\StoreRentalRequest;
use App\Http\Requests\Rental\UpdateRentalRequest;
use App\Http\Resources\RentalResource;
use App\Models\Asset;
use App\Models\ExtraService;
use App\Models\Rental;
use App\Models\RentalExtra;
use App\Support\PlanHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RentalController extends BaseController
{
    /**
     * Lista paginada de rentas con filtros Spatie
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rentals = QueryBuilder::for(Rental::where('user_id', $userId))
            ->with(['customer', 'asset', 'extras', 'payments'])
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('payment_status'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('asset_id'),
            ])
            ->allowedSorts([
                'start_date',
                'end_date',
                'total_amount_cents',
                'created_at',
            ])
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            $rentals,
            RentalResource::collection($rentals),
            'Rentas obtenidas exitosamente.'
        );
    }

    /**
     * Registrar un nuevo contrato de renta
     */
    public function store(StoreRentalRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! PlanHelper::canCreateRental($user)) {
            $limit = PlanHelper::getPlanLimit($user, 'max_rentals_per_month');
            return $this->error(
                "Has alcanzado el límite de {$limit} rentas mensuales de tu plan. Actualiza a Pro para registrar rentas ilimitadas.",
                403
            );
        }

        $validated = $request->validated();
        
        $assetIds = $validated['asset_ids'] ?? (isset($validated['asset_id']) ? [$validated['asset_id']] : []);
        
        if (count($assetIds) > 1 && ! PlanHelper::isPro($user)) {
            return $this->error(
                'El Plan Gratuito permite solo 1 activo por contrato de renta. Actualiza al Plan Pro para rentar múltiples activos en un solo paquete o combo.',
                403
            );
        }

        $assets = Asset::whereIn('id', $assetIds)->where('user_id', $user->id)->get();
        if ($assets->isEmpty()) {
            return $this->error('Debes seleccionar al menos un activo válido para la renta.', 422);
        }

        $mainAsset = $assets->first();

        // Calcular días de renta (mínimo 1 día)
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate   = Carbon::parse($validated['end_date'])->startOfDay();
        $daysDiff  = (int) $startDate->diffInDays($endDate);
        $rentalDays = max(1, $daysDiff);

        // Sumar monto base de todos los activos seleccionados
        $baseAmountCents = 0;
        foreach ($assets as $assetItem) {
            $baseAmountCents += $this->calculateBaseAmount($assetItem, $rentalDays);
        }

        $depositCents = $validated['deposit_cents'] ?? ($mainAsset->deposit_cents ?? 0);
        $discountCents = $validated['discount_cents'] ?? 0;

        return DB::transaction(function () use ($user, $validated, $assets, $mainAsset, $startDate, $endDate, $rentalDays, $baseAmountCents, $depositCents, $discountCents) {
            
            // Crear la renta
            $rental = Rental::create([
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'],
                'asset_id' => $mainAsset->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'rental_days' => $rentalDays,
                'base_amount_cents' => $baseAmountCents,
                'deposit_cents' => $depositCents,
                'discount_cents' => $discountCents,
                'total_amount_cents' => 0,
                'status' => 'active',
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Asociar todos los activos a la tabla pivote rental_assets
            foreach ($assets as $assetItem) {
                $subtotal = $this->calculateBaseAmount($assetItem, $rentalDays);
                \App\Models\RentalAsset::create([
                    'rental_id' => $rental->id,
                    'asset_id' => $assetItem->id,
                    'daily_rate_cents' => $assetItem->daily_rate_cents,
                    'subtotal_cents' => $subtotal,
                ]);

                // Actualizar estado del activo a 'rented'
                $assetItem->update(['status' => 'rented']);
            }

            // Procesar servicios extras
            $extrasTotalCents = 0;
            if (! empty($validated['extras'])) {
                foreach ($validated['extras'] as $extraItem) {
                    $service = ExtraService::find($extraItem['extra_service_id']);
                    $unitPrice = $extraItem['unit_price_cents'] ?? ($service ? $service->price_cents : 0);
                    $qty = $extraItem['quantity'] ?? 1;
                    $itemTotal = $unitPrice * $qty;
                    $extrasTotalCents += $itemTotal;

                    RentalExtra::create([
                        'rental_id' => $rental->id,
                        'extra_service_id' => $service?->id,
                        'name' => $service?->name ?? 'Servicio Extra',
                        'quantity' => $qty,
                        'unit_price_cents' => $unitPrice,
                        'total_price_cents' => $itemTotal,
                    ]);
                }
            }

            // Calcular monto final total
            $totalAmountCents = max(0, $baseAmountCents + $extrasTotalCents + $depositCents - $discountCents);
            $rental->update(['total_amount_cents' => $totalAmountCents]);

            $rental->load(['customer', 'asset', 'assets', 'extras', 'payments']);

            return $this->created(
                new RentalResource($rental),
                'Contrato de renta creado exitosamente.'
            );
        });
    }

    /**
     * Detalle de una renta
     */
    public function show(Request $request, Rental $rental): JsonResponse
    {
        if ($rental->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rental->load(['customer', 'asset', 'extras', 'payments']);

        return $this->success(
            new RentalResource($rental)
        );
    }

    /**
     * Actualizar contrato de renta
     */
    public function update(UpdateRentalRequest $request, Rental $rental): JsonResponse
    {
        if ($rental->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rental->update($request->validated());
        $rental->load(['customer', 'asset', 'extras', 'payments']);

        return $this->success(
            new RentalResource($rental),
            'Renta actualizada exitosamente.'
        );
    }

    /**
     * Eliminar renta
     */
    public function destroy(Request $request, Rental $rental): JsonResponse
    {
        if ($rental->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        // Liberar activo si la renta eliminada estaba activa
        if ($rental->status === 'active' && $rental->asset) {
            $rental->asset->update(['status' => 'available']);
        }

        $rental->delete();

        return $this->success(null, 'Renta eliminada exitosamente.');
    }

    /**
     * Completar / Finalizar entrega de renta y liberar activo
     */
    public function complete(Request $request, Rental $rental): JsonResponse
    {
        if ($rental->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rental->update([
            'status' => 'completed',
            'actual_return_date' => now()->toDateString(),
        ]);

        if ($rental->asset) {
            $rental->asset->update(['status' => 'available']);
        }

        $rental->load(['customer', 'asset', 'extras', 'payments']);

        return $this->success(
            new RentalResource($rental),
            'Renta completada y activo liberado exitosamente.'
        );
    }

    /**
     * Cancelar renta y liberar activo
     */
    public function cancel(Request $request, Rental $rental): JsonResponse
    {
        if ($rental->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rental->update([
            'status' => 'cancelled',
        ]);

        if ($rental->asset) {
            $rental->asset->update(['status' => 'available']);
        }

        $rental->load(['customer', 'asset', 'extras', 'payments']);

        return $this->success(
            new RentalResource($rental),
            'Renta cancelada y activo liberado exitosamente.'
        );
    }

    /**
     * Generar y descargar contrato en formato PDF
     */
    public function contractPdf(Request $request, Rental $rental): Response|JsonResponse
    {
        if ($rental->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rental->load(['customer', 'asset', 'extras']);

        $pdf = Pdf::loadView('pdf.contract', [
            'rental' => $rental,
            'user' => $request->user(),
        ]);

        return $pdf->download("Contrato_{$rental->folio}.pdf");
    }

    /**
     * Helper para calcular el monto base según tarifas diarias/semanales/mensuales
     */
    private function calculateBaseAmount(Asset $asset, int $days): int
    {
        $dailyRate = $asset->daily_rate_cents;

        // Si tiene tarifa mensual configurada y la renta es >= 30 días
        if ($asset->monthly_rate_cents && $days >= 30) {
            $months = intdiv($days, 30);
            $remDays = $days % 30;
            return ($months * $asset->monthly_rate_cents) + ($remDays * $dailyRate);
        }

        // Si tiene tarifa semanal configurada y la renta es >= 7 días
        if ($asset->weekly_rate_cents && $days >= 7) {
            $weeks = intdiv($days, 7);
            $remDays = $days % 7;
            return ($weeks * $asset->weekly_rate_cents) + ($remDays * $dailyRate);
        }

        // Por defecto: tarifa diaria * días
        return $dailyRate * $days;
    }
}

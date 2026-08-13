<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Asset;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Rental;
use App\Support\PlanHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends BaseController
{
    /**
     * Valida si el usuario tiene acceso a la función de reportes (Plan Pro)
     */
    private function checkAccess(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! PlanHelper::hasFeature($user, 'reports')) {
            return $this->error(
                'Esta función requiere el plan Pro. Actualiza tu suscripción para acceder al módulo de reportes avanzados.',
                403
            );
        }
        return null;
    }

    /**
     * Reporte de Ingresos detallado
     */
    public function income(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());
        $assetId = $request->input('asset_id');

        $query = Payment::where('payments.user_id', $userId)
            ->where('payments.type', 'income')
            ->whereBetween('payments.payment_date', [$dateFrom, $dateTo])
            ->join('rentals', 'payments.rental_id', '=', 'rentals.id')
            ->join('customers', 'rentals.customer_id', '=', 'customers.id')
            ->join('assets', 'rentals.asset_id', '=', 'assets.id');

        if ($assetId) {
            $query->where('rentals.asset_id', $assetId);
        }

        $payments = (clone $query)->select([
            'payments.id',
            'payments.amount_cents',
            'payments.payment_date',
            'payments.method',
            'payments.reference',
            'rentals.folio as rental_folio',
            'customers.name as customer_name',
            'assets.name as asset_name',
        ])->orderBy('payments.payment_date', 'desc')->get();

        $totalIncomeCents = (int) $payments->sum('amount_cents');

        // Desglose por método de pago
        $byMethod = $payments->groupBy('method')->map(function ($items, $method) {
            return [
                'method' => $method,
                'total_cents' => $items->sum('amount_cents'),
                'count' => $items->count(),
            ];
        })->values();

        // Desglose por activo
        $byAsset = $payments->groupBy('asset_name')->map(function ($items, $name) {
            return [
                'asset_name' => $name,
                'total_cents' => $items->sum('amount_cents'),
                'count' => $items->count(),
            ];
        })->values();

        return $this->success([
            'period' => "{$dateFrom} al {$dateTo}",
            'total_income_cents' => $totalIncomeCents,
            'by_method' => $byMethod,
            'by_asset' => $byAsset,
            'items' => $payments,
        ], 'Reporte de ingresos generado exitosamente.');
    }

    /**
     * Reporte de Egresos y Mantenimientos
     */
    public function expenses(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());
        $type = $request->input('type');
        $assetId = $request->input('asset_id');

        $query = Expense::where('user_id', $userId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->with('asset');

        if ($type) {
            $query->where('type', $type);
        }
        if ($assetId) {
            $query->where('asset_id', $assetId);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();
        $totalExpenseCents = (int) $expenses->sum('amount_cents');

        $byType = $expenses->groupBy('type')->map(function ($items, $t) {
            return [
                'type' => $t,
                'total_cents' => $items->sum('amount_cents'),
                'count' => $items->count(),
            ];
        })->values();

        return $this->success([
            'period' => "{$dateFrom} al {$dateTo}",
            'total_expense_cents' => $totalExpenseCents,
            'by_type' => $byType,
            'items' => $expenses,
        ], 'Reporte de egresos generado exitosamente.');
    }

    /**
     * Reporte de Cuentas por Cobrar (Saldos pendientes y morosidad)
     */
    public function accountsReceivable(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;
        $today = Carbon::today();

        $rentals = Rental::where('user_id', $userId)
            ->where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->with(['customer', 'asset', 'payments'])
            ->get();

        $totalReceivableCents = 0;
        $items = [];

        foreach ($rentals as $r) {
            $paidCents = (int) $r->payments->where('type', 'income')->sum('amount_cents');
            $pendingCents = max(0, $r->total_amount_cents - $paidCents);

            if ($pendingCents > 0) {
                $totalReceivableCents += $pendingCents;
                $endDate = Carbon::parse($r->end_date);
                $overdueDays = $endDate->lt($today) ? (int) $endDate->diffInDays($today) : 0;

                $items[] = [
                    'rental_id' => $r->id,
                    'folio' => $r->folio,
                    'customer_name' => $r->customer?->name,
                    'customer_phone' => $r->customer?->phone,
                    'asset_name' => $r->asset?->name,
                    'end_date' => $r->end_date,
                    'total_amount_cents' => $r->total_amount_cents,
                    'paid_amount_cents' => $paidCents,
                    'pending_amount_cents' => $pendingCents,
                    'overdue_days' => $overdueDays,
                    'urgency' => $overdueDays > 0 ? 'overdue' : ($endDate->diffInDays($today) <= 3 ? 'soon' : 'normal'),
                ];
            }
        }

        // Ordenar por morosidad
        usort($items, fn($a, $b) => $b['overdue_days'] <=> $a['overdue_days']);

        return $this->success([
            'total_receivable_cents' => $totalReceivableCents,
            'count' => count($items),
            'items' => $items,
        ], 'Reporte de cuentas por cobrar generado exitosamente.');
    }

    /**
     * Reporte de Cuentas por Pagar (Mantenimientos/Egresos futuros)
     */
    public function accountsPayable(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        return $this->success([
            'total_payable_cents' => 0,
            'items' => [],
        ], 'Reporte de cuentas por pagar.');
    }

    /**
     * Reporte de Utilización y Rentabilidad de Activos
     */
    public function assetUtilization(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;
        $dateFrom = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()));
        $dateTo = Carbon::parse($request->input('date_to', now()->endOfMonth()->toDateString()));
        $totalDaysInPeriod = max(1, (int) $dateFrom->diffInDays($dateTo) + 1);

        $assets = Asset::where('user_id', $userId)->with(['rentals', 'expenses'])->get();
        $items = [];

        foreach ($assets as $asset) {
            // Rentas dentro del periodo
            $periodRentals = $asset->rentals()
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('start_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                      ->orWhereBetween('end_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                })
                ->get();

            $rentedDays = (int) $periodRentals->sum('rental_days');
            $utilizationPct = min(100, round(($rentedDays / $totalDaysInPeriod) * 100, 1));
            $incomeGenerated = (int) $periodRentals->sum('total_amount_cents');

            $maintenanceCost = (int) $asset->expenses()
                ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->sum('amount_cents');

            $netReturn = $incomeGenerated - $maintenanceCost;

            $items[] = [
                'asset_id' => $asset->id,
                'name' => $asset->name,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status,
                'rented_days' => $rentedDays,
                'total_days_in_period' => $totalDaysInPeriod,
                'utilization_pct' => $utilizationPct,
                'income_cents' => $incomeGenerated,
                'expense_cents' => $maintenanceCost,
                'net_return_cents' => $netReturn,
            ];
        }

        return $this->success([
            'period' => "{$dateFrom->toDateString()} al {$dateTo->toDateString()}",
            'total_assets' => count($items),
            'items' => $items,
        ], 'Reporte de utilización de activos generado.');
    }

    /**
     * Reporte de Balance General (Ingresos vs Egresos = Utilidad)
     */
    public function balance(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());

        $totalIncomeCents = (int) Payment::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->sum('amount_cents');

        $totalExpenseCents = (int) Expense::where('user_id', $userId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->sum('amount_cents');

        $netProfitCents = $totalIncomeCents - $totalExpenseCents;
        $marginPct = $totalIncomeCents > 0 ? round(($netProfitCents / $totalIncomeCents) * 100, 1) : 0;

        return $this->success([
            'period' => "{$dateFrom} al {$dateTo}",
            'total_income_cents' => $totalIncomeCents,
            'total_expense_cents' => $totalExpenseCents,
            'net_profit_cents' => $netProfitCents,
            'profit_margin_pct' => $marginPct,
        ], 'Reporte de balance generado exitosamente.');
    }

    /**
     * Exportar reporte a PDF profesional
     */
    public function exportPdf(Request $request): Response|JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $type = $request->input('report_type', 'balance');
        $user = $request->user();

        $pdfData = [
            'user' => $user,
            'title' => 'Reporte Financiero',
            'period' => $request->input('period', now()->translatedFormat('F Y')),
            'kpis' => [],
            'columns' => [],
            'items' => [],
        ];

        if ($type === 'balance') {
            $balanceRes = $this->balance($request)->getData(true)['data'];
            $pdfData['title'] = 'Estado de Resultados y Balance';
            $pdfData['kpis'] = [
                ['label' => 'Total Ingresos', 'value' => '$' . number_format($balanceRes['total_income_cents'] / 100, 2), 'color' => '#16a34a'],
                ['label' => 'Total Egresos', 'value' => '$' . number_format($balanceRes['total_expense_cents'] / 100, 2), 'color' => '#dc2626'],
                ['label' => 'Utilidad Neta', 'value' => '$' . number_format($balanceRes['net_profit_cents'] / 100, 2), 'color' => '#2563eb'],
                ['label' => 'Margen Operativo', 'value' => $balanceRes['profit_margin_pct'] . '%', 'color' => '#0f172a'],
            ];
        } elseif ($type === 'receivable') {
            $recRes = $this->accountsReceivable($request)->getData(true)['data'];
            $pdfData['title'] = 'Cuentas por Cobrar y Morosidad';
            $pdfData['kpis'] = [
                ['label' => 'Total por Cobrar', 'value' => '$' . number_format($recRes['total_receivable_cents'] / 100, 2), 'color' => '#dc2626'],
                ['label' => 'Contratos con Saldo', 'value' => (string) $recRes['count'], 'color' => '#2563eb'],
            ];
            $pdfData['columns'] = [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'customer_name', 'label' => 'Cliente'],
                ['key' => 'asset_name', 'label' => 'Activo'],
                ['key' => 'end_date', 'label' => 'Vencimiento'],
                ['key' => 'pending_amount_cents', 'label' => 'Saldo Pendiente', 'format' => 'currency', 'align' => 'text-right'],
            ];
            $pdfData['items'] = $recRes['items'];
        }

        $pdf = Pdf::loadView('pdf.financial_report', $pdfData);

        return $pdf->download("Reporte_{$type}_" . date('Ymd') . ".pdf");
    }

    /**
     * Reporte Histórico Vitalicio de Rentabilidad y ROI de Activos
     */
    public function assetRoi(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;

        $assets = Asset::where('user_id', $userId)
            ->with(['category'])
            ->get();

        $items = $assets->map(function ($asset) use ($userId) {
            // Contratos en los que ha participado el activo
            $rentals = Rental::where('user_id', $userId)
                ->where(function ($q) use ($asset) {
                    $q->where('asset_id', $asset->id)
                      ->orWhereHas('rentalAssets', function ($rq) use ($asset) {
                          $rq->where('asset_id', $asset->id);
                      });
                })
                ->where('status', '!=', 'cancelled')
                ->get();

            $rentalIds = $rentals->pluck('id');
            $rentalsCount = $rentals->count();
            $totalDaysRented = (int) $rentals->sum('rental_days');

            // Ingresos generados por los pagos de esas rentas
            $revenueCents = (int) Payment::whereIn('rental_id', $rentalIds)
                ->where('type', 'income')
                ->sum('amount_cents');

            // Egresos y mantenimientos registrados para este activo específico
            $expenseCents = (int) Expense::where('user_id', $userId)
                ->where('asset_id', $asset->id)
                ->sum('amount_cents');

            $netProfitCents = $revenueCents - $expenseCents;
            $roiMarginPct = $revenueCents > 0 ? round(($netProfitCents / $revenueCents) * 100, 1) : 0;

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'serial_number' => $asset->serial_number,
                'category_name' => $asset->category?->name ?? 'General',
                'status' => $asset->status,
                'daily_rate_cents' => $asset->daily_rate_cents,
                'rentals_count' => $rentalsCount,
                'total_days_rented' => $totalDaysRented,
                'total_revenue_cents' => $revenueCents,
                'total_expense_cents' => $expenseCents,
                'net_profit_cents' => $netProfitCents,
                'roi_margin_pct' => $roiMarginPct,
            ];
        })->sortByDesc('net_profit_cents')->values();

        $totalRevenueCents = (int) $items->sum('total_revenue_cents');
        $totalExpenseCents = (int) $items->sum('total_expense_cents');
        $totalNetProfitCents = $totalRevenueCents - $totalExpenseCents;

        return $this->success([
            'total_assets' => $items->count(),
            'total_revenue_cents' => $totalRevenueCents,
            'total_expense_cents' => $totalExpenseCents,
            'total_net_profit_cents' => $totalNetProfitCents,
            'items' => $items,
        ], 'Reporte histórico de ROI de activos generado exitosamente.');
    }

    /**
     * Reporte Ranking de Activos Más Solicitados / Demandados
     */
    public function assetDemand(Request $request): JsonResponse
    {
        if ($forbidden = $this->checkAccess($request)) return $forbidden;

        $userId = $request->user()->id;

        $assets = Asset::where('user_id', $userId)->with(['category'])->get();

        $items = $assets->map(function ($asset) use ($userId) {
            $rentals = Rental::where('user_id', $userId)
                ->where(function ($q) use ($asset) {
                    $q->where('asset_id', $asset->id)
                      ->orWhereHas('rentalAssets', function ($rq) use ($asset) {
                          $rq->where('asset_id', $asset->id);
                      });
                })
                ->where('status', '!=', 'cancelled')
                ->get();

            $rentalsCount = $rentals->count();
            $daysRented = (int) $rentals->sum('rental_days');

            // Determinar insignia de demanda
            $demandStatus = 'medium';
            $demandLabel = 'Demanda Normal';
            $demandClass = 'bg-info-subtle text-info-emphasis';

            if ($rentalsCount >= 5 || $daysRented >= 30) {
                $demandStatus = 'high';
                $demandLabel = '🔥 Alta Demanda (Re-invertir)';
                $demandClass = 'bg-success-subtle text-success';
            } elseif ($rentalsCount === 0) {
                $demandStatus = 'low';
                $demandLabel = '🧊 Sin Demanda / Estancado';
                $demandClass = 'bg-secondary-subtle text-secondary';
            }

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'serial_number' => $asset->serial_number,
                'category_name' => $asset->category?->name ?? 'General',
                'daily_rate_cents' => $asset->daily_rate_cents,
                'rentals_count' => $rentalsCount,
                'days_rented' => $daysRented,
                'demand_status' => $demandStatus,
                'demand_label' => $demandLabel,
                'demand_class' => $demandClass,
            ];
        })->sortByDesc('rentals_count')->values();

        return $this->success([
            'items' => $items,
        ], 'Reporte de demanda de activos generado exitosamente.');
    }
}

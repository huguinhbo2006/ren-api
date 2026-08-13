<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Asset;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseController
{
    /**
     * Resumen de métricas, KPIs, rentas por vencer y gráfica financiera del dashboard (con caché de 60s)
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = Cache::remember("dashboard_user_{$userId}", 60, function () use ($userId) {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();

        // 1. Conteo de rentas activas
        $totalRentalsActive = Rental::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        // 2. Ingresos del mes actual (pagos tipo 'income')
        $monthlyIncomeCents = (int) Payment::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('payment_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('amount_cents');

        // 3. Egresos del mes actual
        $monthlyExpensesCents = (int) Expense::where('user_id', $userId)
            ->whereBetween('expense_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('amount_cents');

        // 4. Cuentas por cobrar (saldo pendiente de contratos no pagados totalmente)
        $rentalsWithPending = Rental::where('user_id', $userId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->with(['payments'])
            ->get();

        $accountsReceivableCents = 0;
        foreach ($rentalsWithPending as $rental) {
            $paid = $rental->payments->where('type', 'income')->sum('amount_cents');
            $pending = max(0, $rental->total_amount_cents - $paid);
            $accountsReceivableCents += $pending;
        }

        // 5. Conteo de activos por estado
        $assetsCounts = Asset::where('user_id', $userId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $assetsAvailable   = $assetsCounts->get('available', 0);
        $assetsRented      = $assetsCounts->get('rented', 0);
        $assetsMaintenance = $assetsCounts->get('maintenance', 0);

        // 6. Rentas por vencer en los próximos 7 días
        $rentalsExpiringSoon = Rental::where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
            ->with([
                'customer:id,name,phone',
                'asset:id,name',
            ])
            ->orderBy('end_date')
            ->take(5)
            ->get()
            ->map(function ($rental) use ($now) {
                $daysRemaining = (int) $now->diffInDays(Carbon::parse($rental->end_date), false);
                return [
                    'id' => $rental->id,
                    'folio' => $rental->folio,
                    'end_date' => $rental->end_date->toDateString(),
                    'days_remaining' => max(0, $daysRemaining),
                    'customer' => $rental->customer ? [
                        'id' => $rental->customer->id,
                        'name' => $rental->customer->name,
                        'phone' => $rental->customer->phone,
                    ] : null,
                    'asset' => $rental->asset ? [
                        'id' => $rental->asset->id,
                        'name' => $rental->asset->name,
                    ] : null,
                ];
            });

        // 7. Últimos 5 pagos registrados
        $recentPayments = Payment::where('user_id', $userId)
            ->with(['rental:id,folio,customer_id', 'rental.customer:id,name'])
            ->latest('payment_date')
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'rental_id' => $payment->rental_id,
                    'rental_folio' => $payment->rental?->folio,
                    'customer_name' => $payment->rental?->customer?->name ?? 'N/A',
                    'amount_cents' => $payment->amount_cents,
                    'payment_date' => $payment->payment_date->toDateString(),
                    'method' => $payment->method,
                    'type' => $payment->type,
                    'reference' => $payment->reference,
                ];
            });

        // 8. Gráfica mensual de los últimos 6 meses (Ingresos vs Egresos)
        $monthlyChart = [];
        $monthsNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth()->toDateString();
            $mEnd   = $monthDate->copy()->endOfMonth()->toDateString();

            $income = (int) Payment::where('user_id', $userId)
                ->where('type', 'income')
                ->whereBetween('payment_date', [$mStart, $mEnd])
                ->sum('amount_cents');

            $expenses = (int) Expense::where('user_id', $userId)
                ->whereBetween('expense_date', [$mStart, $mEnd])
                ->sum('amount_cents');

            $monthlyChart[] = [
                'month' => $monthsNames[$monthDate->month - 1] . ' ' . $monthDate->format('y'),
                'income_cents' => $income,
                'expenses_cents' => $expenses,
            ];
        }

            return [
                'total_rentals_active' => $totalRentalsActive,
                'monthly_income_cents' => $monthlyIncomeCents,
                'monthly_expenses_cents' => $monthlyExpensesCents,
                'accounts_receivable_cents' => $accountsReceivableCents,
                'assets_available' => $assetsAvailable,
                'assets_rented' => $assetsRented,
                'assets_maintenance' => $assetsMaintenance,
                'rentals_expiring_soon' => $rentalsExpiringSoon,
                'recent_payments' => $recentPayments,
                'monthly_chart' => $monthlyChart,
            ];
        });

        return $this->success($data, 'Datos del dashboard obtenidos exitosamente.');
    }
}

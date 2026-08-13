<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Rental;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentController extends BaseController
{
    /**
     * Lista paginada de pagos registrados
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $payments = QueryBuilder::for(Payment::where('user_id', $userId))
            ->with(['rental.customer', 'rental.asset'])
            ->allowedFilters([
                AllowedFilter::exact('rental_id'),
                AllowedFilter::exact('method'),
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts([
                'payment_date',
                'amount_cents',
                'created_at',
            ])
            ->defaultSort('-payment_date')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            $payments,
            PaymentResource::collection($payments),
            'Pagos obtenidos exitosamente.'
        );
    }

    /**
     * Registrar un nuevo pago y actualizar el estatus de cobranza de la renta
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $rental = Rental::findOrFail($validated['rental_id']);

        return DB::transaction(function () use ($user, $validated, $rental) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'rental_id' => $rental->id,
                'amount_cents' => $validated['amount_cents'],
                'payment_date' => $validated['payment_date'] ?? now()->toDateString(),
                'method' => $validated['method'],
                'type' => $validated['type'] ?? 'income',
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Recalcular estado de pago de la renta
            $this->updateRentalPaymentStatus($rental);

            $payment->load(['rental.customer', 'rental.asset']);

            return $this->created(
                new PaymentResource($payment),
                'Pago registrado exitosamente.'
            );
        });
    }

    /**
     * Detalle de un pago
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $payment->load(['rental.customer', 'rental.asset']);

        return $this->success(
            new PaymentResource($payment)
        );
    }

    /**
     * Eliminar pago y recalcular cobranza de la renta
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rental = $payment->rental;

        DB::transaction(function () use ($payment, $rental) {
            $payment->delete();
            if ($rental) {
                $this->updateRentalPaymentStatus($rental);
            }
        });

        return $this->success(null, 'Pago eliminado y estatus de cobranza recalculado exitosamente.');
    }

    /**
     * Descargar recibo de pago en formato PDF
     */
    public function receiptPdf(Request $request, Payment $payment): Response|JsonResponse
    {
        if ($payment->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $payment->load(['rental.customer', 'rental.asset', 'rental.payments']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'user' => $request->user(),
        ]);

        return $pdf->download("Recibo_Pago_{$payment->id}.pdf");
    }

    /**
     * Resumen de ingresos por método de pago del mes actual
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $byMethod = Payment::where('user_id', $userId)
            ->where('type', 'income')
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('method, sum(amount_cents) as total_cents, count(*) as count')
            ->groupBy('method')
            ->get();

        $totalMonthCents = (int) $byMethod->sum('total_cents');

        return $this->success([
            'month' => $now->translatedFormat('F Y'),
            'total_income_cents' => $totalMonthCents,
            'by_method' => $byMethod,
        ], 'Resumen de ingresos obtenido exitosamente.');
    }

    /**
     * Recalcular y persistir payment_status de la renta
     */
    private function updateRentalPaymentStatus(Rental $rental): void
    {
        $totalPaidCents = (int) Payment::where('rental_id', $rental->id)
            ->where('type', 'income')
            ->sum('amount_cents');

        if ($totalPaidCents >= $rental->total_amount_cents && $rental->total_amount_cents > 0) {
            $rental->update(['payment_status' => 'paid']);
        } elseif ($totalPaidCents > 0) {
            $rental->update(['payment_status' => 'partial']);
        } else {
            $rental->update(['payment_status' => 'unpaid']);
        }
    }
}

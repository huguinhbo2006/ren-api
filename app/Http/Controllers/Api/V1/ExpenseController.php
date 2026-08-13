<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExpenseController extends BaseController
{
    /**
     * Lista paginada de egresos con filtros Spatie
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $expenses = QueryBuilder::for(Expense::where('user_id', $userId))
            ->with(['asset'])
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('asset_id'),
                AllowedFilter::partial('category'),
                AllowedFilter::partial('description'),
            ])
            ->allowedSorts([
                'expense_date',
                'amount_cents',
                'created_at',
            ])
            ->defaultSort('-expense_date')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            $expenses,
            ExpenseResource::collection($expenses),
            'Egresos obtenidos exitosamente.'
        );
    }

    /**
     * Registrar un nuevo egreso
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $receiptUrl = null;
        if ($request->hasFile('receipt')) {
            $receiptUrl = $request->file('receipt')->store('expenses', 'public');
        }

        $expense = Expense::create([
            'user_id' => $user->id,
            'asset_id' => $validated['asset_id'] ?? null,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount_cents' => $validated['amount_cents'],
            'expense_date' => $validated['expense_date'] ?? now()->toDateString(),
            'vendor' => $validated['vendor'] ?? null,
            'type' => $validated['type'],
            'receipt_url' => $receiptUrl,
        ]);

        $expense->load('asset');

        return $this->created(
            new ExpenseResource($expense),
            'Egreso registrado exitosamente.'
        );
    }

    /**
     * Detalle de un egreso
     */
    public function show(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $expense->load('asset');

        return $this->success(
            new ExpenseResource($expense)
        );
    }

    /**
     * Actualizar egreso
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $validated = $request->validated();

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_url) {
                Storage::disk('public')->delete($expense->receipt_url);
            }
            $validated['receipt_url'] = $request->file('receipt')->store('expenses', 'public');
        }

        $expense->update($validated);
        $expense->load('asset');

        return $this->success(
            new ExpenseResource($expense),
            'Egreso actualizado exitosamente.'
        );
    }

    /**
     * Eliminar egreso y su archivo adjunto
     */
    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        if ($expense->receipt_url) {
            Storage::disk('public')->delete($expense->receipt_url);
        }

        $expense->delete();

        return $this->success(null, 'Egreso eliminado exitosamente.');
    }

    /**
     * Subir o actualizar el comprobante / factura del egreso
     */
    public function uploadReceipt(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($expense->receipt_url) {
            Storage::disk('public')->delete($expense->receipt_url);
        }

        $path = $request->file('receipt')->store('expenses', 'public');
        $expense->update(['receipt_url' => $path]);

        return $this->success([
            'receipt_url' => Storage::disk('public')->url($path),
        ], 'Comprobante subido exitosamente.');
    }

    /**
     * Resumen de egresos por tipo del mes actual
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        $byType = Expense::where('user_id', $userId)
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('type, sum(amount_cents) as total_cents, count(*) as count')
            ->groupBy('type')
            ->get();

        $totalMonthCents = (int) $byType->sum('total_cents');

        return $this->success([
            'month' => $now->translatedFormat('F Y'),
            'total_expense_cents' => $totalMonthCents,
            'by_type' => $byType,
        ], 'Resumen de egresos obtenido exitosamente.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Support\PlanHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends BaseController
{
    /**
     * Lista paginada de clientes con filtros y búsqueda
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $customers = QueryBuilder::for(Customer::where('user_id', $userId))
            ->with(['rentals.payments'])
            ->withCount('rentals')
            ->allowedFilters([
                AllowedFilter::exact('is_active'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts([
                'name',
                'created_at',
            ])
            ->defaultSort('name')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            $customers,
            CustomerResource::collection($customers),
            'Clientes obtenidos exitosamente.'
        );
    }

    /**
     * Registrar un nuevo cliente (verifica límites del plan)
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! PlanHelper::canCreateCustomer($user)) {
            $limit = PlanHelper::getPlanLimit($user, 'max_customers');
            return $this->error(
                "Has alcanzado el límite de {$limit} clientes de tu plan. Actualiza a Pro para registrar clientes ilimitados.",
                403
            );
        }

        $customer = Customer::create(array_merge(
            $request->validated(),
            ['user_id' => $user->id]
        ));

        return $this->created(
            new CustomerResource($customer),
            'Cliente creado exitosamente.'
        );
    }

    /**
     * Detalle de un cliente
     */
    public function show(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $customer->load(['rentals.asset', 'rentals.payments']);
        $customer->loadCount('rentals');

        return $this->success(
            new CustomerResource($customer)
        );
    }

    /**
     * Actualizar datos del cliente
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        if ($customer->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $customer->update($request->validated());
        $customer->load(['rentals.payments']);
        $customer->loadCount('rentals');

        return $this->success(
            new CustomerResource($customer),
            'Cliente actualizado exitosamente.'
        );
    }

    /**
     * Eliminar cliente (Soft delete)
     */
    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $customer->delete();

        return $this->success(null, 'Cliente eliminado exitosamente.');
    }

    /**
     * Historial de rentas del cliente
     */
    public function rentals(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rentals = $customer->rentals()
            ->with(['asset:id,name', 'payments'])
            ->latest('start_date')
            ->get();

        return $this->success(
            $rentals,
            'Historial de rentas obtenido exitosamente.'
        );
    }

    /**
     * Estado de cuenta del cliente (Rentas, pagos y saldo total)
     */
    public function statement(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $rentals = $customer->rentals()
            ->with(['asset:id,name', 'payments'])
            ->orderBy('start_date', 'desc')
            ->get();

        $totalBilledCents = 0;
        $totalPaidCents = 0;

        $items = $rentals->map(function ($rental) use (&$totalBilledCents, &$totalPaidCents) {
            $paid = $rental->payments->where('type', 'income')->sum('amount_cents');
            $balance = max(0, $rental->total_amount_cents - $paid);

            $totalBilledCents += $rental->total_amount_cents;
            $totalPaidCents += $paid;

            return [
                'rental_id' => $rental->id,
                'folio' => $rental->folio,
                'asset_name' => $rental->asset?->name,
                'start_date' => $rental->start_date->toDateString(),
                'end_date' => $rental->end_date->toDateString(),
                'total_amount_cents' => $rental->total_amount_cents,
                'paid_amount_cents' => $paid,
                'balance_cents' => $balance,
                'status' => $rental->status,
                'payment_status' => $rental->payment_status,
            ];
        });

        return $this->success([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'rfc' => $customer->rfc,
            ],
            'summary' => [
                'total_billed_cents' => $totalBilledCents,
                'total_paid_cents' => $totalPaidCents,
                'balance_owed_cents' => max(0, $totalBilledCents - $totalPaidCents),
            ],
            'rentals' => $items,
        ], 'Estado de cuenta generado exitosamente.');
    }
}

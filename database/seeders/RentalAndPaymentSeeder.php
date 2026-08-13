<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExtraService;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalExtra;
use App\Models\User;
use Illuminate\Database\Seeder;

class RentalAndPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $assets = Asset::where('user_id', $user->id)->get();
            $customers = Customer::where('user_id', $user->id)->get();
            $services = ExtraService::where('user_id', $user->id)->get();

            if ($assets->isEmpty() || $customers->isEmpty()) {
                continue;
            }

            // 1. Renta Activa
            $asset1 = $assets->first();
            $cust1  = $customers->first();

            $rental1 = Rental::create([
                'user_id' => $user->id,
                'customer_id' => $cust1->id,
                'asset_id' => $asset1->id,
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'rental_days' => 7,
                'base_amount_cents' => ($asset1->daily_rate_cents * 7),
                'extras_amount_cents' => 0,
                'discount_cents' => 0,
                'deposit_cents' => $asset1->deposit_cents,
                'deposit_returned' => false,
                'total_amount_cents' => ($asset1->daily_rate_cents * 7),
                'status' => 'active',
                'payment_status' => 'partial',
                'notes' => 'Renta en curso. Entrega realizada en sitio.',
                'terms_text' => 'El arrendatario se compromete a entregar el activo en óptimas condiciones.',
            ]);

            // Add Extra to Rental 1 if services exist
            if ($services->isNotEmpty()) {
                $svc = $services->first();
                RentalExtra::create([
                    'rental_id' => $rental1->id,
                    'extra_service_id' => $svc->id,
                    'name' => $svc->name,
                    'quantity' => 1,
                    'unit_price_cents' => $svc->price_cents,
                    'subtotal_cents' => $svc->price_cents,
                ]);
                $rental1->calculateTotal();
                $rental1->save();
            }

            // Pagos de la Renta 1: Depósito + 50% de anticipo
            Payment::create([
                'rental_id' => $rental1->id,
                'user_id' => $user->id,
                'amount_cents' => $rental1->deposit_cents,
                'payment_date' => now()->subDays(2)->toDateString(),
                'method' => 'transfer',
                'reference' => 'DEP-' . rand(10000, 99999),
                'notes' => 'Depósito en garantía recibido',
                'type' => 'deposit',
            ]);

            Payment::create([
                'rental_id' => $rental1->id,
                'user_id' => $user->id,
                'amount_cents' => intval($rental1->total_amount_cents / 2),
                'payment_date' => now()->subDays(2)->toDateString(),
                'method' => 'transfer',
                'reference' => 'ANT-' . rand(10000, 99999),
                'notes' => '50% de anticipo de renta',
                'type' => 'income',
            ]);

            // 2. Renta Completada y Pagada (si hay más activos)
            if ($assets->count() > 1 && $customers->count() > 1) {
                $asset2 = $assets->get(1);
                $cust2  = $customers->get(1);

                $rental2 = Rental::create([
                    'user_id' => $user->id,
                    'customer_id' => $cust2->id,
                    'asset_id' => $asset2->id,
                    'start_date' => now()->subDays(15)->toDateString(),
                    'end_date' => now()->subDays(5)->toDateString(),
                    'actual_return_date' => now()->subDays(5)->toDateString(),
                    'rental_days' => 10,
                    'base_amount_cents' => ($asset2->daily_rate_cents * 10),
                    'extras_amount_cents' => 0,
                    'discount_cents' => 0,
                    'deposit_cents' => $asset2->deposit_cents,
                    'deposit_returned' => true,
                    'total_amount_cents' => ($asset2->daily_rate_cents * 10),
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'notes' => 'Renta finalizada exitosamente. Depósito reembolsado.',
                    'terms_text' => 'Contrato finalizado sin observaciones ni daños.',
                ]);

                Payment::create([
                    'rental_id' => $rental2->id,
                    'user_id' => $user->id,
                    'amount_cents' => $rental2->total_amount_cents,
                    'payment_date' => now()->subDays(15)->toDateString(),
                    'method' => 'card',
                    'reference' => 'TC-' . rand(10000, 99999),
                    'notes' => 'Pago total con tarjeta de crédito',
                    'type' => 'income',
                ]);
            }

            // 3. Egresos asociados al usuario
            Expense::create([
                'user_id' => $user->id,
                'asset_id' => $asset1->id,
                'category' => 'Mantenimiento Preventivo',
                'description' => 'Revisión y afinación general previa a temporada de rentas',
                'amount_cents' => 150000,
                'expense_date' => now()->subDays(10)->toDateString(),
                'vendor' => 'Taller Mecánico Especializado S.A.',
                'receipt_url' => null,
                'type' => 'maintenance',
            ]);

            Expense::create([
                'user_id' => $user->id,
                'asset_id' => null,
                'category' => 'Insumos de Limpieza',
                'description' => 'Compra mensual de desinfectante y productos de empaque',
                'amount_cents' => 48000,
                'expense_date' => now()->subDays(3)->toDateString(),
                'vendor' => 'Distribuidora de Limpieza del Centro',
                'receipt_url' => null,
                'type' => 'other',
            ]);
        }
    }
}

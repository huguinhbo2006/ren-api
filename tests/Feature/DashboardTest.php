<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_returns_correct_kpis(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id, 'status' => 'rented']);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'rental_days' => 4,
            'base_amount_cents' => 40000,
            'total_amount_cents' => 40000,
            'status' => 'active',
            'payment_status' => 'partial',
        ]);

        Payment::create([
            'rental_id' => $rental->id,
            'user_id' => $user->id,
            'amount_cents' => 15000,
            'payment_date' => now()->toDateString(),
            'type' => 'income',
            'method' => 'transfer',
        ]);

        Expense::create([
            'user_id' => $user->id,
            'asset_id' => $asset->id,
            'category' => 'Mantenimiento',
            'description' => 'Servicio preventivo',
            'amount_cents' => 5000,
            'expense_date' => now()->toDateString(),
            'type' => 'maintenance',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_rentals_active', 1)
            ->assertJsonPath('data.monthly_income_cents', 15000)
            ->assertJsonPath('data.monthly_expenses_cents', 5000)
            ->assertJsonPath('data.accounts_receivable_cents', 25000)
            ->assertJsonStructure([
                'data' => [
                    'total_rentals_active',
                    'monthly_income_cents',
                    'monthly_expenses_cents',
                    'accounts_receivable_cents',
                    'assets_available',
                    'assets_rented',
                    'assets_maintenance',
                    'rentals_expiring_soon',
                    'recent_payments',
                    'monthly_chart',
                ],
            ]);
    }
}

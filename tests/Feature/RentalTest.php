<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\ExtraService;
use App\Models\Plan;
use App\Models\Rental;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_create_rental_and_calculates_total_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create([
            'user_id' => $user->id,
            'daily_rate_cents' => 10000, // $100 MXN / día
            'deposit_cents' => 20000,    // $200 MXN depósito
            'status' => 'available',
        ]);
        $extraService = ExtraService::create([
            'user_id' => $user->id,
            'name' => 'Combustible extra',
            'price_cents' => 5000,
            'unit' => 'tanque',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/rentals', [
                'customer_id' => $customer->id,
                'asset_id' => $asset->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(), // 3 días: 3 * $100 = $300 (30,000 cents)
                'extras' => [
                    [
                        'extra_service_id' => $extraService->id,
                        'quantity' => 2, // 2 * $50 = $100 (10,000 cents)
                    ],
                ],
                'discount_cents' => 5000, // Descuento $50 (-5,000 cents)
            ]);

        // Total: Base 30000 + Extras 10000 + Deposit 20000 - Discount 5000 = 55000 cents
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.base_amount_cents', 30000)
            ->assertJsonPath('data.total_amount_cents', 55000)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'rented',
        ]);
    }

    public function test_complete_rental_sets_asset_back_to_available(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id, 'status' => 'rented']);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->toDateString(),
            'rental_days' => 2,
            'base_amount_cents' => 20000,
            'total_amount_cents' => 20000,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/rentals/{$rental->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'available',
        ]);
    }

    public function test_cancel_rental_sets_asset_back_to_available(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id, 'status' => 'rented']);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'rental_days' => 2,
            'base_amount_cents' => 20000,
            'total_amount_cents' => 20000,
            'status' => 'active',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/rentals/{$rental->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'available',
        ]);
    }

    public function test_user_can_generate_contract_pdf(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id, 'status' => 'rented']);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'rental_days' => 2,
            'base_amount_cents' => 20000,
            'total_amount_cents' => 20000,
            'status' => 'active',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/rentals/{$rental->id}/contract-pdf");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }
}

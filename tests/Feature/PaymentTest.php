<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Rental;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_payment_updates_rental_payment_status_to_partial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'rental_days' => 2,
            'base_amount_cents' => 40000,
            'total_amount_cents' => 40000,
            'status' => 'active',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/payments', [
                'rental_id' => $rental->id,
                'amount_cents' => 15000,
                'method' => 'cash',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $rental->refresh();
        $this->assertEquals('partial', $rental->payment_status);
    }

    public function test_payment_updates_rental_payment_status_to_paid_when_completed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'rental_days' => 2,
            'base_amount_cents' => 30000,
            'total_amount_cents' => 30000,
            'status' => 'active',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/payments', [
                'rental_id' => $rental->id,
                'amount_cents' => 30000,
                'method' => 'transfer',
            ]);

        $response->assertStatus(201);

        $rental->refresh();
        $this->assertEquals('paid', $rental->payment_status);
    }

    public function test_destroy_payment_recalculates_rental_payment_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $rental = Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'rental_days' => 2,
            'base_amount_cents' => 30000,
            'total_amount_cents' => 30000,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'user_id' => $user->id,
            'amount_cents' => 30000,
            'payment_date' => now()->toDateString(),
            'method' => 'card',
            'type' => 'income',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(200);

        $rental->refresh();
        $this->assertEquals('unpaid', $rental->payment_status);
    }

    public function test_user_can_generate_payment_receipt_pdf(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id]);

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

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'user_id' => $user->id,
            'amount_cents' => 20000,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'type' => 'income',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/v1/payments/{$payment->id}/receipt-pdf");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }
}

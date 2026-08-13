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

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_list_own_customers(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Customer::factory()->count(4)->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        Customer::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 4);
    }

    public function test_user_cannot_see_other_user_customers(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $otherUser = User::factory()->create();
        $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/customers/{$otherCustomer->id}");

        $response->assertStatus(403);
    }

    public function test_free_user_cannot_create_more_than_10_customers(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $user = User::factory()->create(['plan_id' => $freePlan->id]);
        $user->assignRole('user');

        // Create 10 customers (max limit for Free plan)
        Customer::factory()->count(10)->create(['user_id' => $user->id]);

        // Attempt to create 11th customer
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/customers', [
                'name' => 'Cliente Once No Permitido',
                'phone' => '5551234567',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_pro_user_can_create_unlimited_customers(): void
    {
        $proPlan = Plan::where('slug', 'pro')->first();
        $user = User::factory()->create([
            'plan_id' => $proPlan->id,
            'plan_expires_at' => now()->addMonth(),
        ]);
        $user->assignRole('user');

        // Create 10 customers
        Customer::factory()->count(10)->create(['user_id' => $user->id]);

        // Pro user can create 11th customer
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/customers', [
                'name' => 'Cliente Once Permitido en Pro',
                'phone' => '5559876543',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cliente Once Permitido en Pro');
    }

    public function test_user_can_view_customer_statement(): void
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
            'end_date' => now()->addDays(5)->toDateString(),
            'rental_days' => 5,
            'base_amount_cents' => 50000,
            'total_amount_cents' => 50000,
            'status' => 'active',
            'payment_status' => 'partial',
        ]);

        Payment::create([
            'rental_id' => $rental->id,
            'user_id' => $user->id,
            'amount_cents' => 20000,
            'payment_date' => now()->toDateString(),
            'type' => 'income',
            'method' => 'cash',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}/statement");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_billed_cents', 50000)
            ->assertJsonPath('data.summary.total_paid_cents', 20000)
            ->assertJsonPath('data.summary.balance_owed_cents', 30000)
            ->assertJsonCount(1, 'data.rentals');
    }
}

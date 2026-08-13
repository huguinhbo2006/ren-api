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

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_free_user_cannot_access_reports(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $user = User::factory()->create(['plan_id' => $freePlan->id]);
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/reports/balance');

        $response->assertStatus(403);
    }

    public function test_pro_user_can_access_balance_report(): void
    {
        $proPlan = Plan::where('slug', 'pro')->first();
        $user = User::factory()->create(['plan_id' => $proPlan->id]);
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/reports/balance');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'total_income_cents',
                    'total_expense_cents',
                    'net_profit_cents',
                    'profit_margin_pct',
                ],
            ]);
    }

    public function test_pro_user_can_access_accounts_receivable_report(): void
    {
        $proPlan = Plan::where('slug', 'pro')->first();
        $user = User::factory()->create(['plan_id' => $proPlan->id]);
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'rental_days' => 4,
            'base_amount_cents' => 40000,
            'total_amount_cents' => 40000,
            'status' => 'active',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/reports/accounts-receivable');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_receivable_cents', 40000)
            ->assertJsonPath('data.count', 1);
    }

    public function test_pro_user_can_export_report_pdf(): void
    {
        $proPlan = Plan::where('slug', 'pro')->first();
        $user = User::factory()->create(['plan_id' => $proPlan->id]);
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/v1/reports/export-pdf', [
                'report_type' => 'balance',
            ]);

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }
}

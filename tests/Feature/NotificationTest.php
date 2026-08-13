<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Rental;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_get_notifications_and_unread_count(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Notification::create([
            'user_id' => $user->id,
            'type' => 'rental_expiring',
            'title' => 'Contrato por vencer',
            'message' => 'Tu contrato vence mañana',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $countResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count');

        $countResponse->assertStatus(200)
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'system',
            'title' => 'Bienvenido',
            'message' => 'Gracias por unirte a Rentame',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_check_rental_deadlines_command_generates_notifications(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        // Renta que vence mañana (dentro de los 3 días)
        Rental::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'asset_id' => $asset->id,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'rental_days' => 3,
            'base_amount_cents' => 30000,
            'total_amount_cents' => 30000,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $this->artisan('rentame:check-rentals')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'rental_expiring',
        ]);
    }
}

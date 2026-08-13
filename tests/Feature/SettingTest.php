<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    public function test_user_can_get_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'business_name',
                    'business_rfc',
                    'contract_template',
                    'notification_days_before',
                ],
            ]);
    }

    public function test_user_can_update_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/settings', [
                'business_name' => 'Arrendadora del Norte SA de CV',
                'business_rfc' => 'ANO200101XYZ',
                'notification_days_before' => '5',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.business_name', 'Arrendadora del Norte SA de CV')
            ->assertJsonPath('data.business_rfc', 'ANO200101XYZ')
            ->assertJsonPath('data.notification_days_before', '5');
    }

    public function test_user_can_upload_logo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/v1/settings/logo', [
                'logo' => $logo,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'business_logo',
                    'business_logo_url',
                ],
            ]);
    }

    public function test_user_can_upgrade_plan_to_pro(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $user = User::factory()->create(['plan_id' => $freePlan->id]);
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/plans/subscribe', [
                'plan_slug' => 'pro',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.plan_slug', 'pro');

        $this->assertEquals('pro', $user->fresh()->plan->slug);
    }
}

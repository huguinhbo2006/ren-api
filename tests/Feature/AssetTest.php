<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_list_own_assets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        Asset::factory()->count(3)->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        Asset::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/assets');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_user_cannot_see_other_user_assets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $otherUser = User::factory()->create();
        $otherAsset = Asset::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/assets/{$otherAsset->id}");

        $response->assertStatus(403);
    }

    public function test_free_user_cannot_create_more_than_3_assets(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $user = User::factory()->create(['plan_id' => $freePlan->id]);
        $user->assignRole('user');

        // Create 3 existing assets (max for free plan)
        Asset::factory()->count(3)->create(['user_id' => $user->id]);

        // Attempt to create 4th asset
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/assets', [
                'name' => 'Cuarto Activo No Permitido',
                'daily_rate_cents' => 15000,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_pro_user_can_create_more_than_3_assets(): void
    {
        $proPlan = Plan::where('slug', 'pro')->first();
        $user = User::factory()->create([
            'plan_id' => $proPlan->id,
            'plan_expires_at' => now()->addMonth(),
        ]);
        $user->assignRole('user');

        // Create 3 existing assets
        Asset::factory()->count(3)->create(['user_id' => $user->id]);

        // Pro user can create 4th asset
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/assets', [
                'name' => 'Cuarto Activo Permitido en Pro',
                'daily_rate_cents' => 25000,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cuarto Activo Permitido en Pro');
    }

    public function test_user_can_upload_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('user');
        $asset = Asset::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->image('asset.jpg', 600, 400);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/assets/{$asset->id}/photos", [
                'photo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data.images'));
    }

    public function test_user_can_filter_assets_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Asset::factory()->create(['user_id' => $user->id, 'status' => 'available']);
        Asset::factory()->create(['user_id' => $user->id, 'status' => 'available']);
        Asset::factory()->create(['user_id' => $user->id, 'status' => 'rented']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/assets?filter[status]=available');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }
}

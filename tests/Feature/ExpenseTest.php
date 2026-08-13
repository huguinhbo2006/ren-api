<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Expense;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlansSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    public function test_user_can_create_expense_with_receipt(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $asset = Asset::factory()->create(['user_id' => $user->id]);
        $receipt = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/v1/expenses', [
                'asset_id' => $asset->id,
                'category' => 'Mantenimiento preventivo',
                'description' => 'Cambio de aceite y filtros',
                'amount_cents' => 150000,
                'expense_date' => now()->toDateString(),
                'vendor' => 'Taller Mecánico Central',
                'type' => 'maintenance',
                'receipt' => $receipt,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category', 'Mantenimiento preventivo')
            ->assertJsonPath('data.amount_cents', 150000)
            ->assertJsonPath('data.type', 'maintenance');

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'description' => 'Cambio de aceite y filtros',
            'amount_cents' => 150000,
        ]);
    }

    public function test_user_can_filter_expenses_by_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Expense::create([
            'user_id' => $user->id,
            'category' => 'Reparación',
            'description' => 'Freno nuevo',
            'amount_cents' => 50000,
            'type' => 'repair',
            'expense_date' => now()->toDateString(),
        ]);

        Expense::create([
            'user_id' => $user->id,
            'category' => 'Insumos',
            'description' => 'Compra de extensiones',
            'amount_cents' => 30000,
            'type' => 'purchase',
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/expenses?filter[type]=repair');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'repair');
    }

    public function test_user_can_delete_expense(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $expense = Expense::create([
            'user_id' => $user->id,
            'category' => 'Limpieza',
            'description' => 'Lavado de equipo',
            'amount_cents' => 20000,
            'type' => 'other',
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/expenses/{$expense->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }
}

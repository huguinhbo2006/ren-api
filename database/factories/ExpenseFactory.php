<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $expenses = [
            ['cat' => 'Mantenimiento Preventivo', 'desc' => 'Cambio de aceite, bujías y filtros de motor', 'amount' => 185000, 'type' => 'maintenance'],
            ['cat' => 'Reparación Correctiva', 'desc' => 'Reemplazo de balatas y rectificado de discos', 'amount' => 240000, 'type' => 'repair'],
            ['cat' => 'Insumos y Limpieza', 'desc' => 'Compra de detergente industrial y desinfectante', 'amount' => 45000, 'type' => 'maintenance'],
            ['cat' => 'Refacciones', 'desc' => 'Compra de cable de uso rudo y conectores trifásicos', 'amount' => 75000, 'type' => 'purchase'],
            ['cat' => 'Servicio Técnico', 'desc' => 'Calibración y actualización de firmware de consola', 'amount' => 120000, 'type' => 'maintenance'],
        ];

        $sample = fake()->randomElement($expenses);

        return [
            'user_id' => User::factory(),
            'asset_id' => null,
            'category' => $sample['cat'],
            'description' => $sample['desc'],
            'amount_cents' => $sample['amount'],
            'expense_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'vendor' => fake()->company() . ' S.A. de C.V.',
            'receipt_url' => null,
            'type' => $sample['type'],
        ];
    }
}

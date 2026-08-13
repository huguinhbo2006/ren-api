<?php

namespace Database\Factories;

use App\Models\ExtraService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraService>
 */
class ExtraServiceFactory extends Factory
{
    protected $model = ExtraService::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            ['name' => 'Flete / Entrega a domicilio', 'price_cents' => 35000, 'unit' => 'por viaje'],
            ['name' => 'Limpieza y Sanitización', 'price_cents' => 20000, 'unit' => 'por evento'],
            ['name' => 'Instalación y Montaje', 'price_cents' => 45000, 'unit' => 'por servicio'],
            ['name' => 'Operador / Chofer calificado', 'price_cents' => 80000, 'unit' => 'por día'],
            ['name' => 'Seguro contra daños menores', 'price_cents' => 25000, 'unit' => 'por renta'],
            ['name' => 'Combustible de inicio', 'price_cents' => 50000, 'unit' => 'por tanque'],
        ];

        $sample = fake()->randomElement($services);

        return [
            'user_id' => User::factory(),
            'name' => $sample['name'],
            'description' => fake()->sentence(6),
            'price_cents' => $sample['price_cents'],
            'unit' => $sample['unit'],
            'is_active' => true,
        ];
    }
}

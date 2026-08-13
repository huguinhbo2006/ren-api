<?php

namespace Database\Factories;

use App\Models\ExtraService;
use App\Models\Rental;
use App\Models\RentalExtra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalExtra>
 */
class RentalExtraFactory extends Factory
{
    protected $model = RentalExtra::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(15000, 50000);

        return [
            'rental_id' => Rental::factory(),
            'extra_service_id' => null,
            'name' => fake()->randomElement(['Flete de entrega', 'Limpieza profunda', 'Montaje técnico']),
            'quantity' => $qty,
            'unit_price_cents' => $unitPrice,
            'subtotal_cents' => $qty * $unitPrice,
        ];
    }
}

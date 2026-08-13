<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    protected $model = Rental::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');
        $rentalDays = fake()->numberBetween(1, 14);
        $endDate = (clone $startDate)->modify("+{$rentalDays} days");
        $baseAmount = fake()->numberBetween(50000, 500000);
        $deposit = fake()->numberBetween(20000, 100000);

        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'asset_id' => Asset::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'actual_return_date' => null,
            'rental_days' => $rentalDays,
            'base_amount_cents' => $baseAmount,
            'extras_amount_cents' => 0,
            'discount_cents' => 0,
            'deposit_cents' => $deposit,
            'deposit_returned' => false,
            'total_amount_cents' => $baseAmount,
            'status' => fake()->randomElement(['pending', 'active', 'completed']),
            'payment_status' => fake()->randomElement(['unpaid', 'partial', 'paid']),
            'notes' => fake()->optional(0.5)->sentence(8),
            'terms_text' => 'Contrato de arrendamiento sujeto a las políticas estándar de Rentame.',
        ];
    }
}

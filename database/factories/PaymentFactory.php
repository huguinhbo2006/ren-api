<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'user_id' => User::factory(),
            'amount_cents' => fake()->numberBetween(20000, 300000),
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'method' => fake()->randomElement(['cash', 'transfer', 'card']),
            'reference' => fake()->optional(0.7)->bothify('REF-#######'),
            'notes' => fake()->optional(0.4)->sentence(5),
            'type' => 'income',
        ];
    }
}

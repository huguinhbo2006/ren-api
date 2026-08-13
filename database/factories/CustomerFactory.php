<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('es_MX');

        return [
            'user_id' => User::factory(),
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->numerify('55########'),
            'rfc' => strtoupper($faker->bothify('????######???')),
            'address' => $faker->streetAddress() . ', ' . $faker->city() . ', ' . $faker->state(),
            'notes' => $faker->optional(0.7)->sentence(6),
            'is_active' => true,
        ];
    }
}

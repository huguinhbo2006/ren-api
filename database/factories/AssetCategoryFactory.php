<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            ['name' => 'Inmuebles y Locales', 'icon' => 'home-outline', 'color' => '#2563eb'],
            ['name' => 'Vehículos y Remolques', 'icon' => 'car-outline', 'color' => '#0284c7'],
            ['name' => 'Mobiliario y Eventos', 'icon' => 'calendar-outline', 'color' => '#16a34a'],
            ['name' => 'Maquinaria y Herramientas', 'icon' => 'construct-outline', 'color' => '#d97706'],
            ['name' => 'Equipo Audiovisual', 'icon' => 'videocam-outline', 'color' => '#7c3aed'],
            ['name' => 'Electrónica y Cómputo', 'icon' => 'laptop-outline', 'color' => '#e11d48'],
        ];

        $sample = fake()->randomElement($categories);

        return [
            'user_id' => User::factory(),
            'name' => $sample['name'],
            'icon' => $sample['icon'],
            'color' => $sample['color'],
            'description' => fake()->sentence(8),
        ];
    }
}

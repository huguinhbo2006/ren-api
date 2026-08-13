<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assets = [
            // Inmuebles
            ['name' => 'Salón de Eventos Las Palmas (Cap. 150 personas)', 'daily' => 450000, 'weekly' => 2000000, 'monthly' => 6000000, 'deposit' => 200000, 'location' => 'Col. Del Valle, CDMX'],
            ['name' => 'Oficina Ejecutiva Amueblada (4 puestos)', 'daily' => 80000, 'weekly' => 400000, 'monthly' => 1200000, 'deposit' => 120000, 'location' => 'Paseo de la Reforma, CDMX'],
            // Vehículos
            ['name' => 'Camioneta Nissan NP300 Chasis Cabina 2023', 'daily' => 120000, 'weekly' => 700000, 'monthly' => 2400000, 'deposit' => 500000, 'location' => 'Patio Central de Operaciones'],
            ['name' => 'Remolque de Carga 2 Ejes 3.5 Toneladas', 'daily' => 45000, 'weekly' => 250000, 'monthly' => 850000, 'deposit' => 200000, 'location' => 'Patio Central de Operaciones'],
            // Mobiliario
            ['name' => 'Set de 10 Mesas Redondas con 100 Sillas Tiffany', 'daily' => 35000, 'weekly' => 180000, 'monthly' => 550000, 'deposit' => 100000, 'location' => 'Bodega 1'],
            ['name' => 'Carpa Panorámica 6x12m de Aluminio', 'daily' => 60000, 'weekly' => 300000, 'monthly' => 900000, 'deposit' => 150000, 'location' => 'Bodega 1'],
            // Maquinaria
            ['name' => 'Revolvedora de Concreto 1 Saco Motor Honda', 'daily' => 40000, 'weekly' => 220000, 'monthly' => 750000, 'deposit' => 150000, 'location' => 'Almacén de Maquinaria'],
            ['name' => 'Generador Eléctrico 8500W a Gasolina', 'daily' => 55000, 'weekly' => 280000, 'monthly' => 950000, 'deposit' => 250000, 'location' => 'Almacén de Maquinaria'],
            // Audiovisual
            ['name' => 'Sistema de Audio Line Array 4000W + Consola Digital', 'daily' => 95000, 'weekly' => 500000, 'monthly' => 1600000, 'deposit' => 300000, 'location' => 'Cuarto de Audio'],
            ['name' => 'Proyector Láser 6000 Lúmenes + Pantalla 150"', 'daily' => 50000, 'weekly' => 260000, 'monthly' => 800000, 'deposit' => 200000, 'location' => 'Cuarto de Audio'],
        ];

        $sample = fake()->randomElement($assets);

        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'name' => $sample['name'],
            'description' => fake()->paragraph(2),
            'serial_number' => strtoupper(fake()->bothify('SN-####-????')),
            'daily_rate_cents' => $sample['daily'],
            'weekly_rate_cents' => $sample['weekly'],
            'monthly_rate_cents' => $sample['monthly'],
            'deposit_cents' => $sample['deposit'],
            'status' => fake()->randomElement(['available', 'available', 'rented', 'maintenance']),
            'location' => $sample['location'],
            'notes' => fake()->optional(0.5)->sentence(5),
            'images_json' => [],
        ];
    }
}

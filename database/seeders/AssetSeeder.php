<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $assetTemplates = [
            'Inmuebles y Locales' => [
                [
                    'name' => 'Salón de Eventos Las Palmas (150 personas)',
                    'description' => 'Espacio climatizado con cocina, sanitarios, área de mesas y jardín exterior.',
                    'serial_number' => 'INM-001',
                    'daily_rate_cents' => 450000,
                    'weekly_rate_cents' => 2000000,
                    'monthly_rate_cents' => 6000000,
                    'deposit_cents' => 200000,
                    'status' => 'available',
                    'location' => 'Av. Insurgentes Sur 1200, Col. Del Valle, CDMX',
                ],
                [
                    'name' => 'Oficina Ejecutiva Amueblada (4 puestos)',
                    'description' => 'Incluye internet de alta velocidad, sala de juntas compartida y recepción.',
                    'serial_number' => 'INM-002',
                    'daily_rate_cents' => 80000,
                    'weekly_rate_cents' => 400000,
                    'monthly_rate_cents' => 1200000,
                    'deposit_cents' => 120000,
                    'status' => 'rented',
                    'location' => 'Paseo de la Reforma 505, CDMX',
                ],
            ],
            'Vehículos y Remolques' => [
                [
                    'name' => 'Camioneta Nissan NP300 Chasis Cabina 2023',
                    'description' => 'Transmisión manual, motor 2.5L, capacidad de carga 1.5 toneladas.',
                    'serial_number' => 'VEH-NP300-8841',
                    'daily_rate_cents' => 120000,
                    'weekly_rate_cents' => 700000,
                    'monthly_rate_cents' => 2400000,
                    'deposit_cents' => 500000,
                    'status' => 'available',
                    'location' => 'Patio Central de Operaciones',
                ],
                [
                    'name' => 'Remolque de Carga 2 Ejes 3.5 Toneladas',
                    'description' => 'Plataforma con redilas de 4x2 metros, frenos eléctricos y tirón reforzado.',
                    'serial_number' => 'REM-2E-3500',
                    'daily_rate_cents' => 45000,
                    'weekly_rate_cents' => 250000,
                    'monthly_rate_cents' => 850000,
                    'deposit_cents' => 200000,
                    'status' => 'available',
                    'location' => 'Patio Central de Operaciones',
                ],
            ],
            'Mobiliario y Eventos' => [
                [
                    'name' => 'Set de 10 Mesas Redondas con 100 Sillas Tiffany',
                    'description' => 'Mesas de madera de 1.50m con cojines blancos para sillas tiffany doradas.',
                    'serial_number' => 'MOB-TIF-100',
                    'daily_rate_cents' => 35000,
                    'weekly_rate_cents' => 180000,
                    'monthly_rate_cents' => 550000,
                    'deposit_cents' => 100000,
                    'status' => 'available',
                    'location' => 'Bodega Principal - Rack A',
                ],
                [
                    'name' => 'Carpa Panorámica 6x12m de Aluminio',
                    'description' => 'Lona impermeable blanca con cortinas transparentes laterales.',
                    'serial_number' => 'MOB-CRP-612',
                    'daily_rate_cents' => 60000,
                    'weekly_rate_cents' => 300000,
                    'monthly_rate_cents' => 900000,
                    'deposit_cents' => 150000,
                    'status' => 'maintenance',
                    'location' => 'Bodega Principal - Área de Reparación',
                ],
            ],
            'Maquinaria y Herramientas' => [
                [
                    'name' => 'Revolvedora de Concreto 1 Saco Motor Honda 9HP',
                    'description' => 'Olla de acero de 1 saco, llantas neumáticas para arrastre.',
                    'serial_number' => 'MAQ-REV-H9',
                    'daily_rate_cents' => 40000,
                    'weekly_rate_cents' => 220000,
                    'monthly_rate_cents' => 750000,
                    'deposit_cents' => 150000,
                    'status' => 'available',
                    'location' => 'Almacén de Maquinaria Ligera',
                ],
                [
                    'name' => 'Generador Eléctrico 8500W a Gasolina',
                    'description' => 'Arranque eléctrico, autonomía de 10 horas al 50% de carga, salida 120/240V.',
                    'serial_number' => 'MAQ-GEN-8500',
                    'daily_rate_cents' => 55000,
                    'weekly_rate_cents' => 280000,
                    'monthly_rate_cents' => 950000,
                    'deposit_cents' => 250000,
                    'status' => 'available',
                    'location' => 'Almacén de Maquinaria Ligera',
                ],
            ],
            'Equipo Audiovisual' => [
                [
                    'name' => 'Sistema de Audio Line Array 4000W + Consola Digital',
                    'description' => '2 módulos line array, 2 subwoofers 18" y consola digital de 16 canales con Bluetooth.',
                    'serial_number' => 'AV-LA4000-01',
                    'daily_rate_cents' => 95000,
                    'weekly_rate_cents' => 500000,
                    'monthly_rate_cents' => 1600000,
                    'deposit_cents' => 300000,
                    'status' => 'available',
                    'location' => 'Cabina de Audio / Equipos Delicados',
                ],
            ],
        ];

        foreach ($users as $user) {
            // For free user, respect limit: seed at most 3 assets
            $isFree = $user->plan?->slug === 'free';
            $seededCount = 0;

            foreach ($assetTemplates as $categoryName => $assets) {
                $category = AssetCategory::where('user_id', $user->id)
                    ->where('name', $categoryName)
                    ->first();

                foreach ($assets as $assetData) {
                    if ($isFree && $seededCount >= 2) {
                        break 2; // stop seeding for free user
                    }

                    Asset::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'name' => $assetData['name'],
                        ],
                        array_merge($assetData, [
                            'user_id' => $user->id,
                            'category_id' => $category?->id,
                            'images_json' => [],
                        ])
                    );

                    $seededCount++;
                }
            }
        }
    }
}

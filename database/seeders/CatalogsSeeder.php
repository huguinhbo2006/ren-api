<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\ExtraService;
use App\Models\User;
use Illuminate\Database\Seeder;

class CatalogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $defaultCategories = [
            ['name' => 'Inmuebles y Locales', 'icon' => 'home-outline', 'color' => '#2563eb', 'description' => 'Casas, departamentos, salones de eventos y oficinas'],
            ['name' => 'Vehículos y Remolques', 'icon' => 'car-outline', 'color' => '#0284c7', 'description' => 'Autos, camionetas, motos y remolques de carga'],
            ['name' => 'Mobiliario y Eventos', 'icon' => 'calendar-outline', 'color' => '#16a34a', 'description' => 'Mesas, sillas, carpas, mantelería e inflables'],
            ['name' => 'Maquinaria y Herramientas', 'icon' => 'construct-outline', 'color' => '#d97706', 'description' => 'Revolvedoras, andamios, taladros y plantas de luz'],
            ['name' => 'Equipo Audiovisual', 'icon' => 'videocam-outline', 'color' => '#7c3aed', 'description' => 'Bocinas, micrófonos, proyectores e iluminación'],
        ];

        $defaultServices = [
            ['name' => 'Flete / Entrega a domicilio', 'description' => 'Transporte seguro del activo hasta tu ubicación', 'price_cents' => 35000, 'unit' => 'por viaje'],
            ['name' => 'Limpieza y Sanitización profunda', 'description' => 'Entrega y recepción con protocolo de limpieza', 'price_cents' => 20000, 'unit' => 'por evento'],
            ['name' => 'Instalación y Puesta en Marcha', 'description' => 'Montaje profesional por personal técnico', 'price_cents' => 45000, 'unit' => 'por servicio'],
        ];

        foreach ($users as $user) {
            foreach ($defaultCategories as $cat) {
                AssetCategory::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $cat['name']],
                    array_merge($cat, ['user_id' => $user->id])
                );
            }

            foreach ($defaultServices as $svc) {
                ExtraService::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $svc['name']],
                    array_merge($svc, ['user_id' => $user->id, 'is_active' => true])
                );
            }
        }
    }
}

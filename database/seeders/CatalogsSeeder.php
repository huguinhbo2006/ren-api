<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use App\Models\ExpenseCategory;
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
            ['name' => 'Capital Humano y Personal', 'icon' => 'people-outline', 'color' => '#ec4899', 'description' => 'Meseros, chambelanes, edecanes, choferes y operadores'],
            ['name' => 'Maquinaria y Herramientas', 'icon' => 'construct-outline', 'color' => '#d97706', 'description' => 'Revolvedoras, andamios, taladros y plantas de luz'],
            ['name' => 'Equipo Audiovisual', 'icon' => 'videocam-outline', 'color' => '#7c3aed', 'description' => 'Bocinas, micrófonos, proyectores e iluminación'],
        ];

        $defaultServices = [
            ['name' => 'Flete / Entrega a domicilio', 'description' => 'Transporte seguro del activo hasta tu ubicación', 'price_cents' => 35000, 'unit' => 'por viaje'],
            ['name' => 'Limpieza y Sanitización profunda', 'description' => 'Entrega y recepción con protocolo de limpieza', 'price_cents' => 20000, 'unit' => 'por evento'],
            ['name' => 'Instalación y Puesta en Marcha', 'description' => 'Montaje profesional por personal técnico', 'price_cents' => 45000, 'unit' => 'por servicio'],
        ];

        $defaultExpenseCategories = [
            ['name' => 'Mantenimiento Preventivo', 'icon' => 'bi-tools', 'color' => '#2563eb', 'description' => 'Servicios programados y afinaciones'],
            ['name' => 'Reparación Mecánica / Correctiva', 'icon' => 'bi-wrench-adjustable', 'color' => '#dc2626', 'description' => 'Reparaciones por fallas o desperfectos'],
            ['name' => 'Combustible / Gasolina', 'icon' => 'bi-fuel-pump', 'color' => '#d97706', 'description' => 'Cargas de gasolina o diésel'],
            ['name' => 'Seguros y Licencias', 'icon' => 'bi-shield-check', 'color' => '#16a34a', 'description' => 'Pólizas de seguro, tenencias y refrendos'],
            ['name' => 'Compra de Refacciones', 'icon' => 'bi-gear', 'color' => '#7c3aed', 'description' => 'Refacciones, consumibles y piezas de repuesto'],
            ['name' => 'Limpieza y Lavado', 'icon' => 'bi-droplet', 'color' => '#0284c7', 'description' => 'Lavado de vehículos o sanitización de equipos'],
            ['name' => 'Impuestos / Trámites', 'icon' => 'bi-receipt', 'color' => '#4b5563', 'description' => 'Pagos de derechos, contabilidad e impuestos'],
            ['name' => 'Gastos Administrativos', 'icon' => 'bi-building', 'color' => '#64748b', 'description' => 'Servicios, papelería y gastos generales de oficina'],
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

            foreach ($defaultExpenseCategories as $expCat) {
                ExpenseCategory::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $expCat['name']],
                    array_merge($expCat, ['user_id' => $user->id])
                );
            }
        }
    }
}

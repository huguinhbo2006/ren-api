<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan Gratuito',
                'slug' => 'free',
                'description' => 'Ideal para comenzar a administrar tus rentas. Hasta 3 activos y 10 clientes.',
                'price_cents' => 0,
                'duration_days' => 0,
                'features_json' => [
                    'reports' => false,
                    'export_pdf' => false,
                    'export_excel' => false,
                    'multi_user' => false,
                    'contract_pdf' => false,
                    'audit_log' => false,
                    'advanced_dashboard' => false,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Plan Pro',
                'slug' => 'pro',
                'description' => 'Para profesionales y negocios de renta. Activos, clientes y contratos ilimitados, con reportes financieros y contratos PDF.',
                'price_cents' => (int) env('RENTAME_PRO_PRICE_CENTS', 19900),
                'duration_days' => 30,
                'features_json' => [
                    'reports' => true,
                    'export_pdf' => true,
                    'export_excel' => true,
                    'multi_user' => true,
                    'contract_pdf' => true,
                    'audit_log' => true,
                    'advanced_dashboard' => true,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}

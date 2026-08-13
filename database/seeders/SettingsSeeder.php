<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $settings = [
                'business_name' => $user->name,
                'business_rfc' => 'RENT' . rand(100000, 999999) . 'AB1',
                'business_phone' => $user->phone ?? '5512345678',
                'business_address' => 'Av. Universidad 1200, Col. del Valle, CDMX',
                'contract_template' => "CONTRATO DE ARRENDAMIENTO DE BIENES MUEBLES E INMUEBLES\n\nConste por el presente documento el contrato de arrendamiento que celebran de una parte como EL ARRENDADOR y de otra parte como EL ARRENDATARIO, bajo los términos y condiciones estipulados en el presente folio de renta.\n\n1. El arrendatario se compromete al cuidado y devolución oportuna del bien arrendado.\n2. En caso de daño o extravío, se aplicará el depósito en garantía correspondiente.",
                'notification_days_before' => '3',
                'currency_symbol' => '$',
                'timezone' => 'America/Mexico_City',
                'invoice_prefix' => 'RNT',
            ];

            foreach ($settings as $key => $value) {
                Setting::set($user->id, $key, $value);
            }
        }
    }
}

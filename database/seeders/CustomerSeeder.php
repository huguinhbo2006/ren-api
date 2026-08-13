<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $sampleCustomers = [
            [
                'name' => 'Constructora Alarcón S.A. de C.V.',
                'email' => 'compras@alarconconstruye.com',
                'phone' => '5541238901',
                'rfc' => 'CAL180412AA1',
                'address' => 'Av. Insurgentes Sur 1450, Col. Crédito Constructor, Benito Juárez, CDMX',
                'notes' => 'Cliente corporativo frecuente. Renta maquinaria ligera y andamios.',
                'is_active' => true,
            ],
            [
                'name' => 'Eventos Sociales Mar & Sol',
                'email' => 'contacto@eventosmarysol.mx',
                'phone' => '5589012345',
                'rfc' => 'ESM200915B82',
                'address' => 'Calzada de Tlalpan 2890, Col. Espartaco, Coyoacán, CDMX',
                'notes' => 'Organizador de bodas y graduaciones. Solicita siempre flete y montaje.',
                'is_active' => true,
            ],
            [
                'name' => 'Lic. Roberto Mendoza Morales',
                'email' => 'roberto.mendoza@notaria45.mx',
                'phone' => '5578901234',
                'rfc' => 'MEMR820514KP3',
                'address' => 'Paseo de la Reforma 505, Piso 18, Cuauhtémoc, CDMX',
                'notes' => 'Arrendatario de oficina ejecutiva y sala de juntas.',
                'is_active' => true,
            ],
            [
                'name' => 'Transportes y Logística Quetzal',
                'email' => 'flotilla@transquetzal.com',
                'phone' => '5567890123',
                'rfc' => 'TLQ151120CC9',
                'address' => 'Eje Central Lázaro Cárdenas 890, Portales Norte, CDMX',
                'notes' => 'Renta camionetas de carga y remolques para temporadas altas.',
                'is_active' => true,
            ],
            [
                'name' => 'Mariana Torres Delgado',
                'email' => 'mariana.torres@gmail.com',
                'phone' => '5556789012',
                'rfc' => 'TODM900822HG4',
                'address' => 'Calle Sonora 120, Col. Condesa, Cuauhtémoc, CDMX',
                'notes' => 'Cliente particular. Renta mobiliario para eventos familiares.',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            foreach ($sampleCustomers as $customerData) {
                Customer::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'email' => $customerData['email'],
                    ],
                    array_merge($customerData, ['user_id' => $user->id])
                );
            }
        }
    }
}

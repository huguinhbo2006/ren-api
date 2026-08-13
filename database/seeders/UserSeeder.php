<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();
        $proPlan  = Plan::where('slug', 'pro')->first();

        // 1. Super Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@rentame.mx'],
            [
                'name' => 'Administrador Rentame',
                'password' => Hash::make('password123'),
                'phone' => '5512345678',
                'plan_id' => $proPlan?->id,
                'plan_expires_at' => now()->addYears(5),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // 2. Demo User (Plan Free)
        $freeUser = User::updateOrCreate(
            ['email' => 'demo@rentame.mx'],
            [
                'name' => 'Usuario Demo Free',
                'password' => Hash::make('password123'),
                'phone' => '5587654321',
                'plan_id' => $freePlan?->id,
                'plan_expires_at' => null,
                'is_active' => true,
            ]
        );
        $freeUser->assignRole('user');

        // 3. Pro User (Plan Pro)
        $proUser = User::updateOrCreate(
            ['email' => 'pro@rentame.mx'],
            [
                'name' => 'Empresa Rentas Pro',
                'password' => Hash::make('password123'),
                'phone' => '5599887766',
                'plan_id' => $proPlan?->id,
                'plan_expires_at' => now()->addDays(30),
                'is_active' => true,
            ]
        );
        $proUser->assignRole('user');
    }
}

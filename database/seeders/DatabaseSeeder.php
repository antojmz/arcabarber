<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedPaymentMethods();
        $this->seedServices();

        if (app()->isLocal()) {
            $this->seedDemoBarbers();
        }
    }

    private function seedAdmin(): void
    {
        $email = config('barbershop.admin.email');

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        User::create([
            'name' => config('barbershop.admin.name'),
            'email' => $email,
            'password' => config('barbershop.admin.password'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ])->markEmailAsVerified();
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            ['code' => 'cash', 'name' => 'Efectivo', 'atm_fee_rate' => 0],
            ['code' => 'transfer', 'name' => 'Transferencia', 'atm_fee_rate' => 0],
            ['code' => 'debit', 'name' => 'Débito', 'atm_fee_rate' => 1.5],
            ['code' => 'credit', 'name' => 'Crédito', 'atm_fee_rate' => 3],
        ];

        foreach ($methods as $order => $method) {
            PaymentMethod::withTrashed()->firstOrCreate(
                ['code' => $method['code']],
                [...$method, 'sort_order' => $order],
            );
        }
    }

    private function seedServices(): void
    {
        $services = [
            ['name' => 'Corte de cabello', 'base_price' => 14000, 'duration_minutes' => 30],
            ['name' => 'Corte + Barba', 'base_price' => 19000, 'duration_minutes' => 45],
        ];

        foreach ($services as $order => $service) {
            Service::withTrashed()->firstOrCreate(
                ['name' => $service['name']],
                [...$service, 'sort_order' => $order],
            );
        }
    }

    private function seedDemoBarbers(): void
    {
        foreach (['Carlos', 'Matías', 'Javier'] as $name) {
            $email = strtolower(str($name)->ascii()).'@arcabarber.test';

            if (User::withTrashed()->where('email', $email)->exists()) {
                continue;
            }

            User::factory()->barber()->create([
                'name' => $name,
                'email' => $email,
            ]);
        }
    }
}

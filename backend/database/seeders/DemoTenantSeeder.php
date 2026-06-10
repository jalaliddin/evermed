<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['id' => 'demo'],
            [
                'name' => 'Salomatlik Klinikasi',
                'slug' => 'demo',
                'phone' => '+998901234567',
                'address' => 'Toshkent sh., Chilonzor tumani',
                'status' => 'active',
                'plan' => 'basic',
            ]
        );

        // Add subscription
        \App\Models\Subscription::updateOrCreate(
            ['tenant_id' => 'demo'],
            [
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'amount' => 150000,
                'payment_method' => 'cash',
                'status' => 'active',
            ]
        );

        // Run tenant DB seeder
        $tenant->run(function () {
            $this->call(TenantDemoSeeder::class);
        });
    }
}

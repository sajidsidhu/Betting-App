<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::create([
            'title' => 'Purchase Plan',
            'description' => 'Complete access to all features',
            'price' => 5.00,
            'expiry_duration' => 365, // 1 year in days
            'features' => [
                'Ads free',
                'Data history backup',
                'Secure payment'
            ],
        ]);
    }
}

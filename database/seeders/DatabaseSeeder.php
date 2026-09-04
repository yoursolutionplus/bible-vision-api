<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'ad_free'],
            [
                'name' => 'Ad-Free',
                'price' => 9.99,
                'billing_period' => 'monthly',
                'removes_ads' => true,
                'support_sessions' => 0,
                'is_addon' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Support',
                'price' => 19.99,
                'billing_period' => 'monthly',
                'removes_ads' => true,
                'support_sessions' => 2,
                'is_addon' => false,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'extra_sessions'],
            [
                'name' => 'Extra Sessions',
                'price' => 50.00,
                'billing_period' => 'one_time',
                'removes_ads' => false,
                'support_sessions' => 2,
                'is_addon' => true,
                'is_active' => true,
                'sort_order' => 3,
            ]
        );
    }
}

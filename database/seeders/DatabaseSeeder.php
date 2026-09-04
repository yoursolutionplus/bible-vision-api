<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
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

        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if ($adminEmail && $adminPassword) {
            User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => env('ADMIN_NAME', 'Bible Vision Admin'),
                    'password' => $adminPassword,
                ]
            );
        }
    }
}

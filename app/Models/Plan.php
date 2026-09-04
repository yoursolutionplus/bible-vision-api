<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_period',
        'removes_ads',
        'support_sessions',
        'is_addon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'removes_ads' => 'boolean',
            'is_addon' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

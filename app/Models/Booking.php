<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'language',
        'session_date',
        'session_time',
        'note',
        'status',
        'zoom_link',
        'user_id',
        'session_credit_used',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'session_credit_used' => 'boolean',
        ];
    }
}

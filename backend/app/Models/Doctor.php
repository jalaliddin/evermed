<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'user_id', 'specialization', 'room_number',
        'schedule', 'consultation_price', 'bio', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'is_active' => 'boolean',
            'consultation_price' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}

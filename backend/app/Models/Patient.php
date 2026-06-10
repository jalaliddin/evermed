<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name', 'birth_date', 'gender', 'phone',
        'address', 'blood_type', 'allergies', 'notes', 'photo',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'appointment_id',
        'visited_at', 'diagnosis', 'prescription', 'notes',
        'total_amount', 'discount', 'paid_amount',
        'payment_method', 'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'is_paid' => 'boolean',
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function services()
    {
        return $this->hasMany(VisitService::class);
    }

    public function inventory()
    {
        return $this->hasMany(VisitInventory::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}

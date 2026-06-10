<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = ['visit_id', 'receipt_number', 'amount', 'printed_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'printed_at' => 'datetime',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }
}

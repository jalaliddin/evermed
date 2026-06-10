<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitService extends Model
{
    protected $fillable = ['visit_id', 'service_id', 'quantity', 'price', 'total'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}

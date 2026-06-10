<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'name', 'category', 'unit', 'quantity',
        'min_quantity', 'price_per_unit', 'supplier', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'price_per_unit' => 'decimal:2',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }

    public function getStatusAttribute(): string
    {
        if ($this->quantity <= $this->min_quantity) {
            return 'critical';
        }
        if ($this->quantity <= $this->min_quantity * 1.5) {
            return 'low';
        }
        return 'ok';
    }
}

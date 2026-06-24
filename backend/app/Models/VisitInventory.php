<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitInventory extends Model
{
    protected $table = 'visit_inventory';
    protected $fillable = ['visit_id', 'item_id', 'quantity_used', 'unit_price', 'total_price', 'notes'];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:2',
            'unit_price'    => 'decimal:2',
            'total_price'   => 'decimal:2',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}

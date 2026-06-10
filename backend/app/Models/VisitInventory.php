<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitInventory extends Model
{
    protected $table = 'visit_inventory';
    protected $fillable = ['visit_id', 'item_id', 'quantity_used', 'notes'];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}

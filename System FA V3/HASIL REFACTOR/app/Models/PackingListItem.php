<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListItem extends Model
{
    use HasFactory;

    protected $table = 'packing_list_items';

    protected $fillable = [
        'delivery_order_id',
        'box_id',
        'slot_number',
        'serial_number',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function box()
    {
        return $this->belongsTo(PackingListBox::class, 'box_id');
    }
}

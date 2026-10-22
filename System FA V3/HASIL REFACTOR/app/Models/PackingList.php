<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingList extends Model
{
    use HasFactory;

    protected $table = 'packing_lists';

    protected $fillable = [
        'delivery_order_id',
        'crate_number',
        'crate_id',
        'pl_number',
        'total_quantity',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function boxes()
    {
        return $this->hasMany(PackingListBox::class, 'packing_list_id');
    }
}

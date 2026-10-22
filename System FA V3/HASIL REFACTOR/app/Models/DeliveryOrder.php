<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'do_number',
        'pl_number',
        'do_date',
        'customer_name',
        'customer_address',
        'customer_phone',
        'customer_fax',
        'package_type',
        'total_crates',
        'total_boxes',
        'total_units',
        'status',
        'created_by',
        'created_by_name',
        'notes',
        'cloud_pdf_url',
        'cloud_public_id',
        'cloud_uploaded_at',
    ];

    protected $casts = [
        'do_date' => 'date',
        'cloud_uploaded_at' => 'datetime',
    ];

    public function packingLists()
    {
        return $this->hasMany(PackingList::class, 'delivery_order_id');
    }

    public function items()
    {
        return $this->hasMany(PackingListItem::class, 'delivery_order_id');
    }
}

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
        'is_backdate',
        'sj_mode',
        'contract_number',
        'program_name',
        'customer_name',
        'customer_address',
        'customer_phone',
        'customer_fax',
        'product_name',
        'package_type',
        'batch_number',
        'total_crates',
        'total_boxes',
        'total_units',
        'status',
        'created_by',
        'created_by_name',
        'created_by_title',
        'created_by_department',
        'notes',
        'cloud_pdf_url',
        'cloud_public_id',
        'cloud_uploaded_at',
    ];

    protected $casts = [
        'do_date' => 'date',
        'is_backdate' => 'boolean',
        'cloud_uploaded_at' => 'datetime',
    ];

    protected $appends = ['total_quantity'];

    public function getTotalQuantityAttribute()
    {
        if (!empty($this->attributes['total_units']) && (int)$this->attributes['total_units'] > 0) {
            return (int)$this->attributes['total_units'];
        }
        if ($this->relationLoaded('items')) {
            $count = $this->items->count();
            if ($count > 0) return $count;
        }
        if ($this->relationLoaded('packingLists')) {
            $count = 0;
            foreach ($this->packingLists as $pl) {
                if ($pl->relationLoaded('boxes')) {
                    foreach ($pl->boxes as $box) {
                        if ($box->relationLoaded('items')) {
                            $count += $box->items->count();
                        }
                    }
                }
            }
            if ($count > 0) return $count;
        }
        return (int) \App\Models\PackingListItem::where('delivery_order_id', $this->id)->count();
    }

    public function packingLists()
    {
        return $this->hasMany(PackingList::class, 'delivery_order_id');
    }

    public function items()
    {
        return $this->hasMany(PackingListItem::class, 'delivery_order_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListBox extends Model
{
    use HasFactory;

    protected $table = 'packing_list_boxes';

    protected $fillable = [
        'packing_list_id',
        'box_number',
        'box_id',
        'total_quantity',
    ];

    public function packingList()
    {
        return $this->belongsTo(PackingList::class, 'packing_list_id');
    }

    public function items()
    {
        return $this->hasMany(PackingListItem::class, 'box_id');
    }
}

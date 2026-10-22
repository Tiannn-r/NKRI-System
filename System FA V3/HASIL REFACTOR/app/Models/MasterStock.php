<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterStock extends Model
{
    use HasFactory;

    protected $table = 'master_stocks';

    protected $fillable = [
        'sn',
        'cs',
        'part_type',
        'status',
        'allocated_tray_id',
        'allocated_at',
        'used_at',
        'last_reason',
        'last_tray_id',
        'last_operator',
        'is_replaced_from_assy',
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'used_at' => 'datetime',
        'is_replaced_from_assy' => 'boolean',
    ];

    public function tray()
    {
        return $this->belongsTo(Tray::class, 'allocated_tray_id');
    }

    public function lastTray()
    {
        return $this->belongsTo(Tray::class, 'last_tray_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'Tersedia')->whereNull('allocated_tray_id');
    }

    public function scopeByType($query, $type)
    {
        if ($type) {
            return $query->where('part_type', $type);
        }
        return $query;
    }
}

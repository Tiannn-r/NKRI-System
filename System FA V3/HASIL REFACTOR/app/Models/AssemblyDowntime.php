<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyDowntime extends Model
{
    use HasFactory;

    protected $table = 'assembly_downtimes';

    protected $fillable = [
        'tray_id',
        'tray_code',
        'set_number',
        'operator',
        'reason',
        'paused_at',
        'resumed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function tray()
    {
        return $this->belongsTo(Tray::class, 'tray_id');
    }
}

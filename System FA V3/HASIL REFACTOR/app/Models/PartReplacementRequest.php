<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartReplacementRequest extends Model
{
    use HasFactory;

    protected $table = 'part_replacement_requests';

    protected $fillable = [
        'assembly_id',
        'tray_id',
        'set_number',
        'replacements_json',
        'requestor_username',
        'status',
        'admin_username',
        'verified_at',
        'admin_notes',
    ];

    protected $casts = [
        'replacements_json' => 'array',
        'verified_at' => 'datetime',
    ];

    public function assembly()
    {
        return $this->belongsTo(Assembly::class, 'assembly_id');
    }

    public function tray()
    {
        return $this->belongsTo(Tray::class, 'tray_id');
    }
}

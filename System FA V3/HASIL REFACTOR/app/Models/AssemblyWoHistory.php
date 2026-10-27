<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssemblyWoHistory extends Model
{
    use HasFactory;

    protected $table = 'assembly_wo_history';

    protected $fillable = [
        'tray_id',
        'tray_code',
        'set_number',
        'pic_assembly',
        'part15_sn',
        'part12_sn',
        'part1_sn',
        'status',
        'reason',
        'defective_parts',
        'started_at',
        'finished_at',
        'source',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function setDefectivePartsAttribute($value)
    {
        if (is_array($value)) {
            $filtered = array_filter(array_map('trim', $value));
            $this->attributes['defective_parts'] = !empty($filtered) ? implode(',', $filtered) : null;
        } else {
            $this->attributes['defective_parts'] = $value ?: null;
        }
    }
}

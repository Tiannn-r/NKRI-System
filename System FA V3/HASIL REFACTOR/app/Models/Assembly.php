<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assembly extends Model
{
    use HasFactory;

    protected $table = 'assemblies';

    protected $fillable = [
        'tray_id',
        'set_number',
        'pic_assembly',
        'part15_sn',
        'part12_sn',
        'part1_sn',
        'status',
        'original_assembly_status',
        'reason',
        'rework_reason',
        'defective_parts',
        'test_checklist',
        'notes',
        'assy_tester_username',
        'assy_tested_at',
        'assy_doc_number',
        'qc_status',
        'qc_failure_reasons',
        'qc_inspector_username',
        'qc_tested_at',
        'qc_doc_number',
        'verified_status',
        'is_declined',
        'declined_reason',
        'is_reregistered',
        'rereg_pending_operator',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'test_checklist' => 'array',
        'is_declined' => 'boolean',
        'is_reregistered' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'assy_tested_at' => 'datetime',
        'qc_tested_at' => 'datetime',
    ];

    public function tray()
    {
        return $this->belongsTo(Tray::class, 'tray_id');
    }

    public function setDefectivePartsAttribute($value)
    {
        if (is_array($value)) {
            $filtered = array_filter(array_map('trim', $value));
            $this->attributes['defective_parts'] = !empty($filtered) ? implode(',', $filtered) : null;
        } else {
            $this->attributes['defective_parts'] = $value ?: null;
        }
    }

    public function replacements()
    {
        return $this->hasMany(PartReplacementRequest::class, 'assembly_id');
    }
}

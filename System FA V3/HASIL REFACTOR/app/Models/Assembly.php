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
        'lifecycle_path',
        'reason',
        'rework_reason',
        'defective_parts',
        'assy_test_status',
        'assy_test_checklist',
        'assy_failure_reasons',
        'notes',
        'assy_tester_username',
        'assy_tested_at',
        'assy_doc_number',
        'qc_status',
        'qc_failure_reasons',
        'qc_notes',
        'qc_inspector_username',
        'qc_tested_by',
        'qc_tested_at',
        'qc_doc_number',
        'warehouse_receipt_status',
        'warehouse_receipt_at',
        'warehouse_receipt_by',
        'warehouse_doc_number',
        'verified_status',
        'is_declined',
        'declined_reason',
        'is_reregistered',
        'rereg_pending_operator',
        'rereg_wo_suffix',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_declined' => 'boolean',
        'is_reregistered' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'assy_tested_at' => 'datetime',
        'qc_tested_at' => 'datetime',
        'warehouse_receipt_at' => 'datetime',
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

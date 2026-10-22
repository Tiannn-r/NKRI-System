<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssemblyTest extends Model
{
    protected $table = 'assembly_tests';

    protected $fillable = [
        'stage',
        'doc_number',
        'tray_id',
        'tray_code',
        'set_number',
        'assembly_id',
        'p9_sn',
        'part15_sn',
        'part12_sn',
        'part1_sn',
        'tester_username',
        'tester_role',
        'test_checklist',
        'status',
        'failure_reasons',
        'notes',
        'admin_approved_at',
        'admin_approved_by',
        'admin_action',
        'status_history',
        'lifecycle_path',
    ];

    protected $casts = [
        'test_checklist' => 'array',
        'admin_approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

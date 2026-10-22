<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartAuditLog extends Model
{
    use HasFactory;

    protected $table = 'part_audit_logs';

    protected $fillable = [
        'part_type',
        'sn',
        'old_status',
        'new_status',
        'action_type',
        'actor_username',
        'actor_role',
        'reason',
    ];
}

class ReRegisterRequest extends Model
{
    use HasFactory;

    protected $table = 're_register_requests';

    protected $fillable = [
        'part_type',
        'sn',
        'current_status',
        'target_status',
        'requestor_username',
        'requestor_role',
        'reason',
        'status',
        'admin_username',
        'admin_notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];
}

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
}

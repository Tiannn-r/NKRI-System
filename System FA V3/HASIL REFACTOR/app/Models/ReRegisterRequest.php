<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

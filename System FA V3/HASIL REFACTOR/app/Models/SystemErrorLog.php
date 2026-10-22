<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    use HasFactory;

    protected $table = 'system_error_logs';

    protected $fillable = [
        'level',
        'message',
        'file',
        'line',
        'trace',
        'endpoint',
        'method',
        'username',
        'ip_address',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlowQueryLog extends Model
{
    use HasFactory;

    protected $table = 'slow_query_logs';

    protected $fillable = [
        'sql_query',
        'bindings',
        'duration_ms',
        'endpoint',
        'method',
        'username',
        'ip_address',
    ];
}

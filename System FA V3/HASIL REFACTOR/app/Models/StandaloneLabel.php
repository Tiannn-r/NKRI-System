<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StandaloneLabel extends Model
{
    use HasFactory;

    protected $table = 'standalone_packing_labels';

    protected $fillable = [
        'pl_number',
        'label_date',
        'package_type',
        'crates_json',
        'total_quantity',
        'created_by',
    ];

    protected $casts = [
        'label_date' => 'date',
        'crates_json' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestingPoint extends Model
{
    use HasFactory;

    protected $table = 'testing_points';

    protected $fillable = [
        'test_num',
        'category',
        'instruction',
        'output',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];
}

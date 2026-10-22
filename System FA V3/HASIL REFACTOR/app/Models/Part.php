<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $table = 'parts';

    protected $fillable = [
        'tray_id',
        'part_type',
        'sn',
        'status',
    ];

    public function tray()
    {
        return $this->belongsTo(Tray::class, 'tray_id');
    }
}

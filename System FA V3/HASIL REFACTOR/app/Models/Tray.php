<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tray extends Model
{
    use HasFactory;

    protected $table = 'trays';

    protected $fillable = [
        'tray_code',
        'pic_warehouse',
        'assigned_operator',
        'source_document_mo',
        'product_type',
        'status',
        'session_status',
        'verified_by_gudang',
        'verified_at',
        'verified_by_user',
        'return_verified',
        'return_verified_at',
    ];

    protected $casts = [
        'verified_by_gudang' => 'boolean',
        'return_verified' => 'boolean',
        'verified_at' => 'datetime',
        'return_verified_at' => 'datetime',
    ];

    public function parts()
    {
        return $this->hasMany(Part::class, 'tray_id');
    }

    public function assemblies()
    {
        return $this->hasMany(Assembly::class, 'tray_id');
    }

    public function downtimes()
    {
        return $this->hasMany(AssemblyDowntime::class, 'tray_id');
    }
}

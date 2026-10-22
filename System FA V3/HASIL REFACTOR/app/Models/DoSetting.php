<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoSetting extends Model
{
    use HasFactory;

    protected $table = 'do_settings';

    protected $fillable = [
        'company_name',
        'company_logo',
        'head_office',
        'factory_address',
        'contact_email',
        'default_customer_name',
        'default_customer_address',
        'default_customer_phone',
        'default_customer_fax',
        'default_contract_number',
        'default_program_name',
        'default_product_name',
        'default_issuer_name',
        'default_issuer_title',
        'default_issuer_department',
        'do_prefix',
        'pl_prefix',
        'crate_prefix',
        'crate_capacity',
        'box_capacity',
        'do_sequence_offset',
        'do_offset_month',
        'cloudinary_cloud_name',
        'cloudinary_api_key',
        'cloudinary_api_secret',
    ];
}

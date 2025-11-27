<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Merchant extends Model
{
    use HasFactory;

    protected $table = 'merchants';

    protected $fillable = [
        'external_id',
        'state',
        'rzsocial',
        'nit',
        'affiliation',
        'is_vip',
        'fcrenov',
        'email',
        'name',
        'raw_data',
        'is_active',
        'is_ally',
        'discount_common',
        'discount_vip',
        'discount_value',
    ];

    // Casts para que Eloquent convierta bien tipos
    protected $casts = [
        'is_vip'       => 'boolean',
        'is_active'    => 'boolean',
        'is_ally'      => 'boolean',
        'raw_data'     => 'array',
        'fcrenov'      => 'date',
        'discount_common' => 'integer',
        'discount_vip'     => 'integer',
        'discount_value'   => 'integer',
    ];

    public function qrCode()
    {
        return $this->hasOne(QrCode::class, 'merchant_external_id', 'external_id');
    }
}

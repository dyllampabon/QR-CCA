<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanMetric extends Model
{
    protected $fillable = [
        'merchant_external_id',
        'buyer_external_id',
        'ip',
        'user_agent',
        'device',
        'referer',
        'purchase_amount',
        'discount_percent',
        'discount_value',
        'extra',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_external_id', 'external_id');
    }
}
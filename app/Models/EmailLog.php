<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_external_id',
        'qr_code_id',
        'to_email',
        'subject',
        'response',
        'message_id',
        'success',
        'error_message',
    ];

    /**
     * Relaciones
     */
    // Antes: belongsTo(Merchant::class) ← esto buscaba merchant_id
    public function merchant()
    {
        // Relacionar usando merchant_external_id -> merchants.external_id
        return $this->belongsTo(Merchant::class, 'merchant_external_id', 'external_id');
    }

    public function qrCode()
    {
        return $this->belongsTo(QrCode::class);
    }
}

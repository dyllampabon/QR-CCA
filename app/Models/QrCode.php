<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;

class QrCode extends Model
{
    protected $fillable = [
        'merchant_external_id',
        'filename',
        'token_hash',
        'token_expires_at',
        'sent_at',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_external_id', 'external_id');
    }

    /**
     * Genera un QR para un merchant y lo guarda en DB + storage
     */
    public static function generateForMerchant(Merchant $merchant): self
    {
        // 1. Generar token UUID y su hash
        $token = (string) Str::uuid();
        $tokenHash = hash('sha256', $token);

        // 2. Generar la URL de validación (pública) para el QR (fuerza URL absoluta)
        $qrContent = route('qr.validate', ['token' => $token], true);

        // 3. Definir nombre de archivo y ruta
        $fileName = 'qrcodes/' . $merchant->id . '_' . time() . '.png';

        // 4. Generar imagen QR en PNG con la URL de validación
        $qrImage = Builder::create()
            ->data($qrContent)
            ->size(300)
            ->margin(10)
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->build()
            ->getString();

        // 5. Guardar imagen QR en storage
        Storage::disk('public')->put($fileName, $qrImage);

        // 6. Guardar hash y referencia en la base de datos
        return self::updateOrCreate(
            ['merchant_external_id' => $merchant->external_id],
            [
                'token_hash' => $tokenHash,
                'filename' => $fileName,
                'token_expires_at' => now()->addYear(),
                'sent_at' => null,
            ]
        );
    }
}
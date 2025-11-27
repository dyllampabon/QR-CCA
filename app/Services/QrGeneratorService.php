<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Exception;

/**
 * Servicio centralizado para generación y persistencia de QRs.
 *
 * - Genera token UUID y su hash (se guarda solo el hash en BD).
 * - Genera la imagen PNG del QR con la URL absoluta.
 * - Guarda el archivo en storage (disk 'public') y actualiza/crea la fila en qr_codes.
 *
 * Uso:
 *  $result = $service->createAndSave($merchant);
 *  -> devuelve ['qr' => QrCodeModel, 'token' => $token]
 *
 * NOTA: El token se devuelve temporalmente por si se desea incluirlo en un email,
 *       pero nunca se almacena el token sin hash en la base de datos.
 */
class QrGeneratorService
{
    /**
     * Genera, guarda la imagen y crea/actualiza el modelo QrCode para el merchant.
     *
     * @param Merchant $merchant
     * @param string|null $logoPath Opcional: ruta absoluta al logo para el QR
     * @param bool $markSent Si true, marcará sent_at = now()
     * @return array ['qr' => QrCode, 'token' => string]
     * @throws Exception
     */
    public function createAndSave(Merchant $merchant, ?string $logoPath = null, bool $markSent = false): array
    {
        // 1) Generar token (UUID v4) y su hash seguro
        $token = (string) Str::uuid();
        $tokenHash = hash('sha256', $token);

        // 2) Generar la URL absoluta que contendrá el QR
        //    Si la app está en relative env sin URL, route() fallará; se asume APP_URL configurado.
        $qrContent = route('qr.validate', ['token' => $token], true);

        // 3) Nombre de archivo único
        $fileName = 'qrcodes/' . $merchant->id . '_' . time() . '.png';

        // 4) Color del QR según tipo (VIP o no)
        $color = $merchant->is_vip ? new Color(255, 0, 0) : new Color(0, 0, 0);

        // 5) Construir QR con Endroid
        try {
            $builder = Builder::create()
                ->writer(new PngWriter())
                ->data($qrContent)
                ->size(300)
                ->margin(10)
                ->foregroundColor($color);

            // Si se provee logoPath y existe, agregar logo (opcional)
            if ($logoPath && file_exists($logoPath)) {
                // El Logo de Endroid requiere un objeto Logo, pero para simplificar lo omitimos
                // o usar: ->logoPath($logoPath) con la versión correspondiente
            }

            $result = $builder->build();
            $pngString = $result->getString();

            // 6) Guardar en storage (disk 'public')
            Storage::disk('public')->put($fileName, $pngString);

            // 7) Persistir registro en tabla qr_codes (solo el hash del token)
            $qr = QrCode::updateOrCreate(
                ['merchant_external_id' => $merchant->external_id],
                [
                    'token_hash' => $tokenHash,
                    'filename' => $fileName,
                    'token_expires_at' => now()->addYear(),
                    'sent_at' => $markSent ? now() : null,
                    'is_active' => true,
                ]
            );

            return ['qr' => $qr, 'token' => $token];
        } catch (Exception $e) {
            // Re-lanzar para que el caller (job/command) lo registre y aplique lógica de reintento
            throw $e;
        }
    }
}
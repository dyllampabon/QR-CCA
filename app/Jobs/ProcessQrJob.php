<?php

namespace App\Jobs;

use App\Models\Merchant;
use App\Models\QrCode;
use App\Models\EmailLog;
use App\Mail\MerchantQrMail;
use App\Services\QrGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * ProcessQrJob
 *
 * Job versátil que:
 *  - genera QR para el merchant (si no existe o si forzar)
 *  - opcionalmente envía el email con el QR
 *
 * Se parametriza con flags generate/send para reutilizar en un único job.
 */
class ProcessQrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $merchantId;
    public bool $generate;
    public bool $send;
    public bool $forceGenerate;

    // Opciones de reintento/backoff
    public int $tries = 3;
    public int $backoff = 60; // segundos entre reintentos

    /**
     * @param int $merchantId
     * @param bool $generate Generar QR (true por defecto)
     * @param bool $send Enviar email luego de generar (false por defecto)
     * @param bool $forceGenerate Forzar regenerar token y archivo incluso si ya existe QR
     */
    public function __construct(int $merchantId, bool $generate = true, bool $send = false, bool $forceGenerate = false)
    {
        $this->merchantId = $merchantId;
        $this->generate = $generate;
        $this->send = $send;
        $this->forceGenerate = $forceGenerate;
    }

    public function handle(QrGeneratorService $qrService): void
    {
        $merchant = Merchant::find($this->merchantId);
        if (!$merchant) {
            \Log::warning("ProcessQrJob: Merchant ID {$this->merchantId} no encontrado. Abortando.");
            return;
        }

        // Si no se quiere generar y sólo enviar, validar la existencia del QR
        $qr = $merchant->qrCode;

        try {
            if ($this->generate) {
                // Evitar regenerar si ya existe y no se forcé
                if ($qr && !$this->forceGenerate) {
                    // Actualizamos el modelo en memoria
                    $qr = $merchant->qrCode;
                } else {
                    $result = $qrService->createAndSave($merchant, public_path('img/logo.png'), false);
                    $qr = $result['qr'];
                    // token devuelto se puede usar si deseas construir enlaces personalizados
                }
            }

            if ($this->send) {
                // Verificar que exista QR y que merchant tenga email
                if (!$qr) {
                    \Log::warning("ProcessQrJob: Merchant {$merchant->id} no tiene QR; email no enviado.");
                    return;
                }
                if (empty($merchant->email)) {
                    \Log::warning("ProcessQrJob: Merchant {$merchant->id} sin email; omitido.");
                    return;
                }

                // Enviar correo
                Mail::to($merchant->email)->send(new MerchantQrMail($merchant, $qr));

                // Registrar log de email
                EmailLog::create([
                    'merchant_external_id' => $merchant->external_id,
                    'qr_code_id' => $qr->id,
                    'to_email' => $merchant->email,
                    'subject' => 'Tu Código QR de la Cámara de Comercio',
                    'response' => 'Correo enviado correctamente',
                    'success' => true,
                ]);

                // Marcar QR como enviado
                $qr->update(['sent_at' => now()]);
            }
        } catch (Throwable $e) {
            \Log::error("ProcessQrJob error para merchant {$merchant->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Registrar fallo en EmailLog si corresponde al envío
            if ($this->send) {
                EmailLog::create([
                    'merchant_external_id' => $merchant->external_id,
                    'qr_code_id' => $qr->id ?? null,
                    'to_email' => $merchant->email ?? null,
                    'subject' => 'Tu Código QR de la Cámara de Comercio',
                    'response' => null,
                    'error_message' => $e->getMessage(),
                    'success' => false,
                ]);
            }

            // Re-lanzar para que el job sea reintentado según $tries/$backoff
            throw $e;
        }
    }
}
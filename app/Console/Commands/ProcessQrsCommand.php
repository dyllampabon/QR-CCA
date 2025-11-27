<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Merchant;
use App\Models\QrCode;
use App\Jobs\ProcessQrJob;
use App\Services\QrGeneratorService;
use App\Mail\MerchantQrMail;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Comando unificado para procesar QRs:
 * - action=generate : generar QRs para merchants activos sin QR
 * - action=send     : enviar emails para QRs existentes con sent_at NULL y merchant activo
 * - action=both     : generar y enviar (para nuevos merchants)
 *
 * Opciones:
 * --chunk=N       : tamaño de chunk (por defecto 100)
 * --sync          : ejecutar la operación de forma síncrona en este proceso (no despachar jobs)
 * --force         : forzar regeneración incluso si ya existe QR (sólo válido para action=generate/both)
 *
 * Uso:
 * php artisan qr:process --action=generate --chunk=100
 * php artisan qr:process --action=send --chunk=100
 * php artisan qr:process --action=both --chunk=100 --sync --force
 */
class ProcessQrsCommand extends Command
{
    protected $signature = 'qr:process {--action=generate : generate|send|both} {--chunk=100} {--sync : Ejecutar sin queue (síncrono)} {--force : Forzar regeneración}';
    protected $description = 'Procesa QRs (generar y/o enviar) de forma unificada';

    public function handle(QrGeneratorService $qrService)
    {
        $action = $this->option('action') ?: 'generate';
        $chunk = (int) $this->option('chunk');
        $sync = (bool) $this->option('sync');
        $force = (bool) $this->option('force');

        $this->info("Iniciando qr:process action={$action} chunk={$chunk} sync=" . ($sync ? 'yes' : 'no') . " force=" . ($force ? 'yes' : 'no'));

        if (in_array($action, ['generate', 'both'])) {
            // Query: merchants activos (NO requerir email)
            $query = Merchant::where('is_active', true);

            if (!$force) {
                // solamente aquellos sin QR existente
                $query->whereDoesntHave('qrCode');
            }

            $query->orderBy('id')->chunk($chunk, function ($merchants) use ($sync, $force, $qrService) {
                foreach ($merchants as $merchant) {
                    if ($sync) {
                        // Generación SÍNCRONA (útil para debugging)
                        try {
                            $result = $qrService->createAndSave($merchant, public_path('img/logo.png'), false);
                            $this->info("Generado QR (sync) merchant ID {$merchant->id} -> QR ID {$result['qr']->id}");
                        } catch (\Throwable $e) {
                            $this->error("Error generando QR (sync) para merchant {$merchant->id}: " . $e->getMessage());
                            Log::error("qr:process sync generate error", ['merchant_id' => $merchant->id, 'exception' => $e]);
                        }
                    } else {
                        // Despachar job (asíncrono)
                        dispatch(new ProcessQrJob($merchant->id, true, false, $force));
                        $this->info("Despachado ProcessQrJob generate para merchant {$merchant->id}");
                    }
                }
            });
        }

        if (in_array($action, ['send', 'both'])) {
            // QrCodes generados HOY y no enviados (sent_at NULL) y activos
            $qrcodesQuery = QrCode::whereDate('created_at', now())
                ->whereNull('sent_at')
                ->where('is_active', true)
                ->with('merchant')
                ->orderBy('id');

            $qrcodesQuery->chunk($chunk, function ($qrcodes) use ($sync) {
                foreach ($qrcodes as $qr) {
                    $merchant = $qr->merchant;
                    if (!$merchant) {
                        Log::warning("qr:process send: QR {$qr->id} sin merchant asociado. Omitido.");
                        continue;
                    }
                    if (empty($merchant->email)) {
                        Log::warning("qr:process send: Merchant {$merchant->id} sin email. Omitido.");
                        continue;
                    }

                    if ($sync) {
                        // Envío SÍNCRONO (no recomienda en producción para muchos correos)
                        try {
                            Mail::to($merchant->email)->send(new MerchantQrMail($merchant, $qr));

                            EmailLog::create([
                                'merchant_external_id' => $merchant->external_id,
                                'qr_code_id' => $qr->id,
                                'to_email' => $merchant->email,
                                'subject' => 'QR generado de la Cámara de Comercio de Aguachica',
                                'response' => 'Correo enviado correctamente',
                                'success' => true,
                            ]);

                            $qr->update(['sent_at' => now()]);

                            $this->info("Envío (sync) realizado a merchant {$merchant->id} (QR {$qr->id})");
                        } catch (\Throwable $e) {
                            Log::error("qr:process sync send error", ['merchant_id' => $merchant->id, 'qr_id' => $qr->id, 'exception' => $e]);
                            $this->error("Error enviando email (sync) a merchant {$merchant->id}: " . $e->getMessage());
                            EmailLog::create([
                                'merchant_external_id' => $merchant->external_id,
                                'qr_code_id' => $qr->id,
                                'to_email' => $merchant->email,
                                'subject' => 'QR generado de la Cámara de Comercio de Aguachica',
                                'response' => null,
                                'error_message' => $e->getMessage(),
                                'success' => false,
                            ]);
                        }
                    } else {
                        // Despachar job de solo envío: generate=false, send=true
                        dispatch(new ProcessQrJob($merchant->id, false, true, false));
                        $this->info("Despachado ProcessQrJob send para merchant {$merchant->id} (QR {$qr->id})");
                    }
                }
            });
        }

        $this->info("qr:process finalizado para action={$action}");
        return 0;
    }
}
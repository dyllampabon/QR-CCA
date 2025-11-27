<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Merchant;
use League\Csv\Reader;
use League\Csv\Statement;
use Carbon\Carbon;
use App\Jobs\ProcessQrJob;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

/**
 * SyncMerchantsCommand
 *
 * Comando unificado para sincronización de comerciantes:
 *  - modo incremental (por defecto): sólo CREA comerciantes nuevos y genera sus QRs
 *  - modo full (--full): realiza updateOrCreate y desactiva los que no estén en el CSV (sincronización completa)
 *
 * Uso:
 *  php artisan merchants:sync           # incremental (sólo nuevos)
 *  php artisan merchants:sync --full    # sincronización completa (update/create + desactivación)
 */
class SyncMerchantsCommand extends Command
{
    protected $signature = 'merchants:sync {--full : Ejecutar sincronización completa (update/create + desactivar ausentes)}';
    protected $description = 'Sincroniza comerciantes desde API (incremental por defecto; --full para full sync)';

    public function handle()
    {
        $isFull = $this->option('full');
        $modeName = $isFull ? 'FULL' : 'INCREMENTAL';
        $this->info("Iniciando sincronización ({$modeName})...");

        $baseUrl = env('API_BASE', 'https://siiaguachica.confecamaras.co/librerias/wsRestSII/v1');

        // Solicitar token
        $this->info("Solicitando token...");
        try {
            $tokenResponse = Http::post("$baseUrl/solicitarToken", [
                'codigoempresa' => env('CSV_API_CODE', '53'),
                'usuariows'     => env('CSV_API_USER', 'qr'),
                'clavews'       => env('CSV_API_PASS', 'Camara53*'),
            ]);
        } catch (Throwable $e) {
            $this->error("Error solicitando token: " . $e->getMessage());
            \Log::error('SyncMerchantsCommand: token exception', ['exception' => $e]);
            return 1;
        }

        if (!$tokenResponse->successful()) {
            $this->error("Error solicitando token: HTTP {$tokenResponse->status()}");
            \Log::error('SyncMerchantsCommand: token error: ' . $tokenResponse->body());
            return 1;
        }

        $token = $tokenResponse->json('token') ?? null;
        if (!$token) {
            $this->error("Token inválido devuelto por la API.");
            \Log::error('SyncMerchantsCommand: token missing', ['response' => $tokenResponse->body()]);
            return 1;
        }

        // Solicitar CSV
        $this->info("Solicitando CSV de matriculados...");
        $payload = [
            'codigoempresa'          => env('CSV_API_CODE', '53'),
            'usuariows'              => env('CSV_API_USER', 'qr'),
            'token'                  => $token,
            'idusuario'              => env('CSV_API_USER', 'qr'),
            'fechamatriculainicial'  => '',
            'fechamatriculafinal'    => '',
            'fecharenovacioninicial' => now()->startOfYear()->format('Ymd'),
            'fecharenovacionfinal'   => now()->format('Ymd'),
            'fechacancelacioninicial'=> '',
            'fechacancelaciofinal'   => '',
            'filtroexpedientes'      => 'TODOS',
            'filtrociius'            => '',
            'filtromunicipios'       => '',
        ];

        // Timeout configurado en config/services.confecamaras.timeout (milisegundos en .env por compat)
        $timeoutMs = config('services.confecamaras.timeout', 50000);
        // Http::timeout espera segundos (float)
        $timeoutSeconds = max(10, (int) round($timeoutMs / 1000));

        try {
            $exportResponse = Http::timeout($timeoutSeconds)->post("$baseUrl/exportarMatriculados", $payload);
        } catch (Throwable $e) {
            $this->error("Error contactando exportarMatriculados: " . $e->getMessage());
            \Log::error('SyncMerchantsCommand: export exception', ['exception' => $e]);
            return 1;
        }

        if (!$exportResponse->successful()) {
            $this->error("Error exportando matriculados: HTTP {$exportResponse->status()}");
            \Log::error('SyncMerchantsCommand: export error: ' . $exportResponse->body());
            return 1;
        }

        $exportData = $exportResponse->json();
        $csvUrl = $exportData['archivo'] ?? null;
        if (!$csvUrl) {
            $this->error("La API no devolvió la URL del CSV.");
            \Log::error('SyncMerchantsCommand: export missing archivo', ['response' => $exportData]);
            return 1;
        }

        // Descargar CSV con retries + backoff
        $this->info("CSV disponible en: {$csvUrl}");
        $attempts = 3;
        $backoffSeconds = 1;
        $csvString = null;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $this->info("Descargando CSV (intento {$i}/{$attempts})...");
                // Reintentos internos para errores breves, retry acepta ($times, $sleepMilliseconds, $when)
                $csvDownload = Http::retry(2, 1000, function ($exception, $request) {
                    return $exception instanceof ConnectionException;
                })->timeout($timeoutSeconds)->get($csvUrl);

                if ($csvDownload->successful()) {
                    $csvString = $csvDownload->body();
                    break;
                }

                \Log::warning('SyncMerchantsCommand: descarga CSV HTTP no exitosa', [
                    'status' => $csvDownload->status(),
                    'url' => $csvUrl,
                ]);
            } catch (ConnectionException $e) {
                \Log::warning('SyncMerchantsCommand: ConnectionException descargando CSV', [
                    'message' => $e->getMessage(),
                    'attempt' => $i,
                    'url' => $csvUrl
                ]);
            } catch (Throwable $e) {
                \Log::error('SyncMerchantsCommand: excepción al descargar CSV', [
                    'message' => $e->getMessage(),
                    'attempt' => $i,
                    'url' => $csvUrl
                ]);
            }

            sleep($backoffSeconds);
            $backoffSeconds *= 2;
        }

        if (empty($csvString)) {
            $this->error("No se pudo descargar el CSV después de varios intentos. Revisa conectividad o aumenta el timeout.");
            \Log::error('SyncMerchantsCommand: fallo definitivo descarga CSV', ['url' => $csvUrl]);
            return 1;
        }

        // Guardar copia
        $fileName = 'imports/sync_comerciantes_' . now()->format('Ymd_His') . '.csv';
        Storage::put($fileName, $csvString);
        $this->info("CSV guardado en storage: {$fileName}");

        // Procesar CSV (normalizar headers)
        $csv = Reader::createFromString($csvString);
        $csv->setDelimiter(';');

        $rawHeaders = $csv->fetchOne(0);
        if (!$rawHeaders || !is_array($rawHeaders)) {
            $this->error("No se pudieron leer encabezados del CSV.");
            return 1;
        }

        $normalizedHeaders = [];
        $seen = [];
        foreach ($rawHeaders as $h) {
            $hTrim = trim((string)$h);
            $norm = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $hTrim));
            $norm = trim($norm, '_');

            if (isset($seen[$norm])) {
                $seen[$norm]++;
                $norm = $norm . '_' . $seen[$norm];
            } else {
                $seen[$norm] = 1;
            }
            $normalizedHeaders[] = $norm;
        }

        $stmt = (new Statement())->offset(1);
        $records = $stmt->process($csv, $normalizedHeaders);

        // Mapear campos (compat con CSV original)
        $find = function(array $finalHeaders, string $target) {
            $t = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', $target));
            $t = trim($t, '_');
            foreach ($finalHeaders as $fh) {
                if ($fh === $t) return $fh;
                if (str_starts_with($fh, $t . '_')) return $fh;
            }
            return null;
        };

        $finalHeaders = $normalizedHeaders;
        $map = [
            'MATRICULA'      => $find($finalHeaders, 'MATRICULA'),
            'EST_MATRICULA'  => $find($finalHeaders, 'EST-MATRICULA'),
            'FEC_RENOVACION' => $find($finalHeaders, 'FEC-RENOVACION'),
            'ULT_ANO_REN'    => $find($finalHeaders, 'ULT-ANO_REN'),
            'RAZON_SOCIAL'   => $find($finalHeaders, 'RAZON SOCIAL'),
            'NIT'            => $find($finalHeaders, 'NIT'),
            'AFILIADO'       => $find($finalHeaders, 'CTR-AFILIACION'),
            'EMAIL_COMERCIAL'=> $find($finalHeaders, 'EMAIL-COMERCIAL'),
            'NOM_REP_LEGAL'  => $find($finalHeaders, 'NOM-REP-LEGAL'),
        ];

        if (!$map['MATRICULA'] || !$map['EST_MATRICULA']) {
            $this->error("Encabezados clave no encontrados en CSV. Revisar mapeo: " . json_encode($map));
            return 1;
        }

        $created = 0;
        $updated = 0;
        $ignoredNoNit = 0;
        $vip = 0;
        $processedExternalIds = [];
        $currentYear = now()->year;

        foreach ($records as $row) {
            $externalId = trim($row[$map['MATRICULA']] ?? '');
            if (!$externalId) continue;

            $nit = trim($row[$map['NIT']] ?? '');
            if (empty($nit)) {
                $ignoredNoNit++;
                continue;
            }

            $state = trim($row[$map['EST_MATRICULA']] ?? '');
            $affiliation = trim($row[$map['AFILIADO']] ?? '');

            // Validar estado/afiliación
            if (!(
                in_array($state, ['IA', 'MA']) ||
                $affiliation === '1'
            )) {
                continue;
            }

            // Determinar is_active (misma lógica original)
            $isActive = false;
            if ($affiliation === '1') {
                $isActive = true;
            } else {
                $ultAnio = trim((string)($row[$map['ULT_ANO_REN']] ?? ''));
                if ($ultAnio !== '' && preg_match('/^\d{4}$/', $ultAnio)) {
                    $isActive = ((int)$ultAnio === $currentYear);
                } else {
                    $fecRenov = trim((string)($row[$map['FEC_RENOVACION']] ?? ''));
                    if (preg_match('/^\d{8}$/', $fecRenov)) {
                        $anio = (int)substr($fecRenov, 0, 4);
                        $isActive = ($anio === $currentYear);
                    } else {
                        try {
                            $anio = Carbon::parse($fecRenov)->year;
                            $isActive = ($anio === $currentYear);
                        } catch (\Throwable $e) {
                            $isActive = false;
                        }
                    }
                }
            }

            $isVip = ($affiliation === '1');
            if ($isVip) $vip++;

            // Payload base
            $payload = [
                'state'          => $state,
                'rzsocial'       => $row[$map['RAZON_SOCIAL']] ?? null,
                'nit'            => $nit,
                'affiliation'    => $affiliation,
                'fcrenov'        => $row[$map['FEC_RENOVACION']] ?? null,
                'email'          => $row[$map['EMAIL_COMERCIAL']] ?? null,
                'name'           => $row[$map['NOM_REP_LEGAL']] ?? null,
                'is_vip'         => $isVip,
                'raw_data'       => json_encode([
                    'MATRICULA' => $externalId,
                    'EST-MATRICULA' => $state,
                    'FEC-RENOVACION' => $row[$map['FEC_RENOVACION']] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'is_active'      => (bool)$isActive,
            ];

            if ($isFull) {
                // Full: actualizar o crear (updateOrCreate)
                $merchant = Merchant::updateOrCreate(['external_id' => $externalId], array_merge(['external_id' => $externalId], $payload));
                $updated++;
            } else {
                // Incremental: solo crear si no existe (no tocar existentes)
                $exists = Merchant::where('external_id', $externalId)->exists();
                if ($exists) {
                    // no actualizar en incremental
                    $processedExternalIds[] = $externalId;
                    continue;
                }
                $merchant = Merchant::create(array_merge(['external_id' => $externalId], $payload));
                $created++;

                // Despachar job para generar QR (no enviar)
                dispatch(new ProcessQrJob($merchant->id, true, false, false));
                $this->info("Creado merchant {$externalId} (ID {$merchant->id}) y despachado job de generación.");
            }

            $processedExternalIds[] = $externalId;
        }

        // Si full sync: desactivar comerciantes no presentes en CSV
        if ($isFull && count($processedExternalIds) > 0) {
            $desactivados = Merchant::whereNotIn('external_id', $processedExternalIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            $this->info("Comerciantes desactivados: {$desactivados}");
        }

        $this->info("Sincronización finalizada. Nuevos: {$created}, Actualizados: {$updated}, Ignorados (sin NIT): {$ignoredNoNit}, VIPs: {$vip}");

        return 0;
    }
}
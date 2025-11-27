<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define la programación de comandos.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sincronización incremental cada noche a las 03:00 (modo incremental por defecto)
        $schedule->command('merchants:sync')->dailyAt('03:00')->onOneServer()->withoutOverlapping();

        // Generar QRs de los nuevos merchants a las 08:00
        $schedule->command('qr:process --action=generate --chunk=100')->dailyAt('08:00')->onOneServer()->withoutOverlapping();

        // Envío de mails a nuevos merchants: ventana 08:00 - 11:00 (cada 30 minutos)
        $schedule->command('qr:process --action=send --chunk=100')
            ->everyThirtyMinutes()
            ->between('08:00', '11:00')
            ->onOneServer()
            ->withoutOverlapping();

        // Nota: en producción se recomienda usar Supervisor para mantener workers en segundo plano.
        // Aquí se deja queue:work --stop-when-empty para compatibilidad con despliegues simples.
        $schedule->command('queue:work --stop-when-empty')->everyMinute();
    }

    /**
     * Registrar comandos de consola.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
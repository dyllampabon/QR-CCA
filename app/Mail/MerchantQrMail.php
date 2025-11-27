<?php

namespace App\Mail;

use App\Models\Merchant;
use App\Models\QrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MerchantQrMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $merchant;
    public $qr;

    public function __construct(Merchant $merchant, QrCode $qr)
    {
        $this->merchant = $merchant;
        $this->qr = $qr;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Tu Código QR de la Cámara de Comercio')
            ->markdown('emails.merchant_qr')
            ->attach(Storage::disk('public')->path($this->qr->filename), [
                'as'   => 'codigo_qr.png',
                'mime' => 'image/png',
            ]);
    }
}
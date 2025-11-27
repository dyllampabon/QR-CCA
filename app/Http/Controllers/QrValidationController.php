<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QrCode;
use App\Models\Merchant;
use App\Models\ScanMetric;
use Jenssegers\Agent\Agent;
use PDF;

class QrValidationController extends Controller
{
    public function show($token, Request $request)
    {
        $tokenHash = hash('sha256', $token);

        $qr = QrCode::where('token_hash', $tokenHash)
            ->where('token_expires_at', '>', now())
            ->first();

        $allies = Merchant::where('is_ally', 1)
                            ->where('is_active', 1)
                            ->get();

        $isValid = false;
        $merchant = null;
        $message = '';
        $reason = '';
        $discount = 0;
        $buyerType = 'common';

        if ($qr) {
            $merchant = Merchant::where('external_id', $qr->merchant_external_id)
                ->where('is_active', true)
                ->first();
            
            if ($merchant) {
                $buyerType = $merchant->is_vip ? 'vip' : 'common';
                $discount = $merchant->is_vip ? 20 : 10;

                if (empty($merchant->nit)) {
                    $message = 'Este comerciante no tiene nit. No se puede aplicar el beneficio.';
                    $reason = 'no_nit';
                } else {
                    $isValid = true;
                    $message = 'Soy un empresario matriculado en la Cámara de Comercio de Aguachica.';
                    $reason = 'valid';
                }
            } else {
                $message = 'Este comerciante no está activo.';
                $reason = 'inactive';
            }
        } else {
            $message = '¡Ponte al día para seguir disfrutando los beneficios!';
            $reason = 'invalid';
        }

        $buyer = $merchant;

        return view('qr.validate', compact(
            'allies',
            'isValid',
            'merchant',
            'buyer',
            'message',
            'reason',
            'qr',
            'discount',
            'token',
            'buyerType'
        ));
    }

    public function applyBenefit($token, Request $request)
    {
        $tokenHash = hash('sha256', $token);

        $qr = QrCode::where('token_hash', $tokenHash)
            ->where('token_expires_at', '>', now())
            ->first();

        $buyer = null;
        if ($qr) {
            $buyer = Merchant::where('external_id', $qr->merchant_external_id)
                ->where('is_active', true)
                ->first();
        }

        if (!$buyer || empty($buyer->nit)) {
            return redirect()->back()->with('error', 'QR no válido, expirado, el comerciante no está activo o no tiene nit.');
        }

        $request->validate([
            'purchase_amount' => 'required|numeric|min:0',
            'ally_id' => 'required|exists:merchants,id',
        ]);

        $ally = Merchant::where('id', $request->ally_id)->where('is_ally', 1)->first();
        if (!$ally) {
            return redirect()->back()->with('error', 'Debe ingresar un NIT válido de un aliado.');
        }

        $purchaseAmount = $request->input('purchase_amount');
        $discount = $buyer->is_vip ? $ally->discount_vip : $ally->discount_common;
        $discountValue = round($purchaseAmount * $discount / 100, 2);

        $agent = new Agent();
        $deviceType = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : ($agent->isDesktop() ? 'Desktop' : 'Other'));
        $platform = $agent->platform();
        $browser = $agent->browser();

        // Crear solo UNA métrica
        $metric = ScanMetric::create([
            'merchant_external_id' => $ally->external_id,
            'buyer_external_id' => $buyer->rzsocial,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => "{$deviceType} - {$platform} - {$browser}",
            'referer' => $request->headers->get('referer'),
            'purchase_amount' => $purchaseAmount,
            'discount_percent' => $discount,
            'discount_value' => $discountValue,
            'extra' => json_encode(['validated_at' => now()]),
        ]);

        return redirect()->route('qr.benefit_applied', [
            'metricId' => $metric->id,
            'token' => $token
        ]);
    }


    public function showBenefitPage($metricId, $token)
    {
        $metric = ScanMetric::findOrFail($metricId);

        // ⏳ Expiración de 2 minutos
        if ($metric->created_at->diffInMinutes(now()) > 2) {
            return redirect()->route('qr.validate', ['token' => $token])
                ->with('error', 'La página de beneficio ha expirado.');
        }

        $merchant = Merchant::where('external_id', $metric->merchant_external_id)->first();
        $buyer = Merchant::where('external_id', $metric->buyer_external_id)->first();

        $transactionNumber = "TX-{$metric->id}-{$metric->merchant_external_id}";

        return view('qr.benefit_applied', [
            'merchant' => $merchant->nit ?? $metric->merchant_external_id,
            'buyer' => $buyer->rzsocial ?? $metric->buyer_external_id,
            'purchase_amount' => $metric->purchase_amount,
            'discount_value' => $metric->discount_value,
            'discount_percent' => $metric->discount_percent,
            'transaction_number' => $transactionNumber,
            'metric_id' => $metric->id,
            'metric_created_at' => $metric->created_at,
            'token' => $token
        ]);
    }


    public function downloadBenefitPDF($metricId)
    {
        $metric = ScanMetric::findOrFail($metricId);
        $merchant = Merchant::where('external_id', $metric->merchant_external_id)->first();
        $buyer = Merchant::where('external_id', $metric->buyer_external_id)->first();

        $transactionNumber = "TX-{$metric->id}-{$metric->merchant_external_id}";

        $pdf = PDF::loadView('qr.benefit_applied_pdf', [
            'merchant' => $merchant->rzsocial ?? $metric->merchant_external_id,
            'buyer' => $buyer->rzsocial ?? $metric->buyer_external_id,
            'purchase_amount' => $metric->purchase_amount,
            'discount_value' => $metric->discount_value,
            'discount_percent' => $metric->discount_percent,
            'transaction_number' => $transactionNumber,
            'metric_created_at' => $metric->created_at,
        ]);

        return $pdf->download("benefit_{$metric->id}.pdf");
    }

    public function findAllyByNit(Request $request)
    {
        try {
            $request->validate([
                'nit' => 'required|digits_between:4,15'
            ]);

            $nit = $request->nit;
            $ally = Merchant::where('is_ally', 1)
                ->where('nit', $nit)
                ->first();

            if (!$ally) {
                return response()->json(['exists' => false]);
            }

            return response()->json([
                'exists' => true,
                'id' => $ally->id,
                'rzsocial' => $ally->rzsocial,
                'discount_common' => $ally->discount_common,
                'discount_vip' => $ally->discount_vip,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

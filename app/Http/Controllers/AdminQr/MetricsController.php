<?php

namespace App\Http\Controllers\AdminQr;

use App\Http\Controllers\Controller;
use App\Models\ScanMetric;
use App\Models\Merchant;
use App\Exports\MetricsExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function index(Request $request)
    {
        // ============================
        // FILTROS
        // ============================
        $from = $request->from
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $to = $request->to
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::now()->endOfDay();

        $merchantId = $request->merchant_id;

        // Query base
        $query = ScanMetric::whereBetween('created_at', [$from, $to]);

        if ($merchantId) {
            $merchant = Merchant::find($merchantId);
            if ($merchant) {
                $query->where('merchant_external_id', $merchant->external_id);
            }
        }

        //============================
        // PAGINACIÓN DE TABLA
        //============================
        $metrics = $query->with('merchant')
                        ->orderBy('created_at', 'desc')
                        ->paginate(15);

        //============================
        // LISTA DE COMERCIANTES
        //============================
        $merchants = Merchant::orderBy('rzsocial')->get();


        // ============================
        // KPI SUPERIORES
        // ============================
        $totalScans = (clone $query)->count();
        $uniqueIPs = (clone $query)->distinct('ip')->count('ip');

        $avgPurchase = (clone $query)->avg('purchase_amount') ?? 0;
        $avgDiscount = (clone $query)->avg('discount_value') ?? 0;


        // ============================
        // TENDENCIA 30 DÍAS
        // ============================
        $trendDates = [];
        $scanTrend = [];
        $purchaseTrend = [];
        $discountTrend = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->format('Y-m-d');
            $trendDates[] = Carbon::now()->subDays($i)->format('d M');

            $daily = (clone $query)
                ->whereDate('created_at', $day);

            $scanTrend[] = $daily->count();
            $purchaseTrend[] = $daily->sum('purchase_amount');
            $discountTrend[] = $daily->sum('discount_value');
        }


        // ============================
        // ESTADÍSTICAS DE DISPOSITIVOS
        // ============================
        $deviceBase = ScanMetric::query();

        $deviceBase->whereBetween('created_at', [$from, $to]);

        if ($merchantId && isset($merchant)) {
            $deviceBase->where('merchant_external_id', $merchant->external_id);
        }

        $deviceStats = $deviceBase
            ->select('device', DB::raw('COUNT(*) as total'))
            ->groupBy('device')
            ->orderBy('total', 'desc') // ← orden permitido
            ->pluck('total', 'device');


        // Aseguramos consistencia del arreglo
        $deviceStats = $deviceStats->toArray();


        // ============================
        // ESTADÍSTICAS DE REFERERS
        // ============================
        $refererBase = ScanMetric::query();

        $refererBase->whereBetween('created_at', [$from, $to]);

        if ($merchantId && isset($merchant)) {
            $refererBase->where('merchant_external_id', $merchant->external_id);
        }

        $refererStats = $refererBase
            ->select('referer', DB::raw('COUNT(*) as total'))
            ->groupBy('referer')
            ->orderBy('total', 'desc')
            ->pluck('total', 'referer');


        $refererStats = $refererStats->toArray();


        return view("adminqr.metrics.index", compact(
            'metrics',
            'merchants',
            'totalScans',
            'uniqueIPs',
            'avgPurchase',
            'avgDiscount',
            'trendDates',
            'scanTrend',
            'purchaseTrend',
            'discountTrend',
            'deviceStats',
            'refererStats'
        ));
    }



    // ============================
    // EXPORTACIÓN CSV
    // ============================
    public function export(Request $request)
    {
        $from = $request->from ? Carbon::parse($request->from) : Carbon::now()->subDays(30);
        $to = $request->to ? Carbon::parse($request->to) : Carbon::now();

        $merchantExternalId = null;
        if ($request->merchant_id) {
            $merchant = Merchant::find($request->merchant_id);
            if ($merchant) {
                $merchantExternalId = $merchant->external_id;
            }
        }

        $filename = "scan_metrics_export_" . now()->format("Ymd_His") . ".xlsx";

        return Excel::download(new MetricsExport($from, $to, $merchantExternalId), $filename);
    }
}

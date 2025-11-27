<?php

namespace App\Http\Controllers\AdminQr;

use App\Http\Controllers\Controller;
use App\Models\ScanMetric;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Total de escaneos
        $totalScans = ScanMetric::count();

        // Comerciantes activos e inactivos
        $activeMerchants = Merchant::where('is_active', true)->count();
        $vipMerchants = Merchant::where('is_vip', true)->count();
        $allies = Merchant::where('is_ally', 1)->get();

        // Top 5 Comerciantes más escaneados
        $topMerchantsQuery = ScanMetric::select('merchant_external_id', DB::raw('COUNT(*) as total'))
            ->groupBy('merchant_external_id')
            ->orderByDesc('total')
            ->take(5)
            ->with('merchant')
            ->get();

        $topMerchantsData = [
            'labels' => $topMerchantsQuery->map(fn($item) => $item->merchant->rzsocial ?? $item->merchant_external_id)->toArray(),
            'values' => $topMerchantsQuery->pluck('total')->toArray(),
        ];

        $topMerchant = $topMerchantsQuery->first()?->merchant;

        $merchantsStatusData = [
            'labels' => ['Activos', 'VIP'],
            'values' => [$activeMerchants, $vipMerchants],
        ];

        // Tendencias últimos 7 días
        $dates = collect();
        $transactionsData = [];
        $salesData = [];
        $discountsData = [];

        for ($i = 6; $i >= 0; $i--) {

            $dateObj = Carbon::today()->subDays($i);
            $date = $dateObj->format('Y-m-d');

            $dates->push($dateObj->format('d M'));

            $dailyMetrics = ScanMetric::whereDate('created_at', $date)->get();

            // Caso #2: algunos registros NO representan ventas reales
            $transactionsData[] = $dailyMetrics->count();
            $salesData[] = $dailyMetrics->sum('purchase_amount') ?? 0;
            $discountsData[] = $dailyMetrics->sum('discount_value') ?? 0;
        }

        $trendDates = $dates->values()->toArray();
        $transactionsData = array_values($transactionsData);
        $salesData = array_values($salesData);
        $discountsData = array_values($discountsData);

        $totalTransactions = array_sum($transactionsData);
        $totalSales = array_sum($salesData);
        $totalDiscounts = array_sum($discountsData);

        return view('adminqr.dashboard', [
            'totalScans' => $totalScans,
            'activeMerchants' => $activeMerchants,
            'vipMerchants' => $vipMerchants,
            'topMerchant' => $topMerchant,
            'topMerchantsData' => $topMerchantsData,
            'merchantsStatusData' => $merchantsStatusData,
            'trendDates' => $trendDates,           // ← YA NO ES SOLO 'trendDates'
            'transactionsData' => $transactionsData,
            'salesData' => $salesData,
            'discountsData' => $discountsData,
            'totalTransactions' => $totalTransactions,
            'totalSales' => $totalSales,
            'totalDiscounts' => $totalDiscounts,
            'allies' => $allies
        ]);

    }

    // 🔥 AJAX: Filtra la data SIN recargar la página
    public function filter()
    {
        $from = request()->from;
        $to = request()->to;
        $ally = request()->ally;

        $query = ScanMetric::query();

        if ($from) $query->whereDate('created_at', ">=", $from);
        if ($to)   $query->whereDate('created_at', "<=", $to);
        if ($ally) $query->where('merchant_external_id', $ally);

        // Generar series por día
        $dates = collect();
        $transactions = [];
        $sales = [];
        $discounts = [];

        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::today()->subDays($i);
            $date = $dateObj->format('Y-m-d');

            $dates->push($dateObj->format('d M'));

            $daily = (clone $query)->whereDate('created_at', $date)->get();

            $transactions[] = $daily->count();
            $sales[] = $daily->sum('purchase_amount') ?? 0;
            $discounts[] = $daily->sum('discount_value') ?? 0;
        }

        return response()->json([
            'trendDates' => $dates,
            'transactionsData' => $transactions,
            'salesData' => $sales,
            'discountsData' => $discounts,
            'totalTransactions' => array_sum($transactions),
            'totalSales' => array_sum($sales),
            'totalDiscounts' => array_sum($discounts),
        ]);
    }

    // 🔥 POP-UP: Comparación con semana anterior
    public function compare()
    {
        $current = ScanMetric::whereBetween('created_at', [
            Carbon::today()->subDays(6)->startOfDay(),
            Carbon::today()->endOfDay()
        ]);

        $previous = ScanMetric::whereBetween('created_at', [
            Carbon::today()->subDays(13)->startOfDay(),
            Carbon::today()->subDays(7)->endOfDay()
        ]);

        $data = [
            'transactions' => [
                'current' => $current->count(),
                'previous' => $previous->count(),
            ],
            'sales' => [
                'current' => $current->sum('purchase_amount'),
                'previous' => $previous->sum('purchase_amount'),
            ],
            'discounts' => [
                'current' => $current->sum('discount_value'),
                'previous' => $previous->sum('discount_value'),
            ]
        ];

        return response()->json($data);
    }
}

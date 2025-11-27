@extends('layouts.admin')
@section('title', '📊 Métricas de Escaneos')

@section('content')
<div class="p-6">
    <!-- ===================== -->
    <!-- KPI SUPERIORES -->
    <!-- ===================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <div class="bg-indigo-600 text-white rounded-xl shadow p-5">
            <h2 class="text-sm">Escaneos Totales</h2>
            <p class="text-3xl font-bold kpi-number" data-value="{{ $totalScans }}">0</p>
        </div>

        <div class="bg-sky-500 text-white rounded-xl shadow p-5">
            <h2 class="text-sm">Usuarios Únicos (IP)</h2>
            <p class="text-3xl font-bold kpi-number" data-value="{{ $uniqueIPs }}">0</p>
        </div>

        <div class="bg-green-500 text-white rounded-xl shadow p-5">
            <h2 class="text-sm">Ticket Promedio</h2>
            <p class="text-3xl font-bold kpi-number" data-value="{{ $avgPurchase }}%">$0</p>
        </div>

        <div class="bg-yellow-500 text-white rounded-xl shadow p-5">
            <h2 class="text-sm">Descuento Promedio</h2>
            <p class="text-3xl font-bold kpi-number" data-value="{{ $avgDiscount }}">0%</p>
        </div>

    </div>


    <!-- ===================== -->
    <!-- GRÁFICO PRINCIPAL -->
    <!-- ===================== -->
    <div class="bg-white shadow rounded-xl p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            Tendencia de Escaneos, Compras y Descuentos (30 días)
        </h2>
        <canvas id="metricsTrendChart" height="120"></canvas>
    </div>


    <!-- ===================== -->
    <!-- 2 GRÁFICOS SECUNDARIOS -->
    <!-- ===================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

        <!-- Dispositivos -->
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Distribución por Dispositivo</h2>
            <canvas id="deviceChart" height="160"></canvas>
        </div>

        <!-- Referers -->
        <div class="bg-white shadow rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Origen de Tráfico (Referer)</h2>
            <canvas id="refererChart" height="160"></canvas>
        </div>

    </div>


    <!-- ===================== -->
    <!-- FILTROS -->
    <!-- ===================== -->
    <form method="GET" action="{{ route('adminqr.metrics.index') }}" 
        class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white rounded-xl shadow-md p-6 mb-6">

        <div>
            <label class="text-sm text-gray-700">Desde</label>
            <input type="date" name="from" value="{{ request('from') }}"
                class="w-full mt-1 border rounded-lg p-2">
        </div>

        <div>
            <label class="text-sm text-gray-700">Hasta</label>
            <input type="date" name="to" value="{{ request('to') }}"
                class="w-full mt-1 border rounded-lg p-2">
        </div>

        <div>
            <label class="text-sm text-gray-700">Comerciante</label>
            <select name="merchant_id" 
                class="w-full mt-1 border rounded-lg p-2">
                <option value="">Todos</option>
                @foreach($merchants as $merchant)
                    <option value="{{ $merchant->id }}" @selected(request('merchant_id') == $merchant->id)>
                        {{ $merchant->rzsocial }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow">Filtrar</button>
            <a href="{{ route('adminqr.metrics.export', request()->all()) }}" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg shadow">Exportar Excel</a>
        </div>

    </form>


    <!-- ===================== -->
    <!-- TABLA -->
    <!-- ===================== -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-100 text-gray-700 uppercase text-sm">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Aliado</th>
                    <th class="px-4 py-3">Comprador</th>
                    <th class="px-4 py-3">Monto de Compra</th>
                    <th class="px-4 py-3">% de Descuento</th>
                    <th class="px-4 py-3">Valor de Descuento</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($metrics as $metric)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $metric->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 truncate max-w-[200px]">{{ $metric->merchant->rzsocial ?? $metric->merchant_external_id }}</td>
                    <td class="px-4 py-3">{{ $metric->buyer_external_id }}</td>
                    <td class="px-4 py-3 truncate max-w-[200px]">{{ '$ ' . number_format($metric->purchase_amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">{{ number_format($metric->discount_percent, 2) }}%</td>
                    <td class="px-4 py-3 truncate max-w-[150px]">{{ '$ ' . number_format($metric->discount_value, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-6">
        {{ $metrics->links('pagination::tailwind') }}
    </div>

</div>


<!-- ===================== -->
<!-- SCRIPTS -->
<!-- ===================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    // ---------------- KPIs ----------------
    document.querySelectorAll(".kpi-number").forEach(el => {
        let target = parseFloat(el.dataset.value || 0);
        let current = 0;

        let interval = setInterval(() => {
            current += target / 40;
            if (current >= target) {
                current = target;
                clearInterval(interval);
            }
            el.innerText = target < 1000 ? Math.round(current) : Math.round(current).toLocaleString();
        }, 20);
    });

    // ---------------- CHART 1 ----------------
    new Chart(document.getElementById("metricsTrendChart"), {
        type: 'line',
        data: {
            labels: @json($trendDates),
            datasets: [
                { label: 'Escaneos', data: @json($scanTrend), borderColor: '#4f46e5', tension: .3 },
                { label: 'Compras', data: @json($purchaseTrend), borderColor: '#10b981', tension: .3 },
                { label: 'Descuentos', data: @json($discountTrend), borderColor: '#ef4444', tension: .3 }
            ]
        }
    });

    // ---------------- CHART 2 ----------------
    new Chart(document.getElementById("deviceChart"), {
        type: 'pie',
        data: {
            labels: Object.keys(@json($deviceStats)),
            datasets: [{
                data: Object.values(@json($deviceStats)),
                backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444']
            }]
        }
    });

    // ---------------- CHART 3 ----------------
    new Chart(document.getElementById("refererChart"), {
        type: 'doughnut',
        data: {
            labels: Object.keys(@json($refererStats)),
            datasets: [{
                data: Object.values(@json($refererStats)),
                backgroundColor: ['#6366f1', '#14b8a6', '#f97316', '#dc2626']
            }]
        }
    });

});
</script>

@endsection

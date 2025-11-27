@extends('layouts.admin')
@section('title', '📊 Dashboard Administrativo')

@section('content')
<div class="p-6">
    <!-- TARJETAS SUPERIORES -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <div class="bg-green-500 text-white rounded-xl shadow p-4">
            <h4 id="activeMerchants" class="text-2xl font-bold">{{ $activeMerchants }}</h4>
            <p class="mt-1 text-sm">Comerciantes Activos</p>
        </div>

        <div class="bg-sky-500 text-white rounded-xl shadow p-4">
            <h4 id="totalScans" class="text-2xl font-bold">{{ $totalScans }}</h4>
            <p class="mt-1 text-sm">Transacciones Totales</p>
        </div>

        <div class="bg-yellow-500 text-white rounded-xl shadow p-4">
            <h4 id="vipMerchants" class="text-2xl font-bold">{{ $vipMerchants }}</h4>
            <p class="mt-1 text-sm">Comerciantes VIP</p>
        </div>

        <div class="bg-indigo-600 text-white rounded-xl shadow p-4">
            <h4 id="topMerchant" class="text-lg font-bold truncate">
                {{ $topMerchant->rzsocial ?? 'N/A' }}
            </h4>
            <p class="mt-1 text-sm">Más escaneado</p>
        </div>

    </div>


    <!-- CONTENEDOR PRINCIPAL -->
    <div class="mt-10 bg-white rounded-xl shadow-md p-6">

        <h2 class="text-lg font-semibold text-gray-700 mb-4">
            📊 Tendencia de Ventas y Descuentos (últimos 7 días)
        </h2>

        <div class="grid grid-cols-1 lg:grid-cols-10 gap-6">

            <!-- Gráfico -->
            <div class="col-span-1 lg:col-span-7">
                <canvas id="metricsTrendChart" height="160"></canvas>
            </div>

            <!-- Indicadores -->
            <div class="col-span-1 lg:col-span-3 space-y-4">

                <div class="bg-gray-50 shadow-sm rounded-lg p-4 text-center kpi" data-value="{{ $totalTransactions }}">
                    <h2 class="text-gray-500 text-sm">Transacciones</h2>
                    <p class="text-2xl font-bold kpi-number">0</p>
                </div>

                <div class="bg-gray-50 shadow-sm rounded-lg p-4 text-center kpi" data-value="{{ $totalSales }}">
                    <h2 class="text-gray-500 text-sm">Ventas Totales</h2>
                    <p class="text-2xl font-bold kpi-number">$0</p>
                </div>

                <div class="bg-gray-50 shadow-sm rounded-lg p-4 text-center kpi" data-value="{{ $totalDiscounts }}">
                    <h2 class="text-gray-500 text-sm">Descuentos Totales</h2>
                    <p class="text-2xl font-bold kpi-number">$0</p>
                </div>

            </div>
        </div>



        <!-- FILTROS AJAX -->
        <div class="mt-8 border-t pt-6 grid grid-cols-1 md:grid-cols-3 gap-6">

            <div>
                <label class="text-gray-700 text-sm">Desde</label>
                <input id="filter_from" type="date" class="w-full mt-1 border rounded-lg p-2">
            </div>

            <div>
                <label class="text-gray-700 text-sm">Hasta</label>
                <input id="filter_to" type="date" class="w-full mt-1 border rounded-lg p-2">
            </div>

            <div>
                <label class="text-gray-700 text-sm">Aliado</label>
                <select id="filter_ally" class="w-full mt-1 border rounded-lg p-2">
                    <option value="">Todos</option>
                    @foreach($allies as $ally)
                        <option value="{{ $ally->external_id }}">{{ $ally->rzsocial }}</option>
                    @endforeach
                </select>
            </div>

        </div>

    </div>
</div>


<!-- BOTÓN FLOTANTE -->
<button id="compareBtn"
    class="fixed bottom-6 right-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-3 rounded-full shadow-xl text-lg transition">
    📈 Comparar Semana Anterior
</button>


<!-- MODAL DE COMPARACIÓN -->
<div id="compareModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">

    <div class="bg-white w-96 rounded-xl shadow-xl p-6 relative">

        <button onclick="closeModal()" class="absolute top-2 right-3 text-gray-500 text-2xl">&times;</button>

        <h2 class="text-xl font-bold text-gray-700 mb-4">📊 Comparación semana anterior</h2>

        <div id="compareContent" class="space-y-3 text-gray-800 text-md">
            Cargando...
        </div>

    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let chartCtx = document.getElementById('metricsTrendChart');
let trendChart = null;

// Inicializa chart
function loadChart(labels, transactions, sales, discounts) {

    if (trendChart) trendChart.destroy();

    trendChart = new Chart(chartCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Transacciones',
                    data: transactions,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    tension: 0.3
                },
                {
                    label: 'Ventas Totales',
                    data: sales,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.2)',
                    tension: 0.3
                },
                {
                    label: 'Descuentos Totales',
                    data: discounts,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.2)',
                    tension: 0.3
                }
            ]
        }
    });
}


// Cargar gráfico inicial
loadChart(
    @json(array_values($trendDates)),
    @json(array_values($transactionsData)),
    @json(array_values($salesData)),
    @json(array_values($discountsData))
);



// --------------- AJAX FILTROS ------------------

document.querySelectorAll("#filter_from, #filter_to, #filter_ally")
    .forEach(e => e.addEventListener("change", applyFilters));

function applyFilters() {

    let data = {
        from: document.getElementById('filter_from').value,
        to: document.getElementById('filter_to').value,
        ally: document.getElementById('filter_ally').value,
        _token: "{{ csrf_token() }}"
    };

    fetch("{{ route('adminqr.dashboard.filter') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": data._token
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {

        loadChart(
            Object.values(data.trendDates),
            Object.values(data.transactionsData),
            Object.values(data.salesData),
            Object.values(data.discountsData)
        );

        animateKPIs([
            data.totalTransactions,
            data.totalSales,
            data.totalDiscounts,
            data.activeMerchants,
            data.totalScans,
            data.vipMerchants,
            data.topMerchants
        ]);
    });
}



// ---------------- KPIs ANIMADAS -----------------

function animateKPIs(values) {
    let elements = document.querySelectorAll(".kpi-number");

    elements.forEach((el, i) => {
        let target = values[i] ?? 0;
        let duration = 800;
        let start = 0;
        let stepTime = 10;

        let timer = setInterval(() => {
            start += target / (duration / stepTime);
            if (start >= target) {
                start = target;
                clearInterval(timer);
            }
            el.innerText = i === 0 ? Math.round(start) : "$" + Math.round(start).toLocaleString();
        }, stepTime);

    });
}

animateKPIs([
    {{ $totalTransactions }},
    {{ $totalSales }},
    {{ $totalDiscounts }}
]);

// ---------------- ANIMACIÓN TARJETAS SUPERIORES -----------------

function animateTopKPIs() {
    const topKPIs = [
        {selector: '#activeMerchants', value: {{ $activeMerchants ?? 0 }}},
        {selector: '#totalScans', value: {{ $totalScans ?? 0 }}},
        {selector: '#vipMerchants', value: {{ $vipMerchants ?? 0 }}}
    ];

    topKPIs.forEach(kpi => {
        const el = document.querySelector(kpi.selector);
        if (!el) return;

        let start = 0;
        let target = kpi.value;
        let duration = 1000; // duración en ms
        let stepTime = 20;
        let step = target / (duration / stepTime);

        const timer = setInterval(() => {
            start += step;
            if (start >= target) {
                start = target;
                clearInterval(timer);
            }
            el.innerText = Math.round(start);
        }, stepTime);
    });
}

// Llamar la animación al cargar la página
animateTopKPIs();


// ---------------- MODAL COMPARACIÓN -----------------

document.getElementById('compareBtn').addEventListener("click", () => {
    document.getElementById('compareModal').classList.remove('hidden');

    fetch("{{ route('adminqr.dashboard.compare') }}")
        .then(res => res.json())
        .then(data => {
            document.getElementById("compareContent").innerHTML = `
                <p><strong>Transacciones:</strong> ${data.transactions.current} vs ${data.transactions.previous}</p>
                <p><strong>Ventas Totales:</strong> ${data.sales.current} vs ${data.sales.previous}</p>
                <p><strong>Descuentos Totales:</strong> ${data.discounts.current} vs ${data.discounts.previous}</p>
            `;
        });
});

function closeModal() {
    document.getElementById('compareModal').classList.add('hidden');
}

</script>

@endsection

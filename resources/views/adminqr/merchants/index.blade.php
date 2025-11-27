@extends('layouts.admin')
@section('title', '🏪 Gestión de Comerciantes')

@section('head')
    <meta name="ajax-merchants-url" content="{{ route('adminqr.merchants.ajax.list') }}">
@endsection

@section('content')
<div class="p-6">

    <!-- FILTROS -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <input id="search" type="text" placeholder="Buscar..." 
               class="w-full sm:w-1/2 px-4 py-2 border rounded-lg shadow-sm" />

        <select id="merchant_type" class="w-full sm:w-40 px-4 py-2 border rounded-lg shadow-sm">
            <option value="">Todos</option>
            <option value="active">Activos</option>
            <option value="vip">VIP</option>
            <option value="ally">Aliados</option>
        </select>
    </div>
   
    <!-- TEMPLATE DEL SKELETON -->
    <template id="skeletonTemplate">
        {!! view('adminqr.merchants.partials.skeleton')->render() !!}
    </template>

    <!-- TABLA DINÁMICA -->
    <div id="tableContainer" class="bg-white rounded-xl shadow-md overflow-x-auto min-h-[200px]"></div>

    <!-- PAGINACIÓN AJAX -->
    <div id="paginationContainer" class="mt-6 flex justify-center"></div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {

    const tableContainer = document.querySelector("#tableContainer");
    const paginationContainer = document.querySelector("#paginationContainer");
    const ajaxUrl = document.querySelector('meta[name="ajax-merchants-url"]').content;

    function loadMerchants(page = 1) {

        // Mostrar skeleton
        tableContainer.innerHTML = document.querySelector("#skeletonTemplate").innerHTML;

        const search = document.querySelector("#search").value;
        const merchantType = document.querySelector("#merchant_type").value;

        const params = new URLSearchParams({
            page,
            search,
            merchant_type: merchantType
        });

        fetch(`${ajaxUrl}?${params.toString()}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
        .then(res => res.json())
        .then(data => {

            tableContainer.innerHTML = data.table;
            paginationContainer.innerHTML = data.pagination;

            // Reactivar enlaces AJAX
            paginationContainer.querySelectorAll("a").forEach(link => {
                link.addEventListener("click", e => {
                    e.preventDefault();
                    const page = new URL(link.href).searchParams.get("page");
                    loadMerchants(page);
                });
            });

        })
        .catch(err => {
            console.error("AJAX Error:", err);
            tableContainer.innerHTML = 
                "<p class='text-red-500 text-center py-6'>❌ Error al cargar los comerciantes.</p>";
        });
    }

    // Eventos filtros en tiempo real
    document.querySelector("#search").addEventListener("input", () => loadMerchants());
    document.querySelector("#merchant_type").addEventListener("change", () => loadMerchants());

    // Carga inicial
    loadMerchants();
});
</script>
@endsection
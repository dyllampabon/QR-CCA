@extends('layouts.public')

@section('title', 'Beneficio Aplicado')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[70vh] px-4 py-10 text-center">

    <!-- Badge número de transacción -->
    <div class="inline-block bg-black text-white py-1 px-3 rounded mb-4 font-mono text-sm">
        Número de transacción: {{ $transaction_number }}
    </div>

    <img src="{{ asset('img/aplicado.png') }}"
         alt="Tortuga - Cámara de Comercio"
         class="w-24 sm:w-32 md:w-40 lg:w-48 max-w-full h-auto mx-auto mb-4 transition-all duration-300"
         style="object-fit: contain;"
    >

    <h2 class="text-success mb-4">¡Tu descuento ha sido aplicado! 🎉 ¡Sigue comprando y ahorrando!</h2>

    <div class="w-full max-w-md bg-white rounded-xl shadow-md overflow-hidden border border-green-600 p-0">
        <div class="bg-green-500 text-white py-3 font-semibold text-lg">
            Resumen de la transacción
        </div>
        <div class="p-6">
            <p><strong>Aliado:</strong> {{ $merchant }}</p>
            <p><strong>Beneficiado:</strong> {{ $buyer }}</p>
            <p><strong>Valor de Compra:</strong> ${{ number_format($purchase_amount, 0, ',', '.') }}</p>
            <p><strong>Descuento aplicado:</strong> {{ $discount_percent }}% </p>
            <p><strong>Valor del descuento:</strong> ${{ number_format($discount_value, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Botón para descargar PDF -->
    <a href="{{ route('qr.download_benefit_pdf', ['metricId' => $metric_id]) }}" 
       class="mt-6 inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded">
        Descargar PDF
    </a>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const metricCreatedAt = new Date("{{ $metric_created_at }}");
    const now = new Date();

    // 3 minutos = 180 segundos
    if ((now - metricCreatedAt) / 1000 > 180) {
        alert('Esta página expiró. Serás redirigido a la página de validación.');

        window.location.href = "{{ route('qr.validate', ['token' => $token]) }}";
    }
});
</script>

@endsection

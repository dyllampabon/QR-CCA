@extends('layouts.public')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[70vh] px-4 py-2 text-center">
    
    @if($reason === 'valid')
        @php
            $cardColor = $buyerType === 'vip' ? 'bg-gradient-to-br border-yellow-600' : 'border-red-600';
            $buttonColor = $buyerType === 'vip' ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-red-600 hover:bg-red-700';
        @endphp

        <!-- ✅ QR válido -->
        <div class="max-w-md mxauto rounded-xl shadow-md overflow-hidden border p-6 text-center {{ $cardColor }}">
            <!-- Logos -->
            <div class="flex flex-row items-center justify-center gap-4 sm:gap-6 mb-4">
                <img src="{{ asset('img/logo.png') }}" alt="Cámara de Comercio" class="h-auto max-h-16 sm:max-h-20 md:max-h-24 w-auto transition-all duration-300">
                <img src="{{ asset('img/colombia_logo.png') }}" alt="CO Colombia" class="h-auto max-h-16 sm:max-h-20 md:max-h-24 w-auto transition-all duration-300">
            </div>

            <!-- Nombre del empresario o Razón social-->
            <h2 class="text-xl sm:text-2xl font-bold text-black mb-2">
                 @if(!empty($merchant->name))
                    {{ $merchant->name }}
                @else
                    {{ $merchant->rzsocial }}
                @endif
            </h2>

            <!-- NIT -->
            <h2 class="text-xs sm:text-2xs font-bold text-black mb-2">
                NIT: {{ $merchant->nit }}
            </h2>

            <!-- Mensaje -->
            <p class="text-gray-700 text-sm sm:text-base mb-4">
                {{ $message }}
            </p>

            <!-- Frase destacada -->
            <p class="inline-block bg-red-600 text-white font-bold py-1 px-3 rounded {{ $buttonColor }}">
                "Mi Cámara Es Tu Cámara"
            </p>
        </div>

        <!-- Card de comerciante -->
        <div class="w-full max-w-md mt-6 bg-white rounded-xl shadow-md p-6 text-left">

            <!-- Formulario beneficio -->
            <form method="POST" action="{{ route('qr.apply_benefit', ['token' => $token]) }}" class="space-y-5">
                @csrf

                <!-- Campo oculto necesario para evitar error SQL -->
                <input type="hidden" name="buyer_external_id" value="{{ $buyer->external_id }}">

                <!-- Ingresar NIT Aliado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ingresa tu NIT
                    </label>

                    <input 
                        type="text"
                        id="ally_nit"
                        placeholder="Digita tu NIT"
                        class="block w-full rounded-lg border border-gray-300 shadow-sm text-sm px-4 py-3"
                        autocomplete="off"
                        required
                        pattern="\d+"
                        oninput="this.value = this.value.replace(/[^0-9]/g,'')"
                        title="El NIT debe contener solo números."
                    >

                    <!-- Hidden ID del aliado -->
                    <input type="hidden" name="ally_id" id="ally_id_hidden">
                </div>

                <!-- Mensaje -->
                <p id="ally_message" class="text-sm mt-2 font-semibold"></p>


                <!-- Valor de Compra -->
                <div>
                    <label for="purchase_amount" class="block text-sm font-medium text-gray-700 mb-1">
                        Valor de Compra
                    </label>
                    <input 
                        type="number" min="0" step="1" 
                        id="purchase_amount" name="purchase_amount" required
                        class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-4 py-3"
                        style="appearance: textfield; -moz-appearance: textfield;" 
                        onwheel="this.blur()">
                </div>

                <!-- Porcentaje de Descuento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ofreceras descuento de
                    </label>
                    <input 
                        type="text" 
                        id="discount_percent" 
                        value="{{ $discount }}%" readonly
                        class="block w-full bg-gray-100 rounded-lg border border-gray-300 text-gray-600 text-sm px-4 py-3">
                </div>

                <!-- Valor del Descuento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Valor del descuento
                    </label>
                    <input 
                        type="text" 
                        id="discount_value" 
                        value="$0" readonly
                        class="block w-full bg-gray-100 rounded-lg border border-gray-300 text-gray-600 text-sm px-4 py-3"
                    >
                </div>

                <!-- Botón -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full px-4 py-3 text-white font-semibold rounded-lg shadow {{ $buttonColor }}"
                    >
                        Aplicar Descuento
                    </button>
                </div>
            </form>
        </div>

        @if(session('error'))
            <div class="mt-4 w-full max-w-md bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Script de cálculo y select2 -->
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buyerType = "{{ $buyerType }}";
            const allyNitInput = document.getElementById('ally_nit');
            const allyIdHidden = document.getElementById('ally_id_hidden');
            const allyMessage = document.getElementById('ally_message');

            const discountPercentInput = document.getElementById('discount_percent');
            const discountValueInput = document.getElementById('discount_value');
            const purchaseAmountInput = document.getElementById('purchase_amount');

            async function fetchAlly(nit) {
                if (!nit || nit.trim() === "") {
                    allyIdHidden.value = "";
                    allyMessage.textContent = "";
                    discountPercentInput.value = "0%";
                    discountValueInput.value = "$0";
                    return;
                }

                try {
                    const res = await fetch(`/api/ally/find-by-nit?nit=${encodeURIComponent(nit)}`);
                    if (!res.ok) throw new Error('Error en la respuesta del servidor');

                    const data = await res.json();

                    if (!data.exists) {
                        allyIdHidden.value = "";
                        allyMessage.textContent = "❌ Aliado no encontrado";
                        allyMessage.classList = "text-red-600 font-semibold";

                        discountPercentInput.value = "0%";
                        discountValueInput.value = "$0";
                        return;
                    }

                    allyIdHidden.value = data.id;
                    allyMessage.textContent = "✔ Aliado";
                    allyMessage.classList = "text-green-600 font-semibold";

                    const percent = buyerType === "vip" ? data.discount_vip : data.discount_common;
                    discountPercentInput.value = percent + "%";

                    let purchaseAmount = parseFloat(purchaseAmountInput.value) || 0;
                    let discountValue = Math.round(purchaseAmount * percent / 100);
                    discountValueInput.value = "$" + discountValue.toLocaleString();

                } catch (err) {
                    console.error(err);
                    allyIdHidden.value = "";
                    allyMessage.textContent = "❌ Error al consultar el aliado";
                    allyMessage.classList = "text-red-600 font-semibold";
                    discountPercentInput.value = "0%";
                    discountValueInput.value = "$0";
                }
            }

            allyNitInput.addEventListener('input', () => {
                const nit = allyNitInput.value;
                if (!/^\d*$/.test(nit)) {
                    allyMessage.textContent = "❌ El NIT solo puede contener números.";
                    allyMessage.classList = "text-red-600 font-semibold";
                    allyIdHidden.value = "";
                    discountPercentInput.value = "0%";
                    discountValueInput.value = "$0";
                    return;
                }
                fetchAlly(nit);
            });

            purchaseAmountInput.addEventListener('input', () => fetchAlly(allyNitInput.value));
        });
        </script>

    @elseif($reason === 'no_identification')
        <img src="{{ asset('img/expirado.png') }}"
         alt="Tortuga - Cámara de Comercio"
         class="w-24 sm:w-32 md:w-40 lg:w-48 max-w-full h-auto mx-auto mb-4 transition-all duration-300"
         style="object-fit: contain;">
        <h2 class="text-2xl font-bold text-yellow-500">Comerciante sin código de comercio</h2>
        <p class="mt-2 text-gray-600">{{ $message }}</p>

    @elseif($reason === 'inactive')
        <img src="{{ asset('img/expirado.png') }}"
         alt="Tortuga - Cámara de Comercio"
         class="w-24 sm:w-32 md:w-40 lg:w-48 max-w-full h-auto mx-auto mb-4 transition-all duration-300"
         style="object-fit: contain;">
        <h2 class="text-2xl font-bold text-yellow-500">Comerciante Inactivo</h2>
        <p class="mt-2 text-gray-600">{{ $message }}</p>

    @else
        <img src="{{ asset('img/expirado.png') }}"
         alt="Tortuga - Cámara de Comercio"
         class="w-24 sm:w-32 md:w-40 lg:w-48 max-w-full h-auto mx-auto mb-4 transition-all duration-300"
         style="object-fit: contain;">
        <h2 class="text-2xl font-bold text-red-600">QR no válido o expirado</h2>
        <p class="mt-2 text-gray-600">{{ $message }}</p>
    @endif
</div>
@endsection

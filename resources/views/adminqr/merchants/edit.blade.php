@extends('layouts.admin')
@section('title', 'Editar Comerciante')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">✏️ Editar Comerciante</h1>

    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="POST" action="{{ route('adminqr.merchants.update', $merchant) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- ESTADO ALIADO -->
            <div class="flex items-center gap-3">
                <input 
                    type="checkbox" 
                    id="is_ally" 
                    name="is_ally"
                    value="1"
                    class="h-5 w-5 text-indigo-600 rounded focus:ring-indigo-500"
                    {{ old('is_ally', $merchant->is_ally) ? 'checked' : '' }}
                >
                <label for="is_ally" class="text-sm font-medium text-gray-700">
                    ¿Es comerciante aliado?
                </label>
            </div>

            <!-- DESCUENTOS (solo visibles cuando es aliado) -->
            <div id="discount_fields" class="{{ old('is_ally', $merchant->is_ally) ? '' : 'hidden' }} space-y-6">

                <!-- Descuento para cliente común -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Descuento para cliente común (%)
                    </label>
                    <input 
                        type="number" 
                        min="0" 
                        max="100"
                        name="discount_common"
                        value="{{ old('discount_common', $merchant->discount_common ?? 0) }}"
                        class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-4 py-3"
                        style="appearance: textfield; -moz-appearance: textfield;" 
                        onwheel="this.blur()">
                </div>

                <!-- Descuento para cliente VIP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Descuento para cliente VIP (%)
                    </label>
                    <input 
                        type="number" 
                        min="0" 
                        max="100"
                        name="discount_vip"
                        value="{{ old('discount_vip', $merchant->discount_vip ?? 0) }}"
                        class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm px-4 py-3"
                        style="appearance: textfield; -moz-appearance: textfield;" 
                        onwheel="this.blur()">
                </div>
            </div>

            <!-- Botón -->
            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow hover:bg-indigo-700 transition"
                >
                    Guardar Cambios
                </button>
            </div>

        </form>
    </div>
</div>

<!-- SCRIPT para mostrar/ocultar descuentos -->
<script>
document.getElementById('is_ally').addEventListener('change', function() {
    const fields = document.getElementById('discount_fields');
    this.checked ? fields.classList.remove('hidden') : fields.classList.add('hidden');
});
</script>
@endsection

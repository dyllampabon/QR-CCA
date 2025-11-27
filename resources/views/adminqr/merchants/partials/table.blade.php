<table class="w-full table-auto border-collapse">
    <thead class="bg-gray-100 text-gray-700 uppercase text-sm">
        <tr>
            <th class="px-4 py-3 border-b w-1/4">Razón Social</th>
            <th class="px-4 py-3 border-b w-1/6">NIT</th>
            <th class="px-4 py-3 border-b w-1/6">Estado</th>
            <th class="px-4 py-3 border-b w-1/6">Afiliado</th>
            <th class="px-4 py-3 border-b w-1/6">Descargar QR</th>
            <th class="px-4 py-3 border-b w-1/6">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        @forelse($merchants as $merchant)
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 truncate max-w-[200px]">{{ $merchant->rzsocial }}</td>
            <td class="px-4 py-3">{{ $merchant->nit }}</td>
            <td class="px-4 py-3">
                @if($merchant->is_active)
                    <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">Activo</span>
                @else
                    <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800">Inactivo</span>
                @endif
            </td>
            <td class="px-4 py-3">
                @if($merchant->is_vip)
                    <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">VIP</span>
                @else
                    <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800">No VIP</span>
                @endif
            </td>
            <td class="px-4 py-3">
                @if($merchant->qrCode)
                    <a href="{{ route('adminqr.merchants.downloadQr', $merchant) }}"
                       class="block w-32 px-3 py-2 bg-blue-600 text-white text-sm text-center rounded-lg shadow hover:bg-blue-700"
                       target="_blank">Descargar QR</a>
                @endif
            </td>
            <td class="px-4 py-3 flex flex-col gap-2">
                <a href="{{ route('adminqr.merchants.edit', $merchant) }}"
                   class="block w-32 px-3 py-2 bg-yellow-500 text-white text-sm text-center rounded-lg shadow hover:bg-yellow-600">Editar</a>
                <form action="{{ route('adminqr.merchants.destroy', $merchant) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este comerciante?')">
                    @csrf
                    <button type="submit"
                            class="w-32 px-3 py-2 bg-red-500 text-white text-sm rounded-lg shadow hover:bg-red-600">Eliminar</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No hay comerciantes registrados.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - @yield('title', 'Auría')</title>
    @vite('resources/css/app.css')
    @yield('head')
</head>
<body class="bg-gray-100 font-sans text-gray-800">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-gray-100 flex flex-col">
            <div class="p-6 text-2xl font-bold border-b border-gray-700">
                QR Admin
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="{{ route('adminqr.dashboard') }}" class="flex items-center p-2 rounded hover:bg-gray-700 transition">
                    <span class="ml-2">📊 Dashboard</span>
                </a>
                <a href="{{ route('adminqr.metrics.index') }}" class="flex items-center p-2 rounded hover:bg-gray-700 transition">
                    <span class="ml-2">📈 Métricas</span>
                </a>
                <a href="{{ route('adminqr.merchants.index') }}" class="flex items-center p-2 rounded hover:bg-gray-700 transition">
                    <span class="ml-2">🛍️ Comerciantes</span>
                </a>
            </nav>
            <div class="p-4 border-t border-gray-700">
                {{-- route('admin.dashboard') --}}
                <a href="" class="flex items-center p-2 rounded hover:bg-gray-700 transition">
                    <span class="ml-2">🔙 Panel principal</span>
                </a>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="flex items-center justify-between bg-white px-6 py-4 border-b border-gray-200 shadow-sm">
                <h1 class="text-xl font-semibold">@yield('title', 'Panel')</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Hola, Admin</span>
                    <img src="https://i.pravatar.cc/40" class="rounded-full w-10 h-10" alt="avatar">
                </div>
            </header>

            <!-- Main content -->
            <main class="flex-1 p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
    {{-- Scripts individuales de cada vista --}}
    @yield('scripts')
</body>
</html>

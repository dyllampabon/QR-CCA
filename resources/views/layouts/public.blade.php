<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Validación QR')</title>
    @vite('resources/css/app.css') <!-- TailwindCSS -->
</head>
<body class="bg-gray-100 font-sans text-gray-800 min-h-screen flex flex-col">

    <!-- Contenido principal -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white shadow rounded-lg p-6">
            @yield('content')
        </div>
    </main>

    <!-- Footer opcional -->
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="container mx-auto px-6 text-center text-gray-600 text-sm">
            © {{ date('Y') }} Powered By Origamy Corp. Todos los derechos reservados.
        </div>
    </footer>

</body>
</html>

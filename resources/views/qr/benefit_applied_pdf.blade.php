<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Beneficio Aplicado</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }

        /* Badge */
        .badge {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            font-family: monospace;
        }

        /* Contenedor para centrar y dar tamaño consistente */
        .image-wrapper {
            width: 100%;
            max-width: 300px; /* mismo ancho que la tarjeta */
            margin: 0 auto 20px auto;
        }

        /* Imagen tortuga */
        .my-image {
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* Mensaje */
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #16a34a;
            margin-bottom: 20px;
        }

        /* Tarjeta Resumen */
        .card {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border: 2px solid #16a34a;
            border-radius: 12px;
            overflow: hidden;
            text-align: left;
        }

        .card-header {
            background: #16a34a;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            padding: 12px;
            text-align: center;
        }

        .card-body {
            padding: 20px;
            font-size: 15px;
            line-height: 1.5;
        }

        .row {
            margin-bottom: 8px;
        }

        .row strong {
            display: inline-block;
            width: 150px;
        }
    </style>
</head>
<body>

    <!-- Badge -->
    <div class="badge">
        Número de transacción: {{ $transaction_number }}
    </div>

    <!-- Imagen centrada del mismo ancho que el resumen -->
    <div class="image-wrapper">
        <img src="{{ public_path('img/aplicado.png') }}" class="my-image" alt="Beneficio aplicado">
    </div>

    <!-- Mensaje -->
    <div class="title">
        ¡Tu descuento ha sido aplicado! 🎉 ¡Sigue comprando y ahorrando!
    </div>

    <!-- Card -->
    <div class="card">
        <div class="card-header">
            Resumen de la transacción
        </div>

        <div class="card-body">
            <p class="row"><strong>Aliado:</strong> {{ $merchant }}</p>
            <p class="row"><strong>Beneficiado:</strong> {{ $buyer }}</p>
            <p class="row"><strong>Valor de Compra:</strong> ${{ number_format($purchase_amount, 0, ',', '.') }}</p>
            <p class="row"><strong>Descuento aplicado:</strong> {{ $discount_percent }}%</p>
            <p class="row"><strong>Valor del descuento:</strong> ${{ number_format($discount_value, 0, ',', '.') }}</p>
            <p class="row"><strong>Fecha:</strong> {{ $metric_created_at }}</p>
        </div>
    </div>

</body>
</html>

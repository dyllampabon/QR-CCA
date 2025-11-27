@component('mail::message')
# Hola {{ $merchant->name }}

Adjunto encontrarás tu **código QR**, el cual te identifica como comerciante activo de la Cámara de Comercio de Aguachica.

Conserva este correo para futuros usos.  

Gracias,  
**{{ config('app.name') }}**
@endcomponent

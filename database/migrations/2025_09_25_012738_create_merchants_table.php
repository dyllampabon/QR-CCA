<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique(); // matrícula / id externo
            $table->string('state')->nullable(); // EST-MATRICULA (IA/MA)
            $table->string('rzsocial')->nullable();
            $table->string('nit')->nullable(); // guardar como string (puede tener ceros/guiones)
            $table->string('affiliation')->nullable(); // CTR-AFILIACION = 1
            $table->boolean('is_vip')->default(false); // true si CTR-AFILIACION = 1
            $table->date('fcrenov')->nullable(); // fecha última renovación
            $table->string('email')->nullable();
            $table->string('name')->nullable(); // representante legal u otro
            $table->json('raw_data')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_ally')->default(false);
            $table->unsignedTinyInteger('discount_common')->default(0); // 0-255
            $table->unsignedTinyInteger('discount_vip')->default(0); // 0-255
            $table->unsignedBigInteger('discount_value')->nullable(); // valor monetario (en centavos)
            $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};

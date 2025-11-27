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
        Schema::create('scan_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_external_id')->index();
            $table->string('buyer_external_id')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('referer')->nullable();
            $table->unsignedBigInteger('purchase_amount')->nullable(); // Valor de la compra
            $table->unsignedTinyInteger('discount_percent')->nullable(); // % de descuento aplicado
            $table->unsignedBigInteger('discount_value')->nullable(); // Valor del descuento aplicado
            $table->json('extra')->nullable(); // Otros datos adicionales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_metrics');
    }
};
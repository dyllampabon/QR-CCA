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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_external_id')->index(); // FK lógica a merchants.external_id
            $table->string('filename')->nullable(); // storage path
            $table->string('token_hash', 64)->nullable(); // hash('sha256', $token)
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable(); // correo enviado
            $table->boolean('is_active')->default(true); // si el QR está vigente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};

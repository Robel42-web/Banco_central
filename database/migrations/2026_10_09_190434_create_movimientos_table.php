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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuenta_id')
                ->constrained('cuentas')
                ->restrictOnDelete();

            $table->string('tipo', 20); // deposito o retiro

            $table->unsignedBigInteger('monto_centavos');
            $table->unsignedBigInteger('saldo_anterior_centavos');
            $table->unsignedBigInteger('saldo_nuevo_centavos');

            $table->string('referencia', 100)->nullable()->unique();
            $table->string('descripcion', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};

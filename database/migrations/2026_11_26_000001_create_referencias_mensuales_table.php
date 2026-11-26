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
        Schema::create('referencias_mensuales', function (Blueprint $table) {
            $table->id();
            $table->date('mes'); // Siempre el día 1 del mes, ej: 2026-11-01
            $table->string('producto');
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('unidad');
            $table->decimal('precio_referencia', 10, 2)->nullable();
            $table->decimal('cantidad_plan_nutrir', 10, 2)->nullable();
            $table->string('nota')->nullable();
            $table->timestamps();

            $table->unique(['mes', 'producto', 'unidad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referencias_mensuales');
    }
};

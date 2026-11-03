<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('denuncias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete(); // quien denuncia
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->string('motivo');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['pendiente', 'revisada', 'descartada'])->default('pendiente');
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('denuncias');
    }
};

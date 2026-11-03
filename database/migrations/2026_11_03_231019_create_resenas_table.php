<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('publicacion_id')->constrained('publicaciones')->cascadeOnDelete();
            $table->unsignedTinyInteger('puntuacion'); // 1 a 5, validado también en el Form Request
            $table->text('comentario')->nullable();
            $table->timestamps();

            // Un usuario no puede reseñar la misma publicación dos veces.
            $table->unique(['usuario_id', 'publicacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('titulo');
            $table->text('descripcion');
            $table->decimal('precio', 10, 2);
            $table->string('unidad'); // kg, unidad, bulto...
            $table->unsignedInteger('stock')->default(0);
            $table->enum('estado', ['activa', 'pausada', 'vendida'])->default('activa');
            $table->timestamps();

            $table->index(['estado', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};

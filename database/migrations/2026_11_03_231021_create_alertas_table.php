<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('mensaje');
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index(['usuario_id', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};

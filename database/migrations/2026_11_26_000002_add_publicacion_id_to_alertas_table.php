<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->foreignId('publicacion_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('publicaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('publicacion_id');
        });
    }
};

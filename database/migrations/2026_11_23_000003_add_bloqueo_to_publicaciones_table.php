<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En MySQL actualizamos el tipo enum para incluir 'bloqueada'; en SQLite alteramos la columna o dejamos texto
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE publicaciones MODIFY COLUMN estado ENUM('activa', 'pausada', 'vendida', 'bloqueada') NOT NULL DEFAULT 'activa'");
        }

        Schema::table('publicaciones', function (Blueprint $table) {
            $table->text('motivo_bloqueo')->nullable()->after('estado');
            $table->foreignId('bloqueada_por')->nullable()->after('motivo_bloqueo')->constrained('users')->nullOnDelete();
            $table->timestamp('bloqueada_at')->nullable()->after('bloqueada_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publicaciones', function (Blueprint $table) {
            $table->dropForeign(['bloqueada_por']);
            $table->dropColumn(['motivo_bloqueo', 'bloqueada_por', 'bloqueada_at']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE publicaciones MODIFY COLUMN estado ENUM('activa', 'pausada', 'vendida') NOT NULL DEFAULT 'activa'");
        }
    }
};

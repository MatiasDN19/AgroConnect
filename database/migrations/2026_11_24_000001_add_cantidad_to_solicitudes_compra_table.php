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
        Schema::table('solicitudes_compra', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->default(1)->after('publicacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_compra', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};

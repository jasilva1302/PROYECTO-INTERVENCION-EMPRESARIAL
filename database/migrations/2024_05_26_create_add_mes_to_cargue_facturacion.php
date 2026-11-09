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
        Schema::table('cargue_facturacion', function (Blueprint $table) {
            $table->string('mes')->nullable()->after('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargue_facturacion', function (Blueprint $table) {
            $table->dropColumn('mes');
        });
    }
}; 
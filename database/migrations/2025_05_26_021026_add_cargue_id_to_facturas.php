<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedBigInteger('cargue_id')->nullable()->after('id');
            $table->foreign('cargue_id')->references('id')->on('cargue_facturacion')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['cargue_id']);
            $table->dropColumn('cargue_id');
        });
    }
}; 
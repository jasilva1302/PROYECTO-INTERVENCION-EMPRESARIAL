<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->date('inicio_vigencia')->after('year');
            $table->date('fin_vigencia')->after('inicio_vigencia');
            $table->unsignedBigInteger('created_by')->nullable()->after('activo');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['inicio_vigencia', 'fin_vigencia', 'created_by', 'updated_by']);
        });
    }
}; 
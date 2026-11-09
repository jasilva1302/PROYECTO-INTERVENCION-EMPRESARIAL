<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audits', function (Blueprint $table) {
            if (!Schema::hasColumn('audits', 'tiempo_acumulado')) {
                $table->integer('tiempo_acumulado')->default(0)->after('completed_at');
            }
        });
    }

    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            if (Schema::hasColumn('audits', 'tiempo_acumulado')) {
                $table->dropColumn('tiempo_acumulado');
            }
        });
    }
}; 
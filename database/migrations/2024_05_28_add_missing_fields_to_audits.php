<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Campos de estado y progreso
            if (!Schema::hasColumn('audits', 'status')) {
                $table->string('status')->default('in_progress')->after('mes');
            }
            if (!Schema::hasColumn('audits', 'completed')) {
                $table->boolean('completed')->default(false)->after('status');
            }

            // Campos de fechas
            if (!Schema::hasColumn('audits', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('completed');
            }
            if (!Schema::hasColumn('audits', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('audits', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('completed_at');
            }

            // Campo de link (antes era ruta)
            if (!Schema::hasColumn('audits', 'link')) {
                $table->string('link')->nullable()->after('finished_at');
            }

            // Campos de timestamps si no existen
            if (!Schema::hasColumn('audits', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'completed',
                'started_at',
                'completed_at',
                'finished_at',
                'link'
            ]);
        });
    }
}; 
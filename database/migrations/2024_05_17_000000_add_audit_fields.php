<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Campos de ID y relaciones si no existen
            if (!Schema::hasColumn('audits', 'id')) {
                $table->id();
            }
            if (!Schema::hasColumn('audits', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');
                $table->foreign('user_id')->references('id')->on('users');
            }

            // Campos de tiempo y estado
            $table->string('status')->default('in_progress')->after('user_id');
            $table->timestamp('started_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('finished_at')->nullable()->after('completed_at');
            $table->boolean('completed')->default(false)->after('finished_at');
            
            // Campo de link para la ruta
            $table->string('link')->nullable()->after('completed');

            // Campos de timestamp de sistema
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Eliminar foreign key
            $table->dropForeign(['user_id']);

            // Eliminar columnas
            $table->dropColumn([
                'user_id',
                'status',
                'started_at',
                'completed_at',
                'finished_at',
                'completed',
                'link',
                'created_at',
                'updated_at'
            ]);
        });
    }
}; 
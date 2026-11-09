<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Agregar campos de relación
            $table->unsignedBigInteger('empresa_id')->after('id');
            $table->unsignedBigInteger('sede_id')->after('empresa_id');
            
            // Agregar campos de fecha
            $table->string('mes')->after('sede_id'); // Formato: YYYY-MM
            $table->date('fecha_inicio')->after('mes')->nullable();
            $table->date('fecha_fin')->after('fecha_inicio')->nullable();
            
            // Agregar campos de estado y progreso
            $table->string('estado')->default('pendiente')->after('fecha_fin');
            $table->integer('total_facturas')->default(0)->after('estado');
            $table->integer('facturas_auditadas')->default(0)->after('total_facturas');
            
            // Agregar campos de auditoría
            $table->unsignedBigInteger('created_by')->after('facturas_auditadas');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Agregar llaves foráneas
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('sede_id')->references('id')->on('sedes');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::table('audits', function (Blueprint $table) {
            // Eliminar llaves foráneas
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['sede_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            
            // Eliminar columnas
            $table->dropColumn([
                'empresa_id',
                'sede_id',
                'mes',
                'fecha_inicio',
                'fecha_fin',
                'estado',
                'total_facturas',
                'facturas_auditadas',
                'created_by',
                'updated_by'
            ]);
        });
    }
}; 
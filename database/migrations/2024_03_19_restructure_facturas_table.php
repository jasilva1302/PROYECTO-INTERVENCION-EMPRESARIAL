<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Crear la nueva tabla factura_servicios
        Schema::create('factura_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->string('cups_servicio')->nullable();
            $table->string('cargo_servicio')->nullable();
            $table->text('descripcion_servicio')->nullable();
            $table->integer('cantidad');
            $table->string('autorizacion_servicio')->nullable();
            $table->decimal('valor_unitario_servicio', 15, 2)->nullable();
            $table->decimal('valor_total_servicio', 15, 2)->nullable();
            $table->timestamps();

            $table->index('factura_id');
            $table->index('cups_servicio');
        });

        // 2. Migrar los datos existentes
        DB::statement('
            INSERT INTO factura_servicios (
                factura_id,
                cups_servicio,
                cargo_servicio,
                descripcion_servicio,
                cantidad,
                autorizacion_servicio,
                valor_unitario_servicio,
                valor_total_servicio,
                created_at,
                updated_at
            )
            SELECT 
                id as factura_id,
                cups_servicio,
                cargo_servicio,
                descripcion_servicio,
                cantidad,
                autorizacion_servicio,
                valor_unitario_servicio,
                valor_total_servicio,
                created_at,
                updated_at
            FROM facturas
            WHERE cups_servicio IS NOT NULL
        ');

        // 3. Eliminar las columnas de la tabla facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'cups_servicio',
                'cargo_servicio',
                'descripcion_servicio',
                'cantidad',
                'autorizacion_servicio',
                'valor_unitario_servicio',
                'valor_total_servicio'
            ]);
        });
    }

    public function down()
    {
        // 1. Agregar las columnas de vuelta a facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('cups_servicio')->nullable();
            $table->string('cargo_servicio')->nullable();
            $table->text('descripcion_servicio')->nullable();
            $table->integer('cantidad')->nullable();
            $table->string('autorizacion_servicio')->nullable();
            $table->decimal('valor_unitario_servicio', 15, 2)->nullable();
            $table->decimal('valor_total_servicio', 15, 2)->nullable();
        });

        // 2. Restaurar los datos
        DB::statement('
            UPDATE facturas f
            INNER JOIN factura_servicios fs ON f.id = fs.factura_id
            SET 
                f.cups_servicio = fs.cups_servicio,
                f.cargo_servicio = fs.cargo_servicio,
                f.descripcion_servicio = fs.descripcion_servicio,
                f.cantidad = fs.cantidad,
                f.autorizacion_servicio = fs.autorizacion_servicio,
                f.valor_unitario_servicio = fs.valor_unitario_servicio,
                f.valor_total_servicio = fs.valor_total_servicio
        ');

        // 3. Eliminar la tabla factura_servicios
        Schema::dropIfExists('factura_servicios');
    }
}; 
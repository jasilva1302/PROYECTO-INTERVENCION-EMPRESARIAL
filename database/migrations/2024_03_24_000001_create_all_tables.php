<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Modules table
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('route')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('module_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });

        // Permission Role pivot table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
            $table->unique(['permission_id', 'role_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // Add foreign key to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        // Empresas table
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nit')->unique();
            $table->string('razon_social');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Sedes table
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_habilitacion')->unique();
            $table->string('sede');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('empresa_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
        });

        // Audits table
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sede_id');
            $table->timestamp('started_at')->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('sede_id')->references('id')->on('sedes');
        });

        // Hallazgos table
        Schema::create('hallazgos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id');
            $table->text('descripcion');
            $table->string('documento_referencia')->nullable();
            $table->boolean('is_ok')->default(false);
            $table->timestamps();
            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
        });

        // Facturas table
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sede_id');
            $table->string('departamento');
            $table->string('tipo');
            $table->string('identificacion');
            $table->string('paciente');
            $table->string('tipo_cliente');
            $table->string('documento_aseguradora');
            $table->string('aseguradora');
            $table->string('plan');
            $table->string('numero_cuenta');
            $table->string('cups_servicio');
            $table->string('cargo_servicio');
            $table->string('descripcion_servicio');
            $table->integer('cantidad');
            $table->string('autorizacion_servicio');
            $table->decimal('valor_unitario_servicio', 15, 2);
            $table->decimal('valor_total_servicio', 15, 2);
            $table->date('fecha_documento');
            $table->string('prefijo_documento');
            $table->string('numero_documento');
            $table->string('factura');
            $table->date('periodo_facturacion_inicio');
            $table->date('periodo_facturacion_final');
            $table->date('contabilizacion_documento')->nullable();
            $table->string('interface_documento')->nullable();
            $table->string('adquiriente');
            $table->decimal('total_documento', 15, 2);
            $table->decimal('saldo', 15, 2);
            $table->text('observacion_documento')->nullable();
            $table->string('estado_documento_dian')->nullable();
            $table->string('estado_transaccion_dian')->nullable();
            $table->datetime('fecha_validacion_dian')->nullable();
            $table->string('resultado_dian')->nullable();
            $table->string('cufe')->nullable();
            $table->date('fecha_radicacion')->nullable();
            $table->string('numero_radicacion')->nullable();
            $table->string('usuario');
            $table->timestamps();
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('sede_id')->references('id')->on('sedes');
        });

        // Cargue Facturacion table
        Schema::create('cargue_facturacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->string('nombre_archivo');
            $table->string('tipo_archivo');
            $table->integer('total_registros');
            $table->integer('registros_procesados')->default(0);
            $table->integer('registros_error')->default(0);
            $table->string('estado')->default('pendiente');
            $table->text('errores')->nullable();
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('sede_id')->references('id')->on('sedes');
        });

        // Tarifas table
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sede_id');
            $table->string('cups');
            $table->string('descripcion');
            $table->string('regimen');
            $table->decimal('valor', 12, 2);
            $table->year('year');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('sede_id')->references('id')->on('sedes');
            $table->unique(['empresa_id', 'sede_id', 'cups', 'year', 'regimen'], 'tarifa_unique');
            $table->index('cups');
        });

        // Tarifa Historial table
        Schema::create('tarifa_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tarifa_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('valor_anterior', 12, 2);
            $table->decimal('valor_nuevo', 12, 2);
            $table->string('motivo')->nullable();
            $table->timestamps();
            $table->foreign('tarifa_id')->references('id')->on('tarifas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });

        // Sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tarifa_historial');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('cargue_facturacion');
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('hallazgos');
        Schema::dropIfExists('audits');
        Schema::dropIfExists('sedes');
        Schema::dropIfExists('empresas');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sessions');
    }
}; 
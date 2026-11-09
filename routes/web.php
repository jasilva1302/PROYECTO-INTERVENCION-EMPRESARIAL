<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\FacturaPdfController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuditFilesController;
use App\Http\Controllers\AuditoriaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\CargueFacturacionController;
use App\Http\Controllers\VerifyRolesController;
use App\Http\Controllers\ImportProgressController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuditoriaTarifasController;
use App\Http\Controllers\RadicacionController;

Route::get('/', fn () => redirect()->route('home'));

// Autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Auditoría
    Route::prefix('auditoria')->name('auditoria.')->group(function () {
        Route::get('/', [AuditoriaController::class, 'index'])->name('index');
        Route::post('/', [AuditoriaController::class, 'store'])->name('store');
        Route::get('/facturas', [AuditoriaController::class, 'getFacturas'])->name('facturas');
        Route::get('/periodos', [AuditoriaController::class, 'getPeriodosDisponibles'])->name('periodos');
        Route::get('/{audit}', [AuditoriaController::class, 'show'])->name('show');
        Route::get('/{audit}/iniciar', [AuditoriaController::class, 'start'])->name('start');
        Route::delete('/{audit}', [AuditoriaController::class, 'destroy'])->name('destroy');
        
        // Rutas específicas de la auditoría en curso
        Route::post('/{audit}/pausar', [AuditoriaController::class, 'pausarAuditoria'])->name('pausar');
        Route::post('/{audit}/{factura}/observacion', [AuditoriaController::class, 'saveObservation'])->name('save-observation');
        Route::get('/{audit}/verificar-observaciones', [AuditoriaController::class, 'verificarObservaciones'])->name('verificar-observaciones');
        Route::post('/{audit}/terminar', [AuditoriaController::class, 'terminarAuditoria'])->name('terminar');
        Route::get('/{audit}/reporte', [AuditoriaController::class, 'generarReporte'])->name('generate-report');
        Route::get('/{audit}/get-factura-data/{factura}', [AuditoriaController::class, 'getFacturaData'])->name('get-factura-data');
        Route::get('/formato-excel', [AuditoriaController::class, 'descargarFormatoExcel'])->name('formato-excel');

        // Auditoría de Tarifas
        
    });

    // Estadísticas
    Route::prefix('estadisticas')->name('estadisticas.')->group(function () {
        Route::get('/', [EstadisticaController::class, 'index'])->name('index');
        Route::get('/asistentes', [EstadisticaController::class, 'asistentes'])->name('asistentes');
        Route::get('/sedes', [EstadisticaController::class, 'sedes'])->name('sedes');
    });

    // Tarifas
    Route::group(['prefix' => 'tarifas', 'as' => 'tarifas.'], function () {
        Route::get('/', [TarifaController::class, 'index'])->name('index');
        Route::get('/create', [TarifaController::class, 'create'])->name('create');
        Route::post('/', [TarifaController::class, 'store'])->name('store');
        Route::get('/{tarifa}/edit', [TarifaController::class, 'edit'])->name('edit');
        Route::put('/{tarifa}', [TarifaController::class, 'update'])->name('update');
        Route::delete('/{tarifa}', [TarifaController::class, 'destroy'])->name('destroy');
        Route::post('/import', [TarifaController::class, 'import'])->name('import');
        Route::post('/bulk-update', [TarifaController::class, 'bulkUpdate'])->name('bulkUpdate');
        Route::post('/{tarifa}/quick-update', [TarifaController::class, 'quickUpdate'])->name('quickUpdate');
        Route::get('/template', [TarifaController::class, 'downloadTemplate'])->name('template');
        Route::get('/error-report/{filename}', [TarifaController::class, 'downloadErrorReport'])->name('download-error-report');
        Route::get('/download-report/{filename}', [TarifaController::class, 'downloadErrorReport'])->name('download-report');
        Route::get('/{tarifa}/historial', [TarifaController::class, 'historial'])->name('historial');
        
        // Auditoría de tarifas
        Route::get('/auditoria', [AuditoriaTarifasController::class, 'index'])->name('auditoria.index');
    });

    // Ruta para el progreso de importación
    Route::get('/import-progress/{importId}', [ImportProgressController::class, 'getProgress'])->name('import.progress');

    // Evaluaciones
    Route::prefix('evaluaciones')->name('evaluaciones.')->group(function () {
        Route::get('/', [EvaluacionController::class, 'index'])->name('index');
        Route::get('/crear', [EvaluacionController::class, 'create'])->name('create');
        Route::post('/', [EvaluacionController::class, 'store'])->name('store');
        Route::get('/grade/{evaluacionUsuario}', [EvaluacionController::class, 'grade'])->name('grade');
        Route::post('/grade/{evaluacionUsuario}', [EvaluacionController::class, 'saveGrade'])->name('saveGrade');
        Route::get('/{evaluacion}/realizar', [EvaluacionController::class, 'take'])->name('take');
        Route::post('/{evaluacion}/submit', [EvaluacionController::class, 'submit'])->name('submit');
        Route::get('/{evaluacion}/editar', [EvaluacionController::class, 'edit'])->name('edit');
        Route::put('/{evaluacion}', [EvaluacionController::class, 'update'])->name('update');
        Route::delete('/{evaluacion}', [EvaluacionController::class, 'destroy'])->name('destroy');
        Route::get('/{evaluacion}', [EvaluacionController::class, 'show'])->name('show');
    });

    // Configuración
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index'])->name('index');
        
        // Usuarios
        Route::get('/usuarios/crear', [ConfiguracionController::class, 'createUser'])->name('create-user');
        Route::post('/usuarios', [ConfiguracionController::class, 'storeUser'])->name('store-user');
        Route::get('/usuarios/{user}/editar', [ConfiguracionController::class, 'editUser'])->name('edit-user');
        Route::put('/usuarios/{user}', [ConfiguracionController::class, 'updateUser'])->name('update-user');
        Route::delete('/usuarios/{user}', [ConfiguracionController::class, 'destroyUser'])->name('destroy-user');
        
        // Roles y Permisos
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/crear', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/editar', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::get('/roles/permisos/ver', [RoleController::class, 'viewAllPermissions'])->name('roles.permissions.view');
        // Gestión de permisos de roles
        Route::get('/roles/{role}/permisos', [ConfiguracionController::class, 'editPermissions'])->name('edit-permissions');
        Route::put('/roles/{role}/permisos', [ConfiguracionController::class, 'updatePermissions'])->name('update-permissions');

        // Empresas
        Route::get('/empresas/create', [EmpresaController::class, 'create'])->name('create-empresa');
        Route::post('/empresas', [EmpresaController::class, 'store'])->name('store-empresa');
        Route::get('/empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('edit-empresa');
        Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('update-empresa');
        Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('destroy-empresa');
        
        // Sedes
        Route::get('/sedes/create', [SedeController::class, 'create'])->name('create-sede');
        Route::post('/sedes', [SedeController::class, 'store'])->name('store-sede');
        Route::get('/sedes/{sede}/edit', [SedeController::class, 'edit'])->name('edit-sede');
        Route::put('/sedes/{sede}', [SedeController::class, 'update'])->name('update-sede');
        Route::delete('/sedes/{sede}', [SedeController::class, 'destroy'])->name('destroy-sede');
    });

    // Facturación
    Route::prefix('facturacion')->name('facturacion.')->group(function () {
        Route::get('/cargue', [CargueFacturacionController::class, 'index'])->name('cargue.index');
        Route::get('/cargue/create', [CargueFacturacionController::class, 'create'])->name('cargue.create');
        Route::post('/cargue', [CargueFacturacionController::class, 'store'])->name('cargue.store');
        Route::get('/cargue/{cargue}', [CargueFacturacionController::class, 'show'])->name('cargue.show');
        Route::get('/cargue/template/download', [CargueFacturacionController::class, 'downloadTemplate'])->name('cargue.template.download');
        Route::delete('/cargue/{cargue}', [CargueFacturacionController::class, 'destroy'])->name('cargue.destroy');
        Route::get('/get-facturas', [CargueFacturacionController::class, 'getFacturas'])->name('get-facturas');
    });

    Route::get('auditoria/facturas', [AuditoriaController::class, 'getFacturas'])->name('auditoria.facturas');

    // Rutas para el módulo de Radicación
    Route::get('/radicacion', [RadicacionController::class, 'index'])->name('radicacion.index');
    Route::post('/radicacion/cargar-masivo', [RadicacionController::class, 'cargarMasivo'])->name('radicacion.cargar-masivo');
    Route::get('/radicacion/descargar-plantilla', [RadicacionController::class, 'descargarPlantilla'])->name('radicacion.descargar-plantilla');
    Route::post('/radicacion/agregar-observacion', [RadicacionController::class, 'agregarObservacion'])->name('radicacion.agregar-observacion');
    Route::post('/radicacion/cargar-observaciones-masivo', [RadicacionController::class, 'cargarObservacionesMasivo'])->name('radicacion.cargar-observaciones-masivo');
    Route::get('/radicacion/descargar-plantilla-observaciones', [RadicacionController::class, 'descargarPlantillaObservaciones'])->name('radicacion.descargar-plantilla-observaciones');
    Route::get('/radicacion/exportar/{estado}', [RadicacionController::class, 'exportarFacturas'])->name('radicacion.exportar');
});

// Rutas para manejo de PDFs
Route::get('/auditoria/pdf-structure/{audit}', [FacturaPdfController::class, 'showStructure'])->name('auditoria.pdf-structure');
Route::get('/auditoria/pdf-viewer/{audit}/{aseguradora}/{factura}', [FacturaPdfController::class, 'showFactura'])->name('auditoria.pdf-viewer');
Route::get('/auditoria/pdf/{audit}/{aseguradora}/{factura}/{filename}', [FacturaPdfController::class, 'getPdf'])->name('auditoria.pdf');

// Ruta para ver PDFs directamente
Route::get('/ver-pdf/{path}', function (Request $request) {
    try {
        $path = base64_decode($request->path);
        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado');
        }
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0'
        ]);
    } catch (\Exception $e) {
        Log::error('Error al servir PDF', [
            'error' => $e->getMessage(),
            'path' => $request->path
        ]);
        abort(404, 'Error al acceder al archivo');
    }
})->name('ver.pdf.simple');

Route::get('/audits/{auditId}/files/{facturaNumber}', [AuditFilesController::class, 'getFacturaFiles']);

Route::get('/check-audit-files', function() {
    try {
        // Obtener la primera factura de prueba
        $factura = App\Models\Factura::first();
        
        if (!$factura) {
            return response()->json([
                'success' => false,
                'message' => 'No hay facturas en la base de datos'
            ], 404);
        }

        // Obtener la auditoría asociada
        $auditFactura = App\Models\AuditFactura::where('factura_id', $factura->id)->first();
        
        if (!$auditFactura) {
            return response()->json([
                'success' => false,
                'message' => 'La factura no está asociada a una auditoría'
            ], 404);
        }

        $audit = App\Models\Audit::find($auditFactura->audit_id);
        
        if (!$audit) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la auditoría'
            ], 404);
        }

        // Obtener los archivos usando el servicio
        $pdfFiles = $audit->getPdfFiles($factura->aseguradora, $factura->factura);

        return response()->json([
            'success' => true,
            'message' => 'Información de archivos',
            'factura' => $factura->factura,
            'audit_id' => $audit->id,
            'files_count' => count($pdfFiles),
            'pdf_files' => array_map(function($file) {
                return [
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'url' => $file['url']
                ];
            }, $pdfFiles)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al verificar archivos',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Ruta de prueba para verificar acceso a archivos
Route::get('/test-file-access/{auditId}/{path?}', function ($auditId, $path = null) {
    try {
        $audit = App\Models\Audit::findOrFail($auditId);
        $fileService = new App\Services\AuditFileService($audit);

        if (!$path) {
            // Obtener la primera factura y su primer PDF
            $facturas = $audit->facturas()->first();
            if (!$facturas) {
                return response()->json([
                    'error' => 'No hay facturas disponibles'
                ], 404);
            }

            $pdfFiles = $fileService->getPdfFiles($facturas->aseguradora, $facturas->factura);
            if (empty($pdfFiles)) {
                return response()->json([
                    'error' => 'No hay PDFs disponibles',
                    'aseguradora' => $facturas->aseguradora,
                    'factura' => $facturas->factura
                ], 404);
            }

            $path = $pdfFiles[0]['path'];
        }

        $result = $fileService->testFileAccess($path);
        
        return response()->json([
            'audit_id' => $auditId,
            'test_results' => $result,
            'disk_config' => [
                'root' => Config::get('filesystems.disks.facturas.root'),
                'driver' => Config::get('filesystems.disks.facturas.driver'),
                'permissions' => Config::get('filesystems.disks.facturas.permissions')
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth')->name('test.file.access');

Route::get('/verify-roles', [VerifyRolesController::class, 'index']);

Route::get('tarifas/download-error-report/{filename}', [App\Http\Controllers\TarifaController::class, 'downloadErrorReport'])->name('tarifas.download-error-report');

// Ruta temporal para verificar permisos
Route::get('/debug-permissions', function() {
    $role = \App\Models\Role::where('name', 'ASISTENTE')->first();
    $user = \App\Models\User::where('role_id', $role->id)->first();
    
    $permissionsToCheck = [
        'ver_auditorias',
        'ver_facturacion',
        'ver_estadisticas',
        'ver_tarifas',
        'ver_evaluaciones'
    ];

    $results = [];
    foreach ($permissionsToCheck as $permission) {
        $results[$permission] = [
            'raw_check' => in_array($permission, $role->permissions ?? []),
            'role_check' => $role->hasPermissionTo($permission),
            'user_check' => $user->hasPermission($permission)
        ];
    }

    return response()->json([
        'role' => [
            'name' => $role->name,
            'permissions_raw' => $role->getOriginal('permissions'),
            'permissions_decoded' => json_decode($role->getOriginal('permissions'), true),
            'permissions_attribute' => $role->permissions
        ],
        'permission_checks' => $results
    ]);
});

// Ruta temporal para verificar y actualizar permisos del ASISTENTE
Route::get('/update-asistente-permissions', function() {
    try {
        DB::beginTransaction();
        
        $role = \App\Models\Role::where('name', 'ASISTENTE')->first();
        
        if (!$role) {
            return response()->json(['error' => 'Rol ASISTENTE no encontrado']);
        }

        // Obtener los IDs de los permisos que necesita el ASISTENTE
        $permissionIds = \App\Models\Permission::whereIn('name', [
            'ver_auditorias',
            'crear_auditorias',
            'editar_auditorias',
            'ver_facturacion',
            'cargar_facturacion',
            'ver_estadisticas',
            'ver_tarifas',
            'ver_evaluaciones',
            'realizar_evaluaciones'
        ])->pluck('id')->toArray();

        // Sincronizar los permisos usando la relación many-to-many
        $role->permissions()->sync($permissionIds);

        DB::commit();

        return response()->json([
            'message' => 'Permisos actualizados correctamente',
            'role' => $role->name,
            'permissions' => $role->permissions()->pluck('name')
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Error al actualizar permisos',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/fix-asistente-permissions', function() {
    try {
        DB::beginTransaction();
        
        // Obtener el rol ASISTENTE
        $role = \App\Models\Role::where('name', 'ASISTENTE')->first();
        if (!$role) {
            return response()->json(['error' => 'Rol ASISTENTE no encontrado']);
        }

        // IDs de los permisos que debe tener el ASISTENTE según la tabla permissions
        $permissionIds = [
            1,  // ver Auditorías
            5,  // ver Facturación
            10, // ver Estadísticas
            11, // ver Tarifas
            15  // ver Evaluaciones
        ];

        // Sincronizar los permisos
        $role->permissions()->sync($permissionIds);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente',
            'permissions' => $role->permissions()->pluck('name')->toArray()
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Error al actualizar permisos',
            'message' => $e->getMessage()
        ], 500);
    }
});


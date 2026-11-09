use App\Http\Controllers\EmpresaController;

Route::get('/empresas/{empresa}/sedes', [EmpresaController::class, 'getSedes']); 
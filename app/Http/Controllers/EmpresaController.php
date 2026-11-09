<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use App\Traits\HasPermissionChecks;

class EmpresaController extends Controller
{
    use HasPermissionChecks;

    public function index()
    {
        $this->checkPermission('ver Empresas');
        $empresas = Empresa::withCount('sedes')->get();
        return view('empresas.index', compact('empresas'));
    }

    public function show(Empresa $empresa)
    {
        $this->checkPermission('ver Empresas');
        return view('empresas.show', compact('empresa'));
    }

    public function create()
    {
        $this->checkPermission('crear Empresas');
        return view('configuracion.empresas.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('crear Empresas');
        
        $validated = $request->validate([
            'nit' => 'required|unique:empresas,nit',
            'razon_social' => 'required'
        ]);

        Empresa::create($validated);

        return redirect()->route('configuracion.index')
            ->with('success', 'Empresa creada exitosamente.');
    }

    public function edit(Empresa $empresa)
    {
        $this->checkPermission('editar Empresas');
        return view('configuracion.empresas.edit', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $this->checkPermission('editar Empresas');
        
        $validated = $request->validate([
            'nit' => 'required|unique:empresas,nit,' . $empresa->id,
            'razon_social' => 'required'
        ]);

        $empresa->update($validated);

        return redirect()->route('configuracion.index')
            ->with('success', 'Empresa actualizada exitosamente.');
    }

    public function destroy(Empresa $empresa)
    {
        $this->checkPermission('eliminar Empresas');
        
        if($empresa->sedes()->count() > 0) {
            return redirect()->route('configuracion.index')
                ->with('error', 'No se puede eliminar la empresa porque tiene sedes asociadas.');
        }

        $empresa->delete();

        return redirect()->route('configuracion.index')
            ->with('success', 'Empresa eliminada exitosamente.');
    }

    public function getSedes(Empresa $empresa)
    {
        return response()->json($empresa->sedes()->select('id', 'sede')->get());
    }
} 
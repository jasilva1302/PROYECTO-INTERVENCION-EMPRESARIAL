<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\Empresa;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function create()
    {
        $empresas = Empresa::all();
        return view('configuracion.sedes.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_habilitacion' => 'required|unique:sedes,codigo_habilitacion',
            'sede' => 'required',
            'empresa_id' => 'required|exists:empresas,id'
        ]);

        Sede::create($validated);

        return redirect()->route('configuracion.index')
            ->with('success', 'Sede creada exitosamente.');
    }

    public function edit(Sede $sede)
    {
        $empresas = Empresa::all();
        return view('configuracion.sedes.edit', compact('sede', 'empresas'));
    }

    public function update(Request $request, Sede $sede)
    {
        $validated = $request->validate([
            'codigo_habilitacion' => 'required|unique:sedes,codigo_habilitacion,' . $sede->id,
            'sede' => 'required',
            'empresa_id' => 'required|exists:empresas,id'
        ]);

        $sede->update($validated);

        return redirect()->route('configuracion.index')
            ->with('success', 'Sede actualizada exitosamente.');
    }

    public function destroy(Sede $sede)
    {
        if($sede->facturas()->count() > 0) {
            return redirect()->route('configuracion.index')
                ->with('error', 'No se puede eliminar la sede porque tiene facturas asociadas.');
        }

        $sede->delete();

        return redirect()->route('configuracion.index')
            ->with('success', 'Sede eliminada exitosamente.');
    }
} 
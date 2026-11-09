<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\User;
use App\Models\Hallazgo;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Sede;
use App\Traits\HasPermissionChecks;
use Illuminate\Support\Facades\Config;

class EstadisticaController extends Controller
{
    use HasPermissionChecks;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.estadisticas.ver'));
        return redirect()->route('estadisticas.asistentes');
    }

    public function asistentes()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.estadisticas.ver'));
        
        $asistentes = User::where('role_id', function($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('name', 'ASISTENTE')
                    ->first();
            })
            ->select('users.*')
            ->withCount(['audits as total_audits'])
            ->withCount(['audits as hallazgos_count' => function($query) {
                $query->whereHas('auditFacturas', function($q) {
                    $q->has('hallazgos');
                });
            }])
            ->withAvg(['audits as tiempo_promedio' => function($query) {
                $query->whereNotNull('completed_at');
            }], DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)'))
            ->get()
            ->map(function($asistente) {
                // Convertir el tiempo promedio de minutos a horas y minutos
                if ($asistente->tiempo_promedio) {
                    $horas = floor($asistente->tiempo_promedio / 60);
                    $minutos = $asistente->tiempo_promedio % 60;
                    $asistente->tiempo_promedio = $horas + ($minutos / 60);
                }
                return $asistente;
            });

        return view('estadisticas.asistentes', compact('asistentes'));
    }

    public function sedes()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.estadisticas.ver'));
        
        $sedes = Sede::withCount(['audits as total_audits'])
            ->withCount(['audits as hallazgos_count' => function($query) {
                $query->whereHas('auditFacturas', function($q) {
                    $q->has('hallazgos');
                });
            }])
            ->withAvg(['audits as tiempo_promedio' => function($query) {
                $query->whereNotNull('completed_at');
            }], DB::raw('TIMESTAMPDIFF(MINUTE, created_at, completed_at)'))
            ->get()
            ->map(function($sede) {
                // Convertir el tiempo promedio de minutos a horas y minutos
                if ($sede->tiempo_promedio) {
                    $horas = floor($sede->tiempo_promedio / 60);
                    $minutos = $sede->tiempo_promedio % 60;
                    $sede->tiempo_promedio = $horas + ($minutos / 60);
                }
                return $sede;
            });

        $hallazgosComunes = Hallazgo::query()
            ->select('descripcion')
            ->selectRaw('count(*) as total')
            ->groupBy('descripcion')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('estadisticas.sedes', compact('sedes', 'hallazgosComunes'));
    }
}
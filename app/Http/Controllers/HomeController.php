<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Support\Facades\Config;

class HomeController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Obtener los módulos del sistema para el menú
        $systemModules = [
            [
                'name' => 'Auditorías',
                'icon' => 'fas fa-clipboard-check',
                'route' => 'auditoria.index',
                'permission' => Config::get('permissions.auditoria.ver'),
                'description' => 'Gestión de auditorías de facturación',
                'color' => '#FF6F00',
                'gradient' => 'linear-gradient(135deg, #FF6F00 0%, #FFA000 100%)'
            ],
            [
                'name' => 'Facturación',
                'icon' => 'fas fa-file-invoice',
                'route' => 'facturacion.cargue.index',
                'permission' => Config::get('permissions.facturacion.ver'),
                'description' => 'Gestión de facturación',
                'color' => '#2196F3',
                'gradient' => 'linear-gradient(135deg, #2196F3 0%, #64B5F6 100%)'
            ],
            [
                'name' => 'Estadísticas',
                'icon' => 'fas fa-chart-bar',
                'route' => 'estadisticas.index',
                'permission' => Config::get('permissions.estadisticas.ver'),
                'description' => 'Reportes y análisis estadísticos',
                'color' => '#4CAF50',
                'gradient' => 'linear-gradient(135deg, #4CAF50 0%, #81C784 100%)'
            ],
            [
                'name' => 'Tarifas',
                'icon' => 'fas fa-dollar-sign',
                'route' => 'tarifas.index',
                'permission' => Config::get('permissions.tarifas.ver'),
                'description' => 'Administración de tarifas médicas',
                'color' => '#9C27B0',
                'gradient' => 'linear-gradient(135deg, #9C27B0 0%, #BA68C8 100%)'
            ],
            [
                'name' => 'Evaluaciones',
                'icon' => 'fas fa-tasks',
                'route' => 'evaluaciones.index',
                'permission' => Config::get('permissions.evaluaciones.ver'),
                'description' => 'Gestión de evaluaciones al personal',
                'color' => '#F44336',
                'gradient' => 'linear-gradient(135deg, #F44336 0%, #EF5350 100%)'
            ],
            [
                'name' => 'Radicación',
                'icon' => 'fas fa-file-invoice',
                'route' => 'radicacion.index',
                'permission' => 'Ver Radicación',
                'description' => 'Gestión de radicación de facturas',
                'color' => '#E91E63',
                'gradient' => 'linear-gradient(135deg, #E91E63 0%, #F06292 100%)'
            ]
        ];

        // Para administradores, mostrar todos los módulos
        if ($user->hasRole('Administrador')) {
            return view('home', ['modules' => $systemModules]);
        }

        // Para otros usuarios, filtrar los módulos según los permisos
        $availableModules = collect($systemModules)->filter(function($module) use ($user) {
            return $user->hasPermission($module['permission']);
        })->all();

        return view('home', ['modules' => $availableModules]);
    }
}
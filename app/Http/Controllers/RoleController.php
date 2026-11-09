<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function getAvailablePermissions()
    {
        return config('permissions');
    }

    public function index()
    {
        try {
            \Log::info('Iniciando carga de roles');
            $roles = Role::withCount('users')->get();
            \Log::info('Roles cargados: ' . $roles->count());
            $availablePermissions = $this->getAvailablePermissions();
            $roles = $roles->map(function($role) use ($availablePermissions) {
                \Log::info("Procesando rol: {$role->name}");
                if (strtoupper($role->name) === 'ADMINISTRADOR') {
                    $allPermissions = collect($availablePermissions)
                        ->flatMap(function($permissions, $module) {
                            return collect($permissions)->map(function($description, $action) use ($module) {
                                return [
                                    'name' => "{$module}.{$action}",
                                    'description' => $description
                                ];
                            });
                        })->values();
                    $role->permissions = $allPermissions;
                    \Log::info("Permisos asignados al ADMINISTRADOR: " . collect($allPermissions)->pluck('name')->join(', '));
                } else {
                    $permissions = collect($role->permissions ?? []);
                    $formattedPermissions = $permissions->map(function($permission) use ($availablePermissions) {
                        if (is_array($permission) && isset($permission['name'])) {
                            return $permission;
                        }
                        return [
                            'name' => $permission,
                            'description' => $this->getPermissionDescription($permission, $availablePermissions)
                        ];
                    });
                    $role->permissions = $formattedPermissions;
                    \Log::info("Permisos del rol {$role->name}: " . collect($formattedPermissions)->pluck('name')->join(', '));
                }
                return $role;
            });
            \Log::info('Roles procesados exitosamente');
            return view('configuracion.roles.index', compact('roles'));
        } catch (\Exception $e) {
            \Log::error('Error al cargar roles: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Error al cargar los roles: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene la descripción de un permiso basado en su nombre
     */
    private function getPermissionDescription(string $permission, array $availablePermissions = null): string
    {
        if (!$availablePermissions) {
            $availablePermissions = $this->getAvailablePermissions();
        }
        
        $parts = explode('.', $permission);
        if (count($parts) !== 2) {
            return $permission;
        }

        $module = $parts[0];
        $action = $parts[1];

        if (isset($availablePermissions[$module][$action])) {
            return $availablePermissions[$module][$action];
        }

        return ucfirst($action) . ' ' . strtolower($module);
    }

    public function create()
    {
        return view('configuracion.roles.create', [
            'availablePermissions' => $this->getAvailablePermissions(),
            'rolePermissions' => []
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array'
        ]);
        try {
            DB::beginTransaction();
            $permissions = [];
            if ($request->has('permissions')) {
                foreach ($request->permissions as $permission) {
                    $parts = explode('.', $permission);
                    if (count($parts) === 2) {
                        $module = $parts[0];
                        $action = $parts[1];
                        $permissions[] = "$module.$action";
                    }
                }
            }
            $role = Role::create([
                'name' => strtoupper($request->name),
                'permissions' => $permissions
            ]);
            DB::commit();
            return redirect()
                ->route('configuracion.roles.index')
                ->with('success', 'Rol creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear rol: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()
                ->with('error', 'Error al crear el rol: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Role $role)
    {
        try {
            $rolePermissions = [];
            $availablePermissions = $this->getAvailablePermissions();
            if (strtoupper($role->name) === 'ADMINISTRADOR') {
                foreach ($availablePermissions as $module => $permissions) {
                    foreach ($permissions as $action => $description) {
                        $rolePermissions[] = "{$module}.{$action}";
                    }
                }
            } else {
                $rolePermissions = $role->permissions ?? [];
            }
            return view('configuracion.roles.edit', [
                'role' => $role,
                'availablePermissions' => $availablePermissions,
                'rolePermissions' => $rolePermissions
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al editar rol: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Error al cargar el rol: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Role $role)
    {
        if (strtoupper($role->name) === 'ADMINISTRADOR') {
            return back()->with('error', 'No se puede modificar el rol de Administrador');
        }
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);
        try {
            DB::beginTransaction();
            $role->name = strtoupper($request->name);
            $permissions = [];
            if ($request->has('permissions')) {
                foreach ($request->permissions as $permission) {
                    $parts = explode('.', $permission);
                    if (count($parts) === 2) {
                        $module = $parts[0];
                        $action = $parts[1];
                        $permissions[] = "$module.$action";
                    }
                }
            }
            $role->permissions = $permissions;
            $role->save();
            DB::commit();
            return redirect()
                ->route('configuracion.roles.index')
                ->with('success', 'Rol actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar rol: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()
                ->with('error', 'Error al actualizar el rol: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'ADMINISTRADOR') {
            return back()->with('error', 'No se puede eliminar el rol de Administrador');
        }
        if ($role->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados');
        }
        try {
            DB::beginTransaction();
            $role->delete();
            DB::commit();
            return redirect()
                ->route('configuracion.roles.index')
                ->with('success', 'Rol eliminado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    public function viewAllPermissions()
    {
        $roles = Role::withCount('users')->get();
        $permissions = $this->getAvailablePermissions();
        
        return view('configuracion.permissions.view-all', compact('roles', 'permissions'));
    }
} 
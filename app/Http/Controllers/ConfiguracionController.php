<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Empresa;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasPermissionChecks;
use Illuminate\Support\Facades\Config;

class ConfiguracionController extends Controller
{
    use HasPermissionChecks;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.ver'));
        
        $users = User::with('role')->get();
        $roles = Role::withCount('users')->get();
        $empresas = Empresa::withCount('sedes')->get();
        $sedes = Sede::with('empresa')->get();
        
        return view('configuracion.index', compact('users', 'roles', 'empresas', 'sedes'));
    }

    public function createUser()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));
        
        $roles = Role::orderBy('name')->get();
        return view('configuracion.create-user', compact('roles'));
    }

    public function storeUser(Request $request)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id']
        ]);

        return redirect()->route('configuracion.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function editUser(User $user)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));
        
        $roles = Role::orderBy('name')->get();
        return view('configuracion.edit-user', compact('user', 'roles'));
    }

    public function updateUser(Request $request, User $user)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => 'required|exists:roles,id'
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id']
        ]);

        if ($validated['password']) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        return redirect()->route('configuracion.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroyUser(User $user)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario');
        }

        $user->delete();

        return redirect()->route('configuracion.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }

    public function createRole()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));
        return view('configuracion.create-role');
    }

    public function storeRole(Request $request)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name']
        ]);

        try {
            Role::create([
                'name' => strtoupper($request->name),
                'permissions' => []
            ]);

            return redirect()->route('configuracion.index')
                ->with('success', 'Rol creado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al crear el rol: ' . $e->getMessage());
        }
    }

    public function editRole(Role $role)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));
        return view('configuracion.edit-role', compact('role'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id]
        ]);

        try {
            $role->name = strtoupper($request->name);
            $role->save();

            return redirect()->route('configuracion.index')
                ->with('success', 'Rol actualizado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el rol: ' . $e->getMessage());
        }
    }

    public function destroyRole(Role $role)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        if ($role->name === 'ADMINISTRADOR') {
            return back()->with('error', 'No se puede eliminar el rol administrador');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el rol porque hay usuarios asignados a él');
        }

        try {
            $role->delete();
            return redirect()->route('configuracion.index')
                ->with('success', 'Rol eliminado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el rol: ' . $e->getMessage());
        }
    }

    public function editPermissions(Role $role)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));
        
        $availablePermissions = Config::get('permissions');
        $currentPermissions = $role->permissions ?? [];
        
        // Convertir los permisos actuales al formato del sistema
        $formattedPermissions = [];
        foreach ($currentPermissions as $permission) {
            // Normalizar el permiso (quitar tildes y convertir a minúsculas)
            $normalizedPermission = $this->normalizeString($permission);
            
            // Extraer el módulo y la acción
            if (preg_match('/^(ver|crear|editar|eliminar|realizar|calificar|asignar|cerrar|cargar|exportar)\s+(.+)$/i', $normalizedPermission, $matches)) {
                $action = strtolower($matches[1]);
                $module = strtolower($matches[2]);
                
                // Remover 's' final si existe
                $module = rtrim($module, 's');
                
                // Manejar casos especiales
                switch ($module) {
                    case 'auditoria':
                        $formattedPermissions[] = "auditoria.$action";
                        break;
                    case 'estadistica':
                        $formattedPermissions[] = "estadisticas.$action";
                        break;
                    case 'tarifa':
                        $formattedPermissions[] = "tarifas.$action";
                        break;
                    case 'evaluacione':
                        $formattedPermissions[] = "evaluaciones.$action";
                        break;
                    case 'facturacion':
                        $formattedPermissions[] = "facturacion.$action";
                        break;
                    default:
                        $formattedPermissions[] = "$module.$action";
                }
            }
        }
        
        return view('configuracion.permissions', [
            'role' => $role,
            'availablePermissions' => $availablePermissions,
            'currentPermissions' => $formattedPermissions
        ]);
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.editar'));

        try {
            DB::beginTransaction();
            
            $permissions = [];
            $requestPermissions = $request->input('permissions', []);
            
            foreach ($requestPermissions as $permission => $value) {
                if ($value === "1" || $value === true) {
                    // Convertir el permiso al formato almacenado
                    $parts = explode('.', $permission);
                    if (count($parts) === 2) {
                        $module = $parts[0];
                        $action = $parts[1];
                        
                        // Capitalizar la primera letra de la acción
                        $action = ucfirst($action);
                        
                        // Manejar casos especiales
                        switch ($module) {
                            case 'auditoria':
                                $permissions[] = "$action Auditorías";
                                break;
                            case 'estadisticas':
                                if ($action === 'Ver') {
                                    $permissions[] = "Ver estadísticas";
                                }
                                break;
                            case 'tarifas':
                                $permissions[] = "$action tarifas";
                                break;
                            case 'evaluaciones':
                                $permissions[] = "$action evaluaciones";
                                break;
                            case 'facturacion':
                                $permissions[] = "$action facturación";
                                break;
                            case 'configuracion':
                                $permissions[] = "$action configuración";
                                break;
                        }
                    }
                }
            }
            
            // Asegurarse de que los permisos sean únicos
            $permissions = array_unique($permissions);
            
            // Actualizar los permisos del rol
            $role->permissions = $permissions;
            $role->save();
            
            DB::commit();
            
            return redirect()->route('configuracion.index')
                ->with('success', 'Permisos actualizados correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar los permisos: ' . $e->getMessage());
        }
    }

    private function normalizeString($string) {
        // Si es un array, retornamos un array con todos los elementos normalizados
        if (is_array($string)) {
            return array_map(function($item) {
                return $this->normalizeString($item);
            }, $string);
        }

        // Si no es un string, convertirlo a string
        if (!is_string($string)) {
            $string = (string)$string;
        }

        $unwanted_array = array(
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
        );
        return strtr($string, $unwanted_array);
    }

    public function viewAllPermissions()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.configuracion.ver'));
        
        $roles = Role::all();
        $permissions = Config::get('permissions');
        
        // Debug de permisos
        foreach ($roles as $role) {
            \Log::info("Permisos del rol {$role->name}:", [
                'permisos' => $role->permissions ?? []
            ]);
        }
        
        return view('configuracion.permissions.view-all', compact('roles', 'permissions'));
    }
}
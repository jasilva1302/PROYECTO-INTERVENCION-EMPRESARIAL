<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Pregunta;
use App\Models\OpcionRespuesta;
use App\Models\EvaluacionUsuario;
use App\Models\RespuestaUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasPermissionChecks;
use Illuminate\Support\Facades\Config;

class EvaluacionController extends Controller
{
    use HasPermissionChecks;

    public function index()
    {
        $this->checkPermissionAndRespond('Ver evaluaciones');
        
        $user = Auth::user();
        $canCreateEvaluaciones = $user->isAdmin() || $user->hasPermission('Crear evaluaciones');
        
        if ($user->isAdmin() || $canCreateEvaluaciones) {
            $evaluaciones = Evaluacion::with('creador')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            $evaluaciones = Evaluacion::whereHas('usuarios', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orWhere('is_active', true)
            ->where('fecha_limite', '>=', now())
            ->orderBy('fecha_limite', 'asc')
            ->paginate(10);
        }

        return view('evaluaciones.index', compact('evaluaciones', 'canCreateEvaluaciones'));
    }

    public function create()
    {
        $this->checkPermissionAndRespond('Crear evaluaciones');

        $asistentes = User::whereHas('role', function($query) {
            $query->where('name', 'ASISTENTE');
        })->get();

        return view('evaluaciones.create', compact('asistentes'));
    }

    public function store(Request $request)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.evaluaciones.crear'));

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_limite' => 'required|date|after:fecha_inicio',
            'tiempo_limite' => 'nullable|integer|min:1',
            'usuarios' => 'required|array',
            'usuarios.*' => 'exists:users,id',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.pregunta' => 'required|string',
            'preguntas.*.tipo' => 'required|in:opcion_multiple,texto_libre,verdadero_falso',
            'preguntas.*.puntaje' => 'required|integer|min:1',
            'preguntas.*.opciones' => 'required_if:preguntas.*.tipo,opcion_multiple|array|min:2',
            'preguntas.*.opciones.*.opcion' => 'required_if:preguntas.*.tipo,opcion_multiple|string',
            'preguntas.*.opciones.*.es_correcta' => 'required_if:preguntas.*.tipo,opcion_multiple|boolean'
        ]);

        try {
            DB::beginTransaction();

            $evaluacion = Evaluacion::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'creador_id' => Auth::id(),
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_limite' => $request->fecha_limite,
                'tiempo_limite' => $request->tiempo_limite,
                'is_active' => true
            ]);

            foreach ($request->preguntas as $index => $preguntaData) {
                $pregunta = $evaluacion->preguntas()->create([
                    'pregunta' => $preguntaData['pregunta'],
                    'tipo' => $preguntaData['tipo'],
                    'puntaje' => $preguntaData['puntaje'],
                    'orden' => $index + 1
                ]);

                if ($preguntaData['tipo'] === 'opcion_multiple') {
                    foreach ($preguntaData['opciones'] as $indexOpcion => $opcionData) {
                        $pregunta->opciones()->create([
                            'opcion' => $opcionData['opcion'],
                            'es_correcta' => $opcionData['es_correcta'],
                            'orden' => $indexOpcion + 1
                        ]);
                    }
                }
            }

            $evaluacion->usuarios()->attach($request->usuarios);

            DB::commit();

            return redirect()->route('evaluaciones.index')
                ->with('success', 'Evaluación creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear la evaluación: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Evaluacion $evaluacion)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.evaluaciones.ver'));

        $evaluacion->load(['preguntas.opciones', 'usuarios', 'creador']);
        
        $evaluacionUsuario = null;
        if (auth()->user()->role->name === 'ASISTENTE') {
            $evaluacionUsuario = $evaluacion->evaluacionesUsuarios()
                ->where('user_id', Auth::id())
                ->first();
        }

        return view('evaluaciones.show', compact('evaluacion', 'evaluacionUsuario'));
    }

    public function take(Evaluacion $evaluacion)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.evaluaciones.realizar'));

        // Verificar si el usuario puede realizar la evaluación
        if (!$evaluacion->isDisponibleParaUsuario(Auth::user())) {
            return redirect()->route('evaluaciones.index')
                ->with('error', 'Esta evaluación no está disponible.');
        }

        // Buscar si ya existe una evaluación en progreso o completada
        $evaluacionUsuario = EvaluacionUsuario::where([
            'evaluacion_id' => $evaluacion->id,
            'user_id' => Auth::id()
        ])->first();

        // Si ya existe una evaluación completada o calificada, redirigir
        if ($evaluacionUsuario && in_array($evaluacionUsuario->estado, ['completada', 'calificada'])) {
            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('error', 'Ya has completado esta evaluación.');
        }

        // Si ya existe una evaluación en progreso, verificar el tiempo
        if ($evaluacionUsuario && $evaluacionUsuario->estado === 'en_progreso') {
            // Calcular tiempo transcurrido
            $tiempoTranscurrido = now()->diffInSeconds($evaluacionUsuario->fecha_inicio);
            $tiempoLimiteEnSegundos = $evaluacion->tiempo_limite * 60;

            // Si se excedió el tiempo límite
            if ($tiempoTranscurrido >= $tiempoLimiteEnSegundos) {
                $evaluacionUsuario->update([
                    'estado' => 'completada',
                    'fecha_fin' => $evaluacionUsuario->fecha_inicio->addSeconds($tiempoLimiteEnSegundos)
                ]);

                return redirect()->route('evaluaciones.show', $evaluacion)
                    ->with('error', 'El tiempo límite para esta evaluación ha expirado.');
            }

            return view('evaluaciones.take', compact('evaluacion', 'evaluacionUsuario', 'tiempoTranscurrido'));
        }

        // Crear nueva evaluación
        $evaluacionUsuario = EvaluacionUsuario::create([
            'evaluacion_id' => $evaluacion->id,
            'user_id' => Auth::id(),
            'estado' => 'en_progreso',
            'fecha_inicio' => now()
        ]);

        return view('evaluaciones.take', compact('evaluacion', 'evaluacionUsuario'));
    }

    public function submit(Request $request, Evaluacion $evaluacion)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.evaluaciones.realizar'));

        try {
            // Validar primero
            $request->validate([
                'respuestas' => 'required|array',
                'respuestas.*' => 'required'
            ]);

            $evaluacionUsuario = EvaluacionUsuario::where([
                'evaluacion_id' => $evaluacion->id,
                'user_id' => Auth::id(),
            ])->where('estado', 'en_progreso')->firstOrFail();

            // Verificar el tiempo límite
            $tiempoTranscurrido = now()->diffInSeconds($evaluacionUsuario->fecha_inicio);
            $tiempoLimiteEnSegundos = $evaluacion->tiempo_limite * 60;

            if ($tiempoTranscurrido > $tiempoLimiteEnSegundos) {
                if ($request->ajax()) {
                    return response()->json(['error' => 'El tiempo límite ha expirado.'], 422);
                }
                return redirect()->route('evaluaciones.show', $evaluacion)
                    ->with('error', 'El tiempo límite ha expirado.');
            }

            DB::beginTransaction();

            // Verificar que todas las preguntas estén respondidas
            $preguntasRespondidas = array_keys($request->respuestas);
            $preguntasEvaluacion = $evaluacion->preguntas()->pluck('id')->toArray();
            
            if (count(array_diff($preguntasEvaluacion, $preguntasRespondidas)) > 0) {
                throw new \Exception('Debes responder todas las preguntas.');
            }

            foreach ($request->respuestas as $preguntaId => $respuesta) {
                $pregunta = $evaluacion->preguntas()->findOrFail($preguntaId);
                
                $respuestaUsuario = RespuestaUsuario::create([
                    'evaluacion_usuario_id' => $evaluacionUsuario->id,
                    'pregunta_id' => $preguntaId,
                    'respuesta_texto' => is_string($respuesta) ? $respuesta : null,
                    'opcion_seleccionada_id' => is_numeric($respuesta) ? $respuesta : null
                ]);

                $respuestaUsuario->calificarAutomaticamente();
            }

            $evaluacionUsuario->update([
                'estado' => 'completada',
                'fecha_fin' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Evaluación enviada exitosamente.',
                    'redirect' => route('evaluaciones.show', $evaluacion)
                ]);
            }

            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('success', 'Evaluación enviada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function grade(EvaluacionUsuario $evaluacionUsuario)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.evaluaciones.calificar'));

        $evaluacionUsuario->load(['evaluacion.preguntas.opciones', 'respuestas']);
        return view('evaluaciones.grade', compact('evaluacionUsuario'));
    }

    public function saveGrade(Request $request, EvaluacionUsuario $evaluacionUsuario)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.evaluaciones.calificar'));

        try {
            $validated = $request->validate([
            'calificaciones' => 'required|array',
                'calificaciones.*' => 'required|numeric|min:0',
                'retroalimentaciones' => 'array',
                'retroalimentacion_general' => 'nullable|string'
        ]);

            DB::beginTransaction();

            // Guardar calificaciones y retroalimentaciones individuales
            foreach ($request->calificaciones as $respuestaId => $calificacion) {
                $respuesta = RespuestaUsuario::findOrFail($respuestaId);
                
                // Verificar que la respuesta pertenece a esta evaluación
                if ($respuesta->evaluacion_usuario_id !== $evaluacionUsuario->id) {
                    throw new \Exception('Respuesta inválida');
                }

                $respuesta->update([
                    'calificacion' => $calificacion,
                    'retroalimentacion' => $request->retroalimentaciones[$respuestaId] ?? null
                ]);
            }

            // Calcular la calificación total (sobre 5.0)
            $puntajeTotal = $evaluacionUsuario->evaluacion->getPuntajeTotal();
            $puntajeObtenido = array_sum($request->calificaciones);
            $calificacionFinal = ($puntajeObtenido / $puntajeTotal) * 5;

            // Actualizar el estado y la retroalimentación general
            $evaluacionUsuario->update([
                'estado' => EvaluacionUsuario::ESTADO_CALIFICADA,
                'calificacion_total' => $puntajeObtenido,
                'calificacion' => $calificacionFinal,
                'retroalimentacion' => $request->retroalimentacion_general
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Evaluación calificada exitosamente',
                    'redirect' => route('evaluaciones.show', $evaluacionUsuario->evaluacion)
                ]);
            }

            return redirect()->route('evaluaciones.show', $evaluacionUsuario->evaluacion)
                ->with('success', 'Evaluación calificada exitosamente.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al calificar evaluación: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al calificar la evaluación: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Error al calificar la evaluación: ' . $e->getMessage());
        }
    }

    public function edit(Evaluacion $evaluacion)
    {
        $this->checkPermissionAndRespond('Editar evaluaciones');

        // Verificar si el usuario es asistente
        if (auth()->user()->hasRole('ASISTENTE')) {
            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('error', 'Los asistentes no pueden editar evaluaciones.');
        }

        // Verificar si la evaluación está calificada
        if ($evaluacion->estado === 'calificada') {
            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('error', 'No se puede editar una evaluación que ya ha sido calificada.');
        }

        $asistentes = User::whereHas('role', function($query) {
            $query->where('name', 'ASISTENTE');
        })->get();

        $evaluacion->load(['preguntas.opciones', 'usuarios']);

        return view('evaluaciones.edit', compact('evaluacion', 'asistentes'));
    }

    public function update(Request $request, Evaluacion $evaluacion)
    {
        $this->checkPermissionAndRespond('Editar evaluaciones');

        // Verificar si el usuario es asistente
        if (auth()->user()->hasRole('ASISTENTE')) {
            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('error', 'Los asistentes no pueden editar evaluaciones.');
        }

        // Verificar si la evaluación está calificada
        if ($evaluacion->estado === 'calificada') {
            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('error', 'No se puede editar una evaluación que ya ha sido calificada.');
        }

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_limite' => 'required|date|after:fecha_inicio',
            'tiempo_limite' => 'nullable|integer|min:1',
            'usuarios' => 'required|array',
            'usuarios.*' => 'exists:users,id',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.pregunta' => 'required|string',
            'preguntas.*.tipo' => 'required|in:opcion_multiple,texto_libre,verdadero_falso',
            'preguntas.*.puntaje' => 'required|integer|min:1',
            'preguntas.*.opciones' => 'required_if:preguntas.*.tipo,opcion_multiple|array|min:2',
            'preguntas.*.opciones.*.opcion' => 'required_if:preguntas.*.tipo,opcion_multiple|string',
            'preguntas.*.opciones.*.es_correcta' => 'required_if:preguntas.*.tipo,opcion_multiple|boolean'
        ]);

        try {
            DB::beginTransaction();

            $evaluacion->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_limite' => $request->fecha_limite,
                'tiempo_limite' => $request->tiempo_limite
            ]);

            // Eliminar preguntas y opciones existentes
            foreach ($evaluacion->preguntas as $pregunta) {
                $pregunta->opciones()->delete();
            }
            $evaluacion->preguntas()->delete();

            // Crear nuevas preguntas y opciones
            foreach ($request->preguntas as $index => $preguntaData) {
                $pregunta = $evaluacion->preguntas()->create([
                    'pregunta' => $preguntaData['pregunta'],
                    'tipo' => $preguntaData['tipo'],
                    'puntaje' => $preguntaData['puntaje'],
                    'orden' => $index + 1
                ]);

                if ($preguntaData['tipo'] === 'opcion_multiple') {
                    foreach ($preguntaData['opciones'] as $indexOpcion => $opcionData) {
                        $pregunta->opciones()->create([
                            'opcion' => $opcionData['opcion'],
                            'es_correcta' => $opcionData['es_correcta'],
                            'orden' => $indexOpcion + 1
                        ]);
                    }
                }
            }

            // Actualizar usuarios asignados
            $evaluacion->usuarios()->sync($request->usuarios);

            DB::commit();

            return redirect()->route('evaluaciones.show', $evaluacion)
                ->with('success', 'Evaluación actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar la evaluación: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Elimina una evaluación y todos sus datos relacionados
     */
    public function destroy(Evaluacion $evaluacion)
    {
        $this->checkPermissionAndRespond('Eliminar evaluaciones');

        // Verificar si el usuario es asistente
        if (auth()->user()->hasRole('ASISTENTE')) {
            return back()->with('error', 'Los asistentes no pueden eliminar evaluaciones.');
        }

        // Verificar si la evaluación está calificada
        if ($evaluacion->estado === 'calificada') {
            return back()->with('error', 'No se puede eliminar una evaluación que ya ha sido calificada.');
        }

        try {
            DB::beginTransaction();

            // Eliminar las respuestas de los usuarios
            foreach ($evaluacion->evaluacionesUsuarios as $evaluacionUsuario) {
                $evaluacionUsuario->respuestas()->delete();
            }
            
            // Eliminar las evaluaciones de usuarios
            $evaluacion->evaluacionesUsuarios()->delete();
            
            // Eliminar las opciones de las preguntas
            foreach ($evaluacion->preguntas as $pregunta) {
                $pregunta->opciones()->delete();
            }
            
            // Eliminar las preguntas
            $evaluacion->preguntas()->delete();
            
            // Eliminar la relación con usuarios
            $evaluacion->usuarios()->detach();
            
            // Finalmente eliminar la evaluación
            $evaluacion->delete();

            DB::commit();

            return redirect()->route('evaluaciones.index')
                ->with('success', 'Evaluación eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar la evaluación: ' . $e->getMessage());
        }
    }
}
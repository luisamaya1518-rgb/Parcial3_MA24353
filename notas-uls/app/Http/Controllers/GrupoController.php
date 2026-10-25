<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Grupo;
use Illuminate\Http\Request;

class GrupoController extends Controller
{
    private function rules(): array
    {
        return [
            'nombre'     => 'required|string|max:100',
            'ciclo_id'   => 'required|exists:ciclos,id',
            'materia_id' => 'required|exists:materias,id',
            'docente_id' => 'nullable|exists:docentes,id',
        ];
    }

    public function index(Request $request)
    {
        return Grupo::with(['ciclo', 'materia', 'docente:id,nombres,apellidos'])
            ->withCount('estudiantes')
            ->when($request->ciclo_id, fn ($q, $v) => $q->where('ciclo_id', $v))
            ->when($request->docente_id, fn ($q, $v) => $q->where('docente_id', $v))
            ->when($request->asignacion === 'sin', fn ($q) => $q->whereNull('docente_id'))
            ->when($request->asignacion === 'con', fn ($q) => $q->whereNotNull('docente_id'))
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 5));
    }

    public function store(Request $request)
    {
        $grupo = Grupo::create($request->validate($this->rules()));
        return response()->json($grupo->load(['ciclo', 'materia', 'docente']), 201);
    }

    public function show(Grupo $grupo)
    {
        return $grupo->load(['ciclo', 'materia', 'docente', 'estudiantes']);
    }

    public function update(Request $request, Grupo $grupo)
    {
        $grupo->update($request->validate($this->rules()));
        return $grupo->load(['ciclo', 'materia', 'docente']);
    }

    public function destroy(Grupo $grupo)
    {
        $grupo->delete();
        return response()->json(['message' => 'Grupo eliminado']);
    }

    // Reemplaza la lista de estudiantes del grupo
    public function asignarEstudiantes(Request $request, Grupo $grupo)
    {
        $data = $request->validate([
            'estudiantes'   => 'required|array',
            'estudiantes.*' => 'exists:estudiantes,id',
        ]);
        $grupo->estudiantes()->sync($data['estudiantes']);
        return $grupo->load('estudiantes');
    }

    // Asigna el grupo a un docente (docente_id = null lo deja sin docente)
    public function asignarDocente(Request $request, Grupo $grupo)
    {
        $data = $request->validate(['docente_id' => 'nullable|exists:docentes,id']);
        $grupo->update(['docente_id' => $data['docente_id'] ?? null]);

        return response()->json([
            'message' => $grupo->docente_id ? 'Grupo asignado al docente' : 'Docente retirado del grupo',
            'grupo'   => $grupo->load(['ciclo', 'materia', 'docente:id,nombres,apellidos']),
        ]);
    }

    // Docentes habilitados, para el selector de asignación
    public function docentesDisponibles()
    {
        return Docente::whereHas('user', fn ($q) => $q->where('estado', 'habilitado'))
            ->orderBy('apellidos')
            ->get(['id', 'nombres', 'apellidos']);
    }
}

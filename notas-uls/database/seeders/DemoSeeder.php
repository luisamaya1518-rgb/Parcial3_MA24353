<?php

namespace Database\Seeders;

use App\Models\Ciclo;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $ciclo = Ciclo::create([
            'nombre' => 'Ciclo 02-2026', 'fecha_inicio' => '2026-07-20', 'fecha_fin' => '2026-12-05',
        ]);

        $materias = collect([
            'Programación Web', 'Base de Datos', 'Matemática Discreta', 'Redes de Computadoras', 'Ingeniería de Software',
        ])->map(fn ($n) => Materia::create(['nombre' => $n]));

        $carlos = $this->docente('Carlos', 'Pérez', 'docente@uls.edu.sv', '70001111', 'Informática');
        $this->docente('María', 'Batres', 'maria@uls.edu.sv', '70002222', 'Bases de datos');

        $nombres = ['Ana', 'Luis', 'Sofía', 'Mateo', 'Valeria', 'Diego', 'Camila', 'Andrés', 'Lucía', 'Pablo',
                    'Daniela', 'Jorge', 'Paola', 'Kevin', 'Gabriela', 'Oscar', 'Fernanda', 'Rafael', 'Karla', 'Samuel'];
        $apellidos = ['García', 'Martínez', 'López', 'Hernández', 'Rodríguez', 'Pérez', 'Flores', 'Ramos', 'Cruz', 'Mejía'];

        $estudiantes = collect(range(0, 99))->map(fn ($i) => Estudiante::create([
            'nombres'   => $nombres[$i % 20],
            'apellidos' => $apellidos[intdiv($i, 10)] . ' ' . $apellidos[(intdiv($i, 10) + 1 + $i % 9) % 10],
            'carnet'    => 'MA' . (24100 + $i),
        ]));

        for ($g = 0; $g < 20; $g++) {
            $grupo = Grupo::create([
                'nombre'     => 'Grupo ' . str_pad((string) ($g + 1), 2, '0', STR_PAD_LEFT),
                'ciclo_id'   => $ciclo->id,
                'materia_id' => $materias[$g % 5]->id,
                'docente_id' => $g < 3 ? $carlos->id : null,
            ]);

            // 10 estudiantes por grupo
            $ids = collect(range(0, 9))->map(fn ($k) => $estudiantes[($g * 5 + $k) % 100]->id);
            $grupo->estudiantes()->sync($ids);

            // Solo los grupos de Carlos traen notas de ejemplo
            if ($g < 3) {
                foreach ($ids as $estudianteId) {
                    $n = [rand(40, 100) / 10, rand(40, 100) / 10, rand(40, 100) / 10];
                    $prom = round(array_sum($n) / 3, 2);
                    Nota::create([
                        'estudiante_id' => $estudianteId, 'grupo_id' => $grupo->id,
                        'nota1' => $n[0], 'nota2' => $n[1], 'nota3' => $n[2],
                        'promedio' => $prom, 'estado' => $prom >= 6 ? 'aprobado' : 'reprobado',
                        'fecha' => now()->subMonths(rand(0, 3))->toDateString(),
                    ]);
                }
            }
        }
    }

    private function docente(string $nombres, string $apellidos, string $email, string $tel, string $esp): Docente
    {
        $user = User::create([
            'name' => "$nombres $apellidos", 'email' => $email, 'password' => 'Docente123*',
            'role' => 'docente', 'must_change_password' => false,
        ]);

        return Docente::create([
            'user_id' => $user->id, 'nombres' => $nombres, 'apellidos' => $apellidos,
            'telefono' => $tel, 'especialidad' => $esp,
        ]);
    }
}

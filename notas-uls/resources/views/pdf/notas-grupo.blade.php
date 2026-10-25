<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #313945; }
  h1 { font-size: 16px; color: #2D52A8; margin-bottom: 4px; }
  .info td { padding: 2px 8px 2px 0; }
  table.notas { width: 100%; border-collapse: collapse; margin-top: 14px; }
  table.notas th { background: #2D52A8; color: #fff; padding: 6px; text-align: left; }
  table.notas td { padding: 5px 6px; border-bottom: 1px solid #ddd; }
  .ap { color: #2e9e6b; font-weight: bold; }
  .rp { color: #EF5350; font-weight: bold; }
  .pie { margin-top: 16px; font-size: 9px; color: #777; }
</style>
</head>
<body>
  <h1>Cuadro de notas - {{ $grupo->materia->nombre }}</h1>
  <table class="info">
    <tr><td><b>Institución:</b></td><td>Universidad Luterana Salvadoreña</td></tr>
    <tr><td><b>Ciclo:</b></td><td>{{ $grupo->ciclo->nombre }}</td></tr>
    <tr><td><b>Grupo:</b></td><td>{{ $grupo->nombre }}</td></tr>
    <tr><td><b>Docente:</b></td><td>{{ $grupo->docente ? $grupo->docente->nombres . ' ' . $grupo->docente->apellidos : 'Sin asignar' }}</td></tr>
  </table>

  <table class="notas">
    <thead>
      <tr><th>Carnet</th><th>Estudiante</th><th>Nota 1</th><th>Nota 2</th><th>Nota 3</th><th>Promedio</th><th>Estado</th></tr>
    </thead>
    <tbody>
      @foreach ($filas as $f)
        <tr>
          <td>{{ $f['carnet'] }}</td>
          <td>{{ $f['estudiante'] }}</td>
          <td>{{ $f['nota1'] }}</td>
          <td>{{ $f['nota2'] }}</td>
          <td>{{ $f['nota3'] }}</td>
          <td><b>{{ $f['promedio'] }}</b></td>
          <td class="{{ $f['estado'] === 'Aprobado' ? 'ap' : ($f['estado'] === 'Reprobado' ? 'rp' : '') }}">{{ $f['estado'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <p class="pie">Generado por {{ $usuario }} el {{ $fecha }} | Registros: {{ count($filas) }}</p>
</body>
</html>

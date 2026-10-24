# Sistema de Gestión de Notas ULS

API RESTful con **Laravel** + SPA con **Vue 3 + Vuetify 3**. Login con 2FA (QR solo la primera vez), registro de docentes con envío de credenciales por correo, registro de notas, informe PDF con visor, exportación Excel/CSV y gráfico de aprobados/reprobados por mes.

## Estructura

```
Parcial3_MA24353/
├── notas-uls/   # Backend Laravel (composer.json, vendor)
└── frontend/    # Frontend Vue (package.json, node_modules)
```

## Requisitos

- PHP 8.2+ con extensiones `gd`, `mbstring`, `sqlite3`, `pdo_sqlite`
- Composer
- Node.js 18+ y npm

## Backend

```powershell
cd notas-uls
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

API disponible en `http://127.0.0.1:8000/api`.

### Correo (envío de credenciales a docentes)

En `notas-uls/.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=clave_de_aplicacion_de_16_letras
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="tu_correo@gmail.com"
MAIL_FROM_NAME="Sistema de Notas ULS"
```

Para pruebas sin enviar, usar `MAIL_MAILER=log` (los correos quedan en `storage/logs/laravel.log`). Después de cambiar el `.env`: `php artisan config:clear`.

## Frontend

```powershell
cd frontend
npm install
npm run dev
```

Aplicación en `http://localhost:5173` (origen permitido por CORS).

## Credenciales de prueba

| Rol           | Correo                | Contraseña  |
|---------------|-----------------------|-------------|
| Administrador | admin@uls.edu.sv      | Admin123*   |
| Docente       | docente@uls.edu.sv    | (ver seeder / correo de credenciales) |

## Flujo de autenticación

1. `POST /api/login`
   - Primer ingreso (2FA sin confirmar): devuelve token temporal `pre-2fa` (10 min), `requires_qr: true` y el QR.
   - Ingresos siguientes: devuelve directamente el token final con `two_factor_omitido: true`.
2. `POST /api/2fa/verify`: recibe `code` de 6 dígitos, confirma el 2FA y devuelve el token final.
3. Si `must_change_password` es `true`, el frontend exige el cambio con `POST /api/change-password`.

Enviar siempre el header `Accept: application/json`.

## Reglas de negocio

- Un docente solo ve y edita notas de sus propios grupos (`grupos.docente_id`).
- Promedio = (nota1 + nota2 + nota3) / 3. **Aprobado** con promedio ≥ 6.0.
- Listados paginados de 5 registros por página (`v-pagination`).

## Endpoints principales

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/mis-grupos` | Grupos del usuario (docente: solo los suyos) |
| GET | `/api/grupos/{grupo}/notas` | Estudiantes con notas (paginado) |
| PUT | `/api/grupos/{grupo}/notas/{estudiante}` | Registrar/actualizar nota |
| GET | `/api/grupos/{grupo}/notas/pdf` | Cuadro de notas en PDF |
| GET | `/api/grupos/{grupo}/notas/excel` | Cuadro de notas en Excel |
| GET | `/api/grafico?mes=&anio=` | Aprobados y reprobados del mes |
| CRUD | `/api/docentes` | Solo administrador |

## Base de datos

Diagrama físico en `diagrama-bd.mmd` (Mermaid). Se puede ver y exportar a imagen en <https://mermaid.live>.

## Tecnologías

Laravel, Sanctum, DomPDF, Google2FA, bacon-qrcode, Maatwebsite Excel, SQLite · Vue 3, Vite, Vuetify 3, Vue Router, Pinia, Axios, Chart.js (vue-chartjs), vue-pdf-embed.

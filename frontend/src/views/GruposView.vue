<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import api, { errorMsg } from '@/services/api'
import type { Paginated } from '@/types'

interface DocenteMini { id: number; nombres: string; apellidos: string }
interface Opcion { title: string; value: number }
interface Estudiante { id: number; nombres: string; apellidos: string; carnet: string }
interface GrupoRow {
  id: number
  nombre: string
  docente_id: number | null
  estudiantes_count: number
  ciclo: { id: number; nombre: string }
  materia: { id: number; nombre: string }
  docente: DocenteMini | null
}

const PER_PAGE = 5

// ---- listado y filtros ----
const grupos = ref<GrupoRow[]>([])
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const loading = ref(false)
const ciclos = ref<Opcion[]>([])
const docentes = ref<Opcion[]>([])
const filtros = reactive<{ ciclo_id: number | null; docente_id: number | null; asignacion: string | null }>({
  ciclo_id: null, docente_id: null, asignacion: null,
})
const asignaciones = [
  { title: 'Sin docente asignado', value: 'sin' },
  { title: 'Con docente asignado', value: 'con' },
]

async function cargarCatalogos(): Promise<void> {
  try {
    const [c, d] = await Promise.all([
      api.get<{ id: number; nombre: string }[]>('/ciclos', { params: { all: 1 } }),
      api.get<DocenteMini[]>('/docentes-lista'),
    ])
    ciclos.value = c.data.map((x) => ({ title: x.nombre, value: x.id }))
    docentes.value = d.data.map((x) => ({ title: `${x.nombres} ${x.apellidos}`, value: x.id }))
  } catch (e) {
    avisar(errorMsg(e), 'error')
  }
}

async function cargar(): Promise<void> {
  loading.value = true
  try {
    const { data } = await api.get<Paginated<GrupoRow>>('/grupos', {
      params: {
        page: page.value,
        per_page: PER_PAGE,
        ciclo_id: filtros.ciclo_id || undefined,
        docente_id: filtros.docente_id || undefined,
        asignacion: filtros.asignacion || undefined,
      },
    })
    grupos.value = data.data
    lastPage.value = data.last_page
    total.value = data.total
  } catch (e) {
    avisar(errorMsg(e), 'error')
  } finally {
    loading.value = false
  }
}

function filtrar(): void {
  page.value = 1
  cargar()
}

function limpiar(): void {
  filtros.ciclo_id = null
  filtros.docente_id = null
  filtros.asignacion = null
  filtrar()
}

// ---- asignar o cambiar docente ----
const dlgAsignar = ref(false)
const grupoSel = ref<GrupoRow | null>(null)
const docenteSel = ref<number | null>(null)
const guardando = ref(false)

function abrirAsignar(g: GrupoRow): void {
  grupoSel.value = g
  docenteSel.value = g.docente_id
  dlgAsignar.value = true
}

async function guardarAsignacion(): Promise<void> {
  if (!grupoSel.value || docenteSel.value === null) return
  guardando.value = true
  try {
    await api.patch(`/grupos/${grupoSel.value.id}/docente`, { docente_id: docenteSel.value })
    avisar('Grupo asignado al docente', 'success')
    dlgAsignar.value = false
    await cargar()
  } catch (e) {
    avisar(errorMsg(e), 'error')
  } finally {
    guardando.value = false
  }
}

// ---- quitar docente ----
const dlgQuitar = ref(false)
const grupoQuitar = ref<GrupoRow | null>(null)

function pedirQuitar(g: GrupoRow): void {
  grupoQuitar.value = g
  dlgQuitar.value = true
}

async function quitarDocente(): Promise<void> {
  if (!grupoQuitar.value) return
  guardando.value = true
  try {
    await api.patch(`/grupos/${grupoQuitar.value.id}/docente`, { docente_id: null })
    avisar('Docente retirado del grupo', 'success')
    dlgQuitar.value = false
    await cargar()
  } catch (e) {
    avisar(errorMsg(e), 'error')
  } finally {
    guardando.value = false
  }
}

// ---- ver estudiantes del grupo ----
const dlgEst = ref(false)
const grupoVer = ref<GrupoRow | null>(null)
const estudiantes = ref<Estudiante[]>([])
const cargandoEst = ref(false)

async function verEstudiantes(g: GrupoRow): Promise<void> {
  grupoVer.value = g
  estudiantes.value = []
  dlgEst.value = true
  cargandoEst.value = true
  try {
    const { data } = await api.get<{ estudiantes: Estudiante[] }>(`/grupos/${g.id}`)
    estudiantes.value = data.estudiantes
  } catch (e) {
    avisar(errorMsg(e), 'error')
  } finally {
    cargandoEst.value = false
  }
}

// ---- avisos ----
const snack = reactive({ show: false, texto: '', color: 'success' })
function avisar(texto: string, color: 'success' | 'error' | 'warning'): void {
  Object.assign(snack, { show: true, texto, color })
}

onMounted(() => {
  cargarCatalogos()
  cargar()
})
</script>

<template>
  <v-card class="page-card pa-4 mb-4" elevation="0" style="border: 2px solid #2D52A8">
    <h2 class="t6 text-secondary font-weight-bold text-center">Grupos y asignación de docentes</h2>
  </v-card>

  <v-card class="page-card pa-4" elevation="0">
    <!-- Filtros -->
    <v-row dense class="align-center">
      <v-col cols="12" sm="3">
        <v-select v-model="filtros.ciclo_id" :items="ciclos" label="Ciclo" clearable hide-details />
      </v-col>
      <v-col cols="12" sm="3">
        <v-select v-model="filtros.docente_id" :items="docentes" label="Docente" clearable hide-details />
      </v-col>
      <v-col cols="12" sm="3">
        <v-select v-model="filtros.asignacion" :items="asignaciones" label="Asignación" clearable hide-details />
      </v-col>
      <v-col cols="12" sm="3" class="d-flex ga-2 justify-end">
        <v-btn color="secondary" @click="filtrar">Filtrar</v-btn>
        <v-btn color="grey" @click="limpiar">Limpiar</v-btn>
      </v-col>
    </v-row>

    <!-- Tabla -->
    <v-progress-linear v-if="loading" indeterminate color="secondary" class="mt-4" />
    <v-table class="mt-4" density="comfortable">
      <thead>
        <tr class="bg-secondary">
          <th class="text-white">Grupo</th>
          <th class="text-white">Materia</th>
          <th class="text-white">Ciclo</th>
          <th class="text-white text-center">Estudiantes</th>
          <th class="text-white">Docente</th>
          <th class="text-white text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="g in grupos" :key="g.id">
          <td class="font-weight-bold">{{ g.nombre }}</td>
          <td>{{ g.materia.nombre }}</td>
          <td>{{ g.ciclo.nombre }}</td>
          <td class="text-center">{{ g.estudiantes_count }}</td>
          <td>
            <span v-if="g.docente">{{ g.docente.nombres }} {{ g.docente.apellidos }}</span>
            <v-chip v-else color="warning" size="small" label class="text-black font-weight-bold">Sin asignar</v-chip>
          </td>
          <td class="text-center text-no-wrap">
            <v-btn icon="mdi-eye" size="small" variant="text" color="secondary" title="Ver estudiantes" @click="verEstudiantes(g)" />
            <v-btn
              icon="mdi-account-switch" size="small" variant="text" color="secondary"
              :title="g.docente ? 'Cambiar docente' : 'Asignar docente'" @click="abrirAsignar(g)"
            />
            <v-btn
              v-if="g.docente" icon="mdi-account-remove" size="small" variant="text" color="error"
              title="Quitar docente" @click="pedirQuitar(g)"
            />
          </td>
        </tr>
        <tr v-if="!loading && grupos.length === 0">
          <td colspan="6" class="text-center text-medium-emphasis py-6">No se encontraron grupos</td>
        </tr>
      </tbody>
    </v-table>

    <!-- Paginación -->
    <div class="d-flex align-center justify-space-between flex-wrap mt-4">
      <span class="sub2 text-medium-emphasis">{{ total }} grupo(s) · {{ PER_PAGE }} por página</span>
      <v-pagination
        v-model="page" :length="lastPage" :total-visible="5"
        density="comfortable" color="secondary" rounded="circle"
        @update:model-value="cargar"
      />
    </div>
  </v-card>

  <!-- Asignar docente -->
  <v-dialog v-model="dlgAsignar" max-width="480" persistent>
    <v-card rounded="lg">
      <v-toolbar color="secondary" density="compact" :title="`Asignar docente: ${grupoSel?.nombre ?? ''}`" />
      <v-card-text class="pa-6">
        <p class="sub2 mb-4">{{ grupoSel?.materia.nombre }} · {{ grupoSel?.estudiantes_count }} estudiantes</p>
        <v-select
          v-model="docenteSel" :items="docentes" label="Docente"
          :rules="[(v: number | null) => v !== null || 'Selecciona un docente']"
        />
      </v-card-text>
      <v-card-actions class="bg-secondary justify-center pa-3">
        <v-btn variant="outlined" color="white" :loading="guardando" :disabled="docenteSel === null" @click="guardarAsignacion">Guardar</v-btn>
        <v-btn variant="outlined" color="white" @click="dlgAsignar = false">Cancelar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Quitar docente -->
  <v-dialog v-model="dlgQuitar" max-width="420">
    <v-card rounded="lg" class="pa-2">
      <v-card-title class="t6">Quitar docente</v-card-title>
      <v-card-text>
        ¿Quitar a {{ grupoQuitar?.docente?.nombres }} {{ grupoQuitar?.docente?.apellidos }} del {{ grupoQuitar?.nombre }}?
        El docente dejará de ver este grupo; las notas ya registradas se conservan.
      </v-card-text>
      <v-card-actions class="justify-end">
        <v-btn variant="text" @click="dlgQuitar = false">Cancelar</v-btn>
        <v-btn color="error" :loading="guardando" @click="quitarDocente">Quitar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Estudiantes del grupo -->
  <v-dialog v-model="dlgEst" max-width="560">
    <v-card rounded="lg">
      <v-toolbar color="secondary" density="compact" :title="`Estudiantes: ${grupoVer?.nombre ?? ''}`" />
      <v-card-text class="pa-4">
        <v-progress-linear v-if="cargandoEst" indeterminate color="secondary" />
        <v-table density="compact">
          <thead>
            <tr><th>Carnet</th><th>Estudiante</th></tr>
          </thead>
          <tbody>
            <tr v-for="e in estudiantes" :key="e.id">
              <td>{{ e.carnet }}</td>
              <td>{{ e.apellidos }}, {{ e.nombres }}</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
      <v-card-actions class="justify-center pa-3">
        <v-btn color="secondary" @click="dlgEst = false">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-snackbar v-model="snack.show" :color="snack.color" timeout="4000" location="top right">
    <span :class="snack.color === 'warning' ? 'text-black' : ''">{{ snack.texto }}</span>
  </v-snackbar>
</template>
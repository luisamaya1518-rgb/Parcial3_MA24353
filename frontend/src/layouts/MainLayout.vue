<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ChangePasswordDialog from '@/components/ChangePasswordDialog.vue'

interface MenuItem {
  title: string
  icon: string
  to: string
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const rail = ref(true)

const menu = computed<MenuItem[]>(() => {
  const items: MenuItem[] = [{ title: 'Inicio', icon: 'mdi-home', to: '/' }]
  if (auth.role === 'admin') {
    items.push(
      { title: 'Docentes', icon: 'mdi-account-tie', to: '/docentes' },
      { title: 'Grupos', icon: 'mdi-account-group', to: '/grupos' },
    )
  }
  items.push(
    { title: 'Registro de notas', icon: 'mdi-pencil-box-multiple', to: '/notas/registro' },
    { title: 'Impresión de notas', icon: 'mdi-printer', to: '/notas' },
    { title: 'Reportes', icon: 'mdi-chart-bar', to: '/reportes' },
  )
  return items
})

const rolTexto = computed(() => (auth.role === 'admin' ? 'Administrador' : 'Docente'))

async function salir(): Promise<void> {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <v-navigation-drawer permanent :rail="rail" color="sidebar" width="240">
    <v-list nav density="comfortable">
      <v-list-item prepend-icon="mdi-menu" @click="rail = !rail" />
      <v-list-item
        v-for="m in menu" :key="m.to"
        :prepend-icon="m.icon" :title="m.title" :to="m.to" exact rounded="lg"
      />
    </v-list>

    <template #append>
      <v-list nav density="comfortable">
        <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" rounded="lg" @click="salir" />
      </v-list>
    </template>
  </v-navigation-drawer>

  <v-main class="bg-background">
    <v-container fluid class="pa-4">
      <!-- Encabezado: ventana a la izquierda, usuario a la derecha -->
      <v-sheet rounded="lg" class="d-flex align-center px-5 py-3 mb-4" elevation="0">
        <h1 class="t5 font-weight-regular">{{ route.meta.title }}</h1>

        <v-spacer />

        <div class="d-flex align-center ga-3">
          <div class="text-right d-none d-sm-block">
            <div class="sub2 font-weight-bold">{{ auth.user?.name }}</div>
            <div class="text-caption text-medium-emphasis">{{ rolTexto }}</div>
          </div>
          <v-menu>
            <template #activator="{ props }">
              <v-btn v-bind="props" icon="mdi-account-circle" variant="text" color="secondary" />
            </template>
            <v-list>
              <v-list-item prepend-icon="mdi-logout" title="Cerrar sesión" @click="salir" />
            </v-list>
          </v-menu>
        </div>
      </v-sheet>

      <router-view />
    </v-container>
  </v-main>

  <ChangePasswordDialog />
</template>
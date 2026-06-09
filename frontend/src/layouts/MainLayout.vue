<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as authService from '@/services/auth.service';
import ThemeSelector from '@/components/ThemeSelector.vue';
import NotificationBell from '@/components/common/NotificationBell.vue';
import NotificationTray from '@/components/common/NotificationTray.vue';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const authStore = useAuthStore();
const { loader } = storeToRefs(appStore);
const { authUser, currentProject, isSuperAdmin } = storeToRefs(authStore);

const drawer = ref(true);
const rail = ref(false);

function toggleDrawer() {
  if (rail.value) {
    rail.value = false;
  } else {
    drawer.value = !drawer.value;
  }
}

// ── Menú base (siempre visible) ──────────────────────────────────────────────
const baseMenu = [
  { title: 'Dashboard', icon: 'ri-dashboard-line', name: 'dashboard' },
  { title: 'Proyectos', icon: 'ri-folders-line', name: 'projects' },
  { title: 'Notificaciones', icon: 'ri-notification-3-line', name: 'notifications' },
];

// ── Menú contextual del proyecto activo ──────────────────────────────────────
const projectMenu = computed(() => {
  if (!currentProject.value) return [];
  const pid = currentProject.value.id;
  return [
    { title: 'Vista general', icon: 'ri-information-line', name: 'project-detail', params: { projectId: pid } },
    { title: 'Objetivos', icon: 'ri-flag-line', name: 'objectives', params: { projectId: pid } },
    { title: 'Fases', icon: 'ri-timeline-view', name: 'phases', params: { projectId: pid } },
    { title: 'Planes', icon: 'ri-calendar-schedule-line', name: 'plans', params: { projectId: pid } },
    { title: 'Tareas', icon: 'ri-checkbox-circle-line', name: 'tasks', params: { projectId: pid } },
    { title: 'Tickets', icon: 'ri-coupon-line', name: 'tickets', params: { projectId: pid } },
    { title: 'Riesgos', icon: 'ri-error-warning-line', name: 'risks', params: { projectId: pid } },
    { title: 'Bloqueadores', icon: 'ri-forbid-line', name: 'blockers', params: { projectId: pid } },
    { title: 'Entregables', icon: 'ri-archive-line', name: 'deliverables', params: { projectId: pid } },
    { title: 'Hitos', icon: 'ri-map-pin-line', name: 'milestones', params: { projectId: pid } },
    { title: 'Miembros', icon: 'ri-group-line', name: 'members', params: { projectId: pid } },
    { title: 'Métricas', icon: 'ri-bar-chart-line', name: 'metrics', params: { projectId: pid } },
  ];
});

// ── Menú admin (solo superadmin) ─────────────────────────────────────────────
const adminMenu = computed(() => {
  if (!isSuperAdmin.value) return [];
  return [
    { title: 'Dashboard Admin', icon: 'ri-vip-crown-line', name: 'admin' },
    { title: 'Usuarios', icon: 'ri-team-fill', name: 'admin-users' },
    { title: 'Roles', icon: 'ri-key-line', name: 'roles' },
  ];
});

function isActive(name: string) {
  return route.name === name;
}

function navigate(name: string, params?: Record<string, unknown>) {
  router.push({ name, params });
}

const themeDialogOpen = ref(false);

async function handleLogout() {
  loader.value = true;
  await authService.logout();
  authStore.clearSession();
  loader.value = false;
  router.push({ name: 'login' });
}
</script>

<template>
  <!-- ── App Bar ───────────────────────────────────────────────── -->
  <VAppBar flat border="b" class="app-bar">
    <VAppBarNavIcon @click="toggleDrawer" />
    <VAppBarTitle class="text-primary">Gestión de Proyectos</VAppBarTitle>
    <VSpacer />
    <VChip v-if="currentProject" variant="tonal" color="primary" size="small" class="me-2 d-none d-md-flex"
      :prepend-icon="'ri-folder-open-line'" :to="{ name: 'project-detail', params: { projectId: currentProject.id } }">
      {{ currentProject.name }}
    </VChip>
    <NotificationBell />
    <VMenu location="bottom end" :offset="8">
      <template #activator="{ props }">
        <VBtn v-bind="props" icon variant="text" class="ms-2">
          <VAvatar size="34" color="primary" variant="tonal">
            <span class="text-caption font-weight-bold">
              {{(authUser?.name ?? 'U').split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase()}}
            </span>
          </VAvatar>
        </VBtn>
      </template>
      <VList density="compact" min-width="200">
        <VListItem :title="authUser?.name ?? 'Usuario'" :subtitle="authUser?.email" class="text-wrap">
          <template #prepend>
            <VAvatar size="36" color="primary" variant="tonal">
              <span class="text-caption font-weight-bold">
                {{ (authUser?.name ?? 'U').charAt(0).toUpperCase() }}
              </span>
            </VAvatar>
          </template>
        </VListItem>
        <VDivider />
        <VListItem prepend-icon="ri-user-line" title="Mi perfil" :active="isActive('profile')"
          @click="navigate('profile')" />
        <VListItem prepend-icon="ri-palette-line" title="Apariencia" @click="themeDialogOpen = true" />
        <VListItem prepend-icon="ri-logout-box-line" title="Cerrar sesión" @click="handleLogout" base-color="error" />
      </VList>
    </VMenu>
  </VAppBar>

  <!-- ── Sidebar ────────────────────────────────────────────────── -->
  <VNavigationDrawer v-model="drawer" :rail="rail">

    <!-- Header -->
    <VListItem prepend-icon="ri-bar-chart-horizontal-line" title="Gestión de Proyectos" nav>
      <template #append>
        <VBtn :icon="rail ? 'ri-arrow-right-s-line' : 'ri-arrow-left-s-line'" variant="text" @click="rail = !rail" />
      </template>
    </VListItem>

    <VDivider />

    <!-- Usuario -->
    <VListItem :prepend-icon="'ri-user-3-line'" :title="authUser?.name ?? 'Usuario'" :subtitle="authUser?.email" nav
      class="my-1" />

    <VDivider />

    <!-- Menú base -->
    <VList density="compact" nav>
      <VListItem v-for="item in baseMenu" :key="item.name" :prepend-icon="item.icon" :title="item.title"
        :active="isActive(item.name)" @click="navigate(item.name)" active-color="primary" />
    </VList>

    <!-- Proyecto activo -->
    <template v-if="currentProject">
      <VDivider />
      <VListSubheader v-if="!rail" class="text-caption font-weight-bold px-4 pt-2">
        <VIcon icon="ri-folder-open-line" size="14" class="me-1" />
        {{ currentProject.name }}
      </VListSubheader>
      <VList density="compact" nav>
        <VListItem v-for="item in projectMenu" :key="item.name" :prepend-icon="item.icon" :title="item.title"
          :active="isActive(item.name)" @click="navigate(item.name, item.params)" active-color="primary" />
      </VList>
    </template>

    <!-- Admin -->
    <template v-if="adminMenu.length">
      <VDivider />
      <VListSubheader v-if="!rail" class="text-caption font-weight-bold px-4 pt-2">
        Administración
      </VListSubheader>
      <VList density="compact" nav>
        <VListItem v-for="item in adminMenu" :key="item.name" :prepend-icon="item.icon" :title="item.title"
          :active="isActive(item.name)" @click="navigate(item.name)" active-color="primary" />
      </VList>
    </template>

    <!-- Spacer + Logout -->
    <template #append>
      <VDivider />
      <VList density="compact" nav class="pb-2">
        <VListItem prepend-icon="ri-user-line" title="Mi perfil" :active="isActive('profile')"
          @click="navigate('profile')" active-color="primary" />
        <VListItem prepend-icon="ri-palette-line" title="Apariencia" @click="themeDialogOpen = true"
          active-color="primary" />
        <VListItem prepend-icon="ri-logout-box-line" title="Cerrar sesión" @click="handleLogout" base-color="error" />
      </VList>
    </template>

  </VNavigationDrawer>

  <!-- ── Theme selector dialog ───────────────────────────────────── -->
  <VDialog v-model="themeDialogOpen" max-width="440" scrollable>
    <VCard>
      <VCardItem>
        <VCardTitle class="d-flex align-center gap-2">
          <VIcon icon="ri-palette-line" color="primary" />
          Apariencia
        </VCardTitle>
        <template #append>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="themeDialogOpen = false" />
        </template>
      </VCardItem>
      <VDivider />
      <VCardText class="pa-0">
        <ThemeSelector />
      </VCardText>
    </VCard>
  </VDialog>

  <!-- ── Main content ───────────────────────────────────────────── -->
  <VMain>
    <VContainer fluid class="pa-6">
      <RouterView />
    </VContainer>
  </VMain>

  <!-- Bandeja de notificaciones (teleport al body) -->
  <NotificationTray />

</template>

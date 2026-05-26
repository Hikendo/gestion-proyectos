<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as authService from '@/services/auth.service';

const route  = useRoute();
const router = useRouter();
const appStore  = useAppStore();
const authStore = useAuthStore();
const { loader, snackbar } = storeToRefs(appStore);
const { authUser, currentProject, isSuperAdmin } = storeToRefs(authStore);

const drawer = ref(true);
const rail   = ref(false);

function toggleDrawer() {
    if (rail.value) {
        rail.value = false;
    } else {
        drawer.value = !drawer.value;
    }
}

// ── Menú base (siempre visible) ──────────────────────────────────────────────
const baseMenu = [
    { title: 'Dashboard',  icon: 'mdi-view-dashboard', name: 'dashboard' },
    { title: 'Proyectos',  icon: 'mdi-folder-multiple', name: 'projects' },
];

// ── Menú contextual del proyecto activo ──────────────────────────────────────
const projectMenu = computed(() => {
    if (!currentProject.value) return [];
    const pid = currentProject.value.id;
    return [
        { title: 'Vista general',  icon: 'mdi-information-outline',   name: 'project-detail',  params: { projectId: pid } },
        { title: 'Objetivos',      icon: 'mdi-flag-checkered',         name: 'objectives',      params: { projectId: pid } },
        { title: 'Fases',          icon: 'mdi-timeline-outline',       name: 'phases',          params: { projectId: pid } },
        { title: 'Planes',         icon: 'mdi-calendar-clock',         name: 'plans',           params: { projectId: pid } },
        { title: 'Tareas',         icon: 'mdi-check-circle-outline',   name: 'tasks',           params: { projectId: pid } },
        { title: 'Tickets',        icon: 'mdi-ticket-outline',         name: 'tickets',         params: { projectId: pid } },
        { title: 'Riesgos',        icon: 'mdi-alert-circle-outline',   name: 'risks',           params: { projectId: pid } },
        { title: 'Bloqueadores',   icon: 'mdi-block-helper',           name: 'blockers',        params: { projectId: pid } },
        { title: 'Entregables',    icon: 'mdi-package-variant-closed', name: 'deliverables',    params: { projectId: pid } },
        { title: 'Hitos',          icon: 'mdi-map-marker-check',       name: 'milestones',      params: { projectId: pid } },
        { title: 'Miembros',       icon: 'mdi-account-group-outline',  name: 'members',         params: { projectId: pid } },
        { title: 'Métricas',       icon: 'mdi-chart-bar',              name: 'metrics',         params: { projectId: pid } },
    ];
});

// ── Menú admin (solo superadmin) ─────────────────────────────────────────────
const adminMenu = computed(() => {
    if (!isSuperAdmin.value) return [];
    return [
        { title: 'Dashboard Admin',  icon: 'mdi-shield-crown-outline', name: 'admin' },
        { title: 'Usuarios',         icon: 'mdi-account-multiple',     name: 'admin-users' },
        { title: 'Roles',            icon: 'mdi-shield-key-outline',   name: 'roles' },
    ];
});

function isActive(name: string) {
    return route.name === name;
}

function navigate(name: string, params?: Record<string, unknown>) {
    router.push({ name, params });
}

async function handleLogout() {
    loader.value = true;
    await authService.logout();
    authStore.clearSession();
    loader.value = false;
    router.push({ name: 'login' });
}
</script>

<template>
  <VApp>
    <!-- ── Snackbar global ─────────────────────────────────────────── -->
    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      location="bottom right"
      :timeout="3500"
    >
      {{ snackbar.text }}
      <template #actions>
        <VBtn icon="mdi-close" variant="text" @click="snackbar.show = false" />
      </template>
    </VSnackbar>

    <!-- ── Loader global ──────────────────────────────────────────── -->
    <VOverlay :model-value="loader" class="align-center justify-center" persistent>
      <VProgressCircular indeterminate color="primary" size="64" />
    </VOverlay>

    <!-- ── App Bar ───────────────────────────────────────────────── -->
    <VAppBar flat border="b">
      <VAppBarNavIcon @click="toggleDrawer" />
      <VAppBarTitle>Gestión de Proyectos</VAppBarTitle>
    </VAppBar>

    <!-- ── Sidebar ────────────────────────────────────────────────── -->
    <VNavigationDrawer v-model="drawer" :rail="rail">

      <!-- Header -->
      <VListItem
        prepend-icon="mdi-chart-gantt"
        title="Gestión de Proyectos"
        nav
      >
        <template #append>
          <VBtn
            :icon="rail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
            variant="text"
            @click="rail = !rail"
          />
        </template>
      </VListItem>

      <VDivider />

      <!-- Usuario -->
      <VListItem
        :prepend-icon="'mdi-account-circle-outline'"
        :title="authUser?.name ?? 'Usuario'"
        :subtitle="authUser?.email"
        nav
        class="my-1"
      />

      <VDivider />

      <!-- Menú base -->
      <VList density="compact" nav>
        <VListItem
          v-for="item in baseMenu"
          :key="item.name"
          :prepend-icon="item.icon"
          :title="item.title"
          :active="isActive(item.name)"
          @click="navigate(item.name)"
          active-color="primary"
        />
      </VList>

      <!-- Proyecto activo -->
      <template v-if="currentProject">
        <VDivider />
        <VListSubheader v-if="!rail" class="text-caption font-weight-bold px-4 pt-2">
          <VIcon icon="mdi-folder-open" size="14" class="me-1" />
          {{ currentProject.name }}
        </VListSubheader>
        <VList density="compact" nav>
          <VListItem
            v-for="item in projectMenu"
            :key="item.name"
            :prepend-icon="item.icon"
            :title="item.title"
            :active="isActive(item.name)"
            @click="navigate(item.name, item.params)"
            active-color="primary"
          />
        </VList>
      </template>

      <!-- Admin -->
      <template v-if="adminMenu.length">
        <VDivider />
        <VListSubheader v-if="!rail" class="text-caption font-weight-bold px-4 pt-2">
          Administración
        </VListSubheader>
        <VList density="compact" nav>
          <VListItem
            v-for="item in adminMenu"
            :key="item.name"
            :prepend-icon="item.icon"
            :title="item.title"
            :active="isActive(item.name)"
            @click="navigate(item.name)"
            active-color="primary"
          />
        </VList>
      </template>

      <!-- Spacer + Logout -->
      <template #append>
        <VDivider />
        <VList density="compact" nav class="pb-2">
          <VListItem
            prepend-icon="mdi-account-outline"
            title="Mi perfil"
            :active="isActive('profile')"
            @click="navigate('profile')"
            active-color="primary"
          />
          <VListItem
            prepend-icon="mdi-logout"
            title="Cerrar sesión"
            @click="handleLogout"
            base-color="error"
          />
        </VList>
      </template>

    </VNavigationDrawer>

    <!-- ── Main content ───────────────────────────────────────────── -->
    <VMain>
      <VContainer fluid class="pa-6">
        <RouterView />
      </VContainer>
    </VMain>

  </VApp>
</template>

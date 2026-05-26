<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as dashboardService from '@/services/dashboard.service';
import type { DashboardProjectItem } from '@/services/types';

const router    = useRouter();
const appStore  = useAppStore();
const authStore = useAuthStore();
const { loader } = storeToRefs(appStore);
const { authUser } = storeToRefs(authStore);

const summary  = ref({ total_projects: 0, my_pending_tasks: 0, open_tickets: 0 });
const projects = ref<DashboardProjectItem[]>([]);
const tasks    = ref<any[]>([]);
const tickets  = ref<any[]>([]);

onMounted(async () => {
    loader.value = true;
    const response = await dashboardService.get();
    if (response.status && response.items) {
        summary.value  = response.items.summary  ?? summary.value;
        projects.value = response.items.projects  ?? [];
        tasks.value    = response.items.my_tasks  ?? [];
        tickets.value  = response.items.my_tickets ?? [];
    }
    loader.value = false;
});

function selectProject(project: DashboardProjectItem) {
    authStore.setCurrentProject(project as any);
    router.push({ name: 'project-detail', params: { projectId: project.id } });
}

const statusColor: Record<string, string> = {
    planning:  'blue-grey',
    active:    'success',
    on_hold:   'warning',
    completed: 'primary',
    cancelled: 'error',
};

const taskStatusColor: Record<string, string> = {
    pending:     'grey',
    in_progress: 'info',
    review:      'warning',
    done:        'success',
    blocked:     'error',
};
</script>

<template>
  <div>
    <!-- Saludo -->
    <div class="d-flex align-center mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Hola, {{ authUser?.name ?? 'Usuario' }} 👋
        </h4>
        <p class="text-body-1 text-medium-emphasis mt-1">
          Aquí tienes un resumen de tu actividad
        </p>
      </div>
    </div>

    <!-- Métricas resumen -->
    <VRow class="mb-4">
      <VCol cols="12" sm="4">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="primary" variant="tonal" size="48">
              <VIcon icon="mdi-folder-multiple" />
            </VAvatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ summary.total_projects }}</div>
              <div class="text-caption text-medium-emphasis">Proyectos asignados</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="4">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="warning" variant="tonal" size="48">
              <VIcon icon="mdi-check-circle-outline" />
            </VAvatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ summary.my_pending_tasks }}</div>
              <div class="text-caption text-medium-emphasis">Tareas pendientes</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="4">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="error" variant="tonal" size="48">
              <VIcon icon="mdi-ticket-outline" />
            </VAvatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ summary.open_tickets }}</div>
              <div class="text-caption text-medium-emphasis">Tickets abiertos</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <!-- Proyectos asignados -->
      <VCol cols="12" md="7">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-folder-multiple" class="me-2" />
              Mis proyectos
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList lines="two">
              <VListItem
                v-for="project in projects"
                :key="project.id"
                :title="project.name"
                :subtitle="`${project.tasks_count ?? 0} tareas · ${project.tickets_count ?? 0} tickets`"
                @click="selectProject(project)"
                class="cursor-pointer"
              >
                <template #prepend>
                  <VAvatar :color="statusColor[project.status] ?? 'grey'" variant="tonal">
                    <VIcon icon="mdi-folder" />
                  </VAvatar>
                </template>
                <template #append>
                  <VChip
                    :color="statusColor[project.status] ?? 'grey'"
                    variant="tonal"
                    size="small"
                  >
                    {{ project.status }}
                  </VChip>
                </template>
              </VListItem>
              <VListItem v-if="projects.length === 0" class="text-center py-8">
                <VListItemTitle class="text-medium-emphasis">Sin proyectos asignados</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Tareas pendientes -->
      <VCol cols="12" md="5">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-check-circle-outline" class="me-2" />
              Tareas recientes
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList density="compact">
              <VListItem
                v-for="task in tasks.slice(0, 8)"
                :key="task.id"
                :title="task.title"
                :subtitle="task.project?.name"
              >
                <template #append>
                  <VChip
                    :color="taskStatusColor[task.status] ?? 'grey'"
                    variant="tonal"
                    size="x-small"
                  >
                    {{ task.status }}
                  </VChip>
                </template>
              </VListItem>
              <VListItem v-if="tasks.length === 0" class="text-center py-6">
                <VListItemTitle class="text-medium-emphasis">Sin tareas pendientes</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as dashboardService from '@/services/dashboard.service';
import type {
    DashboardProjectItem,
    DashboardBlockerItem,
    DashboardRiskItem,
    DashboardPhaseItem,
    DashboardObjectiveItem,
    DashboardManagerTicketItem,
    DashboardManagerTaskItem,
    DashboardClientTicketItem,
} from '@/services/types';

const router    = useRouter();
const appStore  = useAppStore();
const authStore = useAuthStore();
const { loader } = storeToRefs(appStore);
const { authUser } = storeToRefs(authStore);

const summary    = ref({ total_projects: 0, my_pending_tasks: 0, open_tickets: 0, active_blockers_count: 0, active_risks_count: 0 });
const projects   = ref<DashboardProjectItem[]>([]);
const tasks      = ref<any[]>([]);
const tickets    = ref<any[]>([]);
const blockers   = ref<DashboardBlockerItem[]>([]);
const risks      = ref<DashboardRiskItem[]>([]);
const phases     = ref<DashboardPhaseItem[]>([]);
const objectives = ref<DashboardObjectiveItem[]>([]);
const isManager       = ref(false);
const isClient        = ref(false);
const canViewTasks    = ref(false);
const canViewBlockers = ref(false);
const canViewRisks    = ref(false);
const canViewPhases   = ref(false);
const canViewObjectives = ref(false);
const myTickets  = ref<DashboardClientTicketItem[]>([]);
const mgrTickets = ref<DashboardManagerTicketItem[]>([]);
const mgrTasks   = ref<DashboardManagerTaskItem[]>([]);

onMounted(async () => {
    loader.value = true;
    const response = await dashboardService.get();
    if (response.status && response.items) {
        summary.value    = { ...summary.value, ...response.items.summary };
        projects.value   = response.items.projects          ?? [];
        tasks.value      = response.items.my_tasks          ?? [];
        tickets.value    = response.items.open_tickets       ?? [];
        blockers.value   = response.items.active_blockers    ?? [];
        risks.value      = response.items.active_risks       ?? [];
        phases.value     = response.items.active_phases      ?? [];
        objectives.value = response.items.active_objectives  ?? [];
        isManager.value         = response.items.is_manager          ?? false;
        isClient.value          = response.items.is_client            ?? false;
        canViewTasks.value      = response.items.can_view_tasks       ?? false;
        canViewBlockers.value   = response.items.can_view_blockers    ?? false;
        canViewRisks.value      = response.items.can_view_risks       ?? false;
        canViewPhases.value     = response.items.can_view_phases      ?? false;
        canViewObjectives.value = response.items.can_view_objectives  ?? false;
        myTickets.value  = response.items.my_tickets          ?? [];
        mgrTickets.value = response.items.manager_tickets    ?? [];
        mgrTasks.value   = response.items.manager_tasks      ?? [];
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

const ticketStatusColor: Record<string, string> = {
    open:        'error',
    in_progress: 'info',
    resolved:    'success',
    closed:      'grey',
};

const severityColor: Record<string, string> = {
    low:      'success',
    medium:   'warning',
    high:     'error',
    critical: 'deep-purple',
};

const impactColor: Record<string, string> = {
    low:      'success',
    medium:   'warning',
    high:     'error',
    critical: 'deep-purple',
};

const objectiveTypeColor: Record<string, string> = {
    general:  'primary',
    specific: 'teal',
};
</script>

<template>
  <div>
    <!-- Saludo -->
    <div class="d-flex align-center mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Hola, {{ authUser?.name ?? 'Usuario' }}
        </h4>
        <p class="text-body-1 text-medium-emphasis mt-1">
          Aquí tienes un resumen de tu actividad
        </p>
      </div>
    </div>

    <!-- ── Métricas resumen ────────────────────────────────── -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="4">
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
      <VCol v-if="canViewTasks" cols="12" sm="6" md="4">
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
      <VCol cols="12" sm="6" md="4">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="error" variant="tonal" size="48">
              <VIcon icon="mdi-ticket-outline" />
            </VAvatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ summary.open_tickets }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ isClient ? 'Mis tickets activos' : 'Tickets abiertos' }}
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol v-if="canViewBlockers" cols="12" sm="6" md="6">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="deep-purple" variant="tonal" size="48">
              <VIcon icon="mdi-shield-alert-outline" />
            </VAvatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ summary.active_blockers_count ?? 0 }}</div>
              <div class="text-caption text-medium-emphasis">Bloqueadores activos</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol v-if="canViewRisks" cols="12" sm="6" md="6">
        <VCard>
          <VCardText class="d-flex align-center gap-4">
            <VAvatar color="orange" variant="tonal" size="48">
              <VIcon icon="mdi-alert-rhombus-outline" />
            </VAvatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ summary.active_risks_count ?? 0 }}</div>
              <div class="text-caption text-medium-emphasis">Riesgos activos</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Proyectos + Tareas ──────────────────────────────── -->
    <VRow class="mb-4">
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
                class="cursor-pointer"
                @click="selectProject(project)"
              >
                <template #prepend>
                  <VAvatar :color="statusColor[project.status] ?? 'grey'" variant="tonal">
                    <VIcon icon="mdi-folder" />
                  </VAvatar>
                </template>
                <template #append>
                  <VChip :color="statusColor[project.status] ?? 'grey'" variant="tonal" size="small">
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

      <VCol v-if="canViewTasks" cols="12" md="5">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-check-circle-outline" class="me-2" />
              Mis tareas pendientes
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
                  <VChip :color="taskStatusColor[task.status] ?? 'grey'" variant="tonal" size="x-small">
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

    <!-- ── Bloqueadores + Riesgos ─────────────────────────── -->
    <VRow v-if="canViewBlockers || canViewRisks" class="mb-4">
      <VCol v-if="canViewBlockers" cols="12" md="6">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-shield-alert-outline" color="deep-purple" class="me-2" />
              Bloqueadores activos
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList density="compact">
              <VListItem
                v-for="blocker in blockers"
                :key="blocker.id"
                :title="blocker.title"
                :subtitle="blocker.project?.name + (blocker.task ? ' · ' + blocker.task.title : '')"
              >
                <template #prepend>
                  <VIcon icon="mdi-block-helper" :color="severityColor[blocker.severity] ?? 'grey'" class="me-2" />
                </template>
                <template #append>
                  <VChip :color="severityColor[blocker.severity] ?? 'grey'" variant="tonal" size="x-small">
                    {{ blocker.severity }}
                  </VChip>
                </template>
              </VListItem>
              <VListItem v-if="blockers.length === 0" class="text-center py-6">
                <VListItemTitle class="text-medium-emphasis">Sin bloqueadores activos</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <VCol v-if="canViewRisks" cols="12" md="6">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-alert-rhombus-outline" color="orange" class="me-2" />
              Riesgos activos
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList density="compact">
              <VListItem
                v-for="risk in risks"
                :key="risk.id"
                :title="risk.title"
                :subtitle="risk.project?.name"
              >
                <template #prepend>
                  <VIcon icon="mdi-alert-rhombus" :color="impactColor[risk.impact] ?? 'grey'" class="me-2" />
                </template>
                <template #append>
                  <div class="d-flex gap-1">
                    <VChip :color="impactColor[risk.impact] ?? 'grey'" variant="tonal" size="x-small">
                      imp: {{ risk.impact }}
                    </VChip>
                    <VChip color="blue-grey" variant="tonal" size="x-small">
                      prob: {{ risk.probability }}
                    </VChip>
                  </div>
                </template>
              </VListItem>
              <VListItem v-if="risks.length === 0" class="text-center py-6">
                <VListItemTitle class="text-medium-emphasis">Sin riesgos activos</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Fase activa + Objetivo activo ─────────────────── -->
    <VRow v-if="canViewPhases || canViewObjectives" class="mb-4">
      <VCol v-if="canViewPhases" cols="12" md="6">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-timeline-clock-outline" color="teal" class="me-2" />
              Fases en curso
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList density="compact">
              <VListItem
                v-for="phase in phases"
                :key="phase.id"
                :subtitle="phase.project?.name + (phase.end_date ? ' · vence ' + phase.end_date : '')"
              >
                <template #title>
                  <span>{{ phase.name }}</span>
                </template>
                <template #append>
                  <div style="width: 80px" class="d-flex align-center gap-2">
                    <VProgressLinear
                      :model-value="phase.progress"
                      color="teal"
                      rounded
                      height="6"
                    />
                    <span class="text-caption text-medium-emphasis">{{ phase.progress }}%</span>
                  </div>
                </template>
              </VListItem>
              <VListItem v-if="phases.length === 0" class="text-center py-6">
                <VListItemTitle class="text-medium-emphasis">Sin fases en curso</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <VCol v-if="canViewObjectives" cols="12" md="6">
        <VCard height="100%">
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-bullseye-arrow" color="primary" class="me-2" />
              Objetivos pendientes
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList density="compact">
              <VListItem
                v-for="obj in objectives"
                :key="obj.id"
                :title="obj.title"
                :subtitle="obj.project?.name"
              >
                <template #prepend>
                  <VIcon icon="mdi-circle-outline" color="primary" class="me-2" />
                </template>
                <template #append>
                  <VChip :color="objectiveTypeColor[obj.type] ?? 'grey'" variant="tonal" size="x-small">
                    {{ obj.type }}
                  </VChip>
                </template>
              </VListItem>
              <VListItem v-if="objectives.length === 0" class="text-center py-6">
                <VListItemTitle class="text-medium-emphasis">Sin objetivos pendientes</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Tickets abiertos — solo para NO clientes ───────── -->
    <VRow v-if="!isClient && canViewTasks" class="mb-4">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-ticket-outline" color="error" class="me-2" />
              Tickets abiertos
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VList density="compact">
              <VListItem
                v-for="ticket in tickets"
                :key="ticket.id"
                :title="ticket.subject"
                :subtitle="ticket.project?.name"
              >
                <template #prepend>
                  <VIcon icon="mdi-ticket" color="error" class="me-2" />
                </template>
                <template #append>
                  <VChip :color="severityColor[ticket.priority] ?? 'grey'" variant="tonal" size="x-small">
                    {{ ticket.priority }}
                  </VChip>
                </template>
              </VListItem>
              <VListItem v-if="tickets.length === 0" class="text-center py-6">
                <VListItemTitle class="text-medium-emphasis">Sin tickets abiertos</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Mis tickets — solo para clientes ──────────────── -->
    <VRow v-if="isClient" class="mb-4">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>
              <VIcon icon="mdi-ticket-account" color="primary" class="me-2" />
              Mis tickets
            </VCardTitle>
          </VCardItem>
          <VDivider />
          <VCardText class="pa-0">
            <VTable density="compact" hover>
              <thead>
                <tr>
                  <th>Asunto</th>
                  <th>Proyecto</th>
                  <th>Estado</th>
                  <th>Prioridad</th>
                  <th>Asignado a</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in myTickets" :key="t.id">
                  <td>{{ t.subject }}</td>
                  <td>
                    <VChip variant="text" size="small" color="blue-grey">
                      {{ t.project?.code ?? t.project?.name }}
                    </VChip>
                  </td>
                  <td>
                    <VChip :color="ticketStatusColor[t.status] ?? 'grey'" variant="tonal" size="x-small">
                      {{ t.status }}
                    </VChip>
                  </td>
                  <td>
                    <VChip :color="severityColor[t.priority] ?? 'grey'" variant="tonal" size="x-small">
                      {{ t.priority }}
                    </VChip>
                  </td>
                  <td>
                    <span v-if="t.assignee" class="d-flex align-center gap-1">
                      <VIcon icon="mdi-account-check-outline" size="16" color="success" />
                      {{ t.assignee.name }}
                    </span>
                    <VChip v-else color="warning" variant="tonal" size="x-small">
                      Sin asignar
                    </VChip>
                  </td>
                  <td class="text-caption text-medium-emphasis">
                    {{ t.created_at ? String(t.created_at).slice(0, 10) : '—' }}
                  </td>
                </tr>
                <tr v-if="myTickets.length === 0">
                  <td colspan="6" class="text-center text-medium-emphasis py-4">Sin tickets registrados</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- ── Sección Manager ────────────────────────────────── -->
    <template v-if="isManager">
      <VDivider class="mb-6">
        <VChip color="primary" variant="tonal" prepend-icon="mdi-shield-crown-outline">
          Vista de Manager
        </VChip>
      </VDivider>

      <!-- Tickets: cliente que los abrió + miembro asignado -->
      <VRow class="mb-4">
        <VCol cols="12">
          <VCard>
            <VCardItem>
              <VCardTitle>
                <VIcon icon="mdi-account-multiple-outline" color="primary" class="me-2" />
                Tickets del equipo — abiertos por cliente y asignados
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-0">
              <VTable density="compact" hover>
                <thead>
                  <tr>
                    <th>Asunto</th>
                    <th>Proyecto</th>
                    <th>Abierto por</th>
                    <th>Asignado a</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in mgrTickets" :key="t.id">
                    <td>{{ t.subject }}</td>
                    <td>
                      <VChip variant="text" size="small" color="blue-grey">
                        {{ t.project?.code ?? t.project?.name }}
                      </VChip>
                    </td>
                    <td>
                      <span v-if="t.creator" class="d-flex align-center gap-1">
                        <VIcon icon="mdi-account-circle-outline" size="16" />
                        {{ t.creator.name }}
                      </span>
                      <span v-else class="text-medium-emphasis text-caption">—</span>
                    </td>
                    <td>
                      <span v-if="t.assignee" class="d-flex align-center gap-1">
                        <VIcon icon="mdi-account-check-outline" size="16" color="success" />
                        {{ t.assignee.name }}
                      </span>
                      <VChip v-else color="warning" variant="tonal" size="x-small">
                        Sin asignar
                      </VChip>
                    </td>
                    <td>
                      <VChip :color="severityColor[t.priority] ?? 'grey'" variant="tonal" size="x-small">
                        {{ t.priority }}
                      </VChip>
                    </td>
                    <td>
                      <VChip :color="ticketStatusColor[t.status] ?? 'grey'" variant="tonal" size="x-small">
                        {{ t.status }}
                      </VChip>
                    </td>
                  </tr>
                  <tr v-if="mgrTickets.length === 0">
                    <td colspan="6" class="text-center text-medium-emphasis py-4">Sin tickets activos</td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Tareas del equipo con asignados -->
      <VRow class="mb-4">
        <VCol cols="12">
          <VCard>
            <VCardItem>
              <VCardTitle>
                <VIcon icon="mdi-clipboard-account-outline" color="primary" class="me-2" />
                Tareas del equipo — pendientes con miembro asignado
              </VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-0">
              <VTable density="compact" hover>
                <thead>
                  <tr>
                    <th>Tarea</th>
                    <th>Proyecto</th>
                    <th>Asignado a</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Vencimiento</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in mgrTasks" :key="t.id">
                    <td>{{ t.title }}</td>
                    <td>
                      <VChip variant="text" size="small" color="blue-grey">
                        {{ t.project?.code ?? t.project?.name }}
                      </VChip>
                    </td>
                    <td>
                      <span v-if="t.assignee" class="d-flex align-center gap-1">
                        <VIcon icon="mdi-account-check-outline" size="16" color="success" />
                        {{ t.assignee.name }}
                      </span>
                      <VChip v-else color="warning" variant="tonal" size="x-small">
                        Sin asignar
                      </VChip>
                    </td>
                    <td>
                      <VChip :color="severityColor[t.priority ?? ''] ?? 'grey'" variant="tonal" size="x-small">
                        {{ t.priority ?? '—' }}
                      </VChip>
                    </td>
                    <td>
                      <VChip :color="taskStatusColor[t.status] ?? 'grey'" variant="tonal" size="x-small">
                        {{ t.status }}
                      </VChip>
                    </td>
                    <td class="text-caption text-medium-emphasis">
                      {{ t.due_date ? String(t.due_date).slice(0, 10) : '—' }}
                    </td>
                  </tr>
                  <tr v-if="mgrTasks.length === 0">
                    <td colspan="6" class="text-center text-medium-emphasis py-4">Sin tareas pendientes</td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </template>
  </div>
</template>


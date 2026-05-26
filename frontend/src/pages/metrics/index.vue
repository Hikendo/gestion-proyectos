<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as projectsService from '@/services/projects.service';
import type { ProjectMetricsResponseI } from '@/services/projects.service';

import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    ArcElement, BarElement,
    CategoryScale, LinearScale,
    LineElement, PointElement,
    Filler,
} from 'chart.js';
import { Doughnut, Bar } from 'vue-chartjs';

ChartJS.register(
    Title, Tooltip, Legend,
    ArcElement, BarElement,
    CategoryScale, LinearScale,
    LineElement, PointElement,
    Filler,
);

const route     = useRoute();
const appStore  = useAppStore();
const authStore = useAuthStore();
const { loader }                              = storeToRefs(appStore);
const { isSuperAdmin, isProjectManager }      = storeToRefs(authStore);
const projectId                               = () => Number(route.params.projectId);

const metrics   = ref<ProjectMetricsResponseI | null>(null);
const hasError  = ref(false);

const canSeeManagerSection = computed(() => isSuperAdmin.value || isProjectManager.value);

onMounted(async () => {
    loader.value = true;
    const response = await projectsService.getMetrics(projectId());
    if (response.status && response.items) {
        metrics.value = response.items;
    } else {
        hasError.value = true;
    }
    loader.value = false;
});

// ── Helpers ───────────────────────────────────────────────────────────────────
const statusColor = (s?: string) => ({
    planning: 'info', active: 'success', on_hold: 'warning',
    completed: 'success', cancelled: 'error',
}[s ?? ''] ?? 'default');

const statusLabel = (s?: string) => ({
    planning: 'Planificación', active: 'Activo', on_hold: 'En espera',
    completed: 'Completado', cancelled: 'Cancelado',
}[s ?? ''] ?? s ?? '—');

const completionRate = computed(() => metrics.value?.project.progress ?? 0);

const pct = (partial: number, total: number) =>
    total === 0 ? 0 : Math.round((partial / total) * 100);

// ── Doughnut: tareas completadas vs pendientes ────────────────────────────────
const doughnutTasksData = computed(() => {
    const t = metrics.value?.tasks;
    return {
        labels: ['Completadas', 'En progreso', 'En revisión', 'Pendientes', 'Bloqueadas'],
        datasets: [{
            data: [
                t?.completed    ?? 0,
                t?.in_progress  ?? 0,
                (t?.by_status?.find(s => s.status === 'review')?.count ?? 0),
                t?.pending      ?? 0,
                t?.blocked      ?? 0,
            ],
            backgroundColor: ['#4CAF50','#5C6BC0','#29B6F6','#90A4AE','#EF5350'],
            hoverBackgroundColor: ['#388E3C','#3949AB','#039BE5','#607D8B','#C62828'],
            borderWidth: 0,
        }],
    };
});

// ── Doughnut: riesgos ─────────────────────────────────────────────────────────
const doughnutRisksData = computed(() => ({
    labels: ['Activos', 'Resueltos / Mitigados'],
    datasets: [{
        data: [metrics.value?.risks.active ?? 0, metrics.value?.risks.resolved ?? 0],
        backgroundColor: ['#EF5350','#4CAF50'],
        borderWidth: 0,
    }],
}));

// ── Doughnut: objetivos ───────────────────────────────────────────────────────
const doughnutObjectivesData = computed(() => ({
    labels: ['Cumplidos', 'Pendientes'],
    datasets: [{
        data: [metrics.value?.objectives.completed ?? 0, metrics.value?.objectives.pending ?? 0],
        backgroundColor: ['#26A69A','#E0E0E0'],
        borderWidth: 0,
    }],
}));

// ── Doughnut: tickets ─────────────────────────────────────────────────────────
const doughnutTicketsData = computed(() => {
    const t = metrics.value?.tickets;
    return {
        labels: ['Abiertos', 'En progreso', 'Resueltos', 'Cerrados'],
        datasets: [{
            data: [t?.open ?? 0, t?.in_progress ?? 0, t?.resolved ?? 0, t?.closed ?? 0],
            backgroundColor: ['#FFA726','#5C6BC0','#26A69A','#90A4AE'],
            borderWidth: 0,
        }],
    };
});

// ── Bar: tareas por miembro ───────────────────────────────────────────────────
const barMembersData = computed(() => {
    const members = metrics.value?.tasks.by_member ?? [];
    return {
        labels: members.map(m => m.name),
        datasets: [
            { label: 'Total', data: members.map(m => m.total), backgroundColor: '#5C6BC0', borderRadius: 4 },
            { label: 'Completadas', data: members.map(m => m.completed), backgroundColor: '#4CAF50', borderRadius: 4 },
            { label: 'Bloqueadas', data: members.map(m => m.blocked), backgroundColor: '#EF5350', borderRadius: 4 },
        ],
    };
});

// ── Bar: bloqueadores por severidad ──────────────────────────────────────────
const barBlockersData = computed(() => {
    const bs = metrics.value?.blockers.by_severity ?? [];
    return {
        labels: bs.map(s => s.label),
        datasets: [{
            label: 'Bloqueadores',
            data: bs.map(s => s.count),
            backgroundColor: ['#EF5350','#FFA726','#FFEE58'],
            borderRadius: 4,
        }],
    };
});

// ── Bar: riesgos por impacto ──────────────────────────────────────────────────
const barRisksImpactData = computed(() => {
    const ri = metrics.value?.risks.by_impact ?? [];
    return {
        labels: ri.map(i => i.label),
        datasets: [{
            label: 'Riesgos',
            data: ri.map(i => i.count),
            backgroundColor: ['#EF5350','#FFA726','#FFEE58','#66BB6A'],
            borderRadius: 4,
        }],
    };
});

const doughnutOptions = {
    responsive: true, maintainAspectRatio: false, cutout: '68%',
    plugins: { legend: { position: 'bottom' as const } },
};
const barOptionsMulti = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' as const } },
    scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
        x: { grid: { display: false } },
    },
};
const barOptionsSingle = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
        x: { grid: { display: false } },
    },
};
</script>

<template>
  <VRow>
    <!-- ── Header ───────────────────────────────────────────────────────── -->
    <VCol cols="12">
      <VCard elevation="0" border>
        <VCardItem>
          <template #prepend>
            <VAvatar color="primary" variant="tonal" size="48" rounded="lg">
              <VIcon icon="mdi-chart-areaspline" size="28" />
            </VAvatar>
          </template>
          <VCardTitle class="text-h5 font-weight-bold">
            Informe de métricas
          </VCardTitle>
          <VCardSubtitle v-if="metrics">
            {{ metrics.project.name }}
            <VChip :color="statusColor(metrics.project.status)" size="x-small" class="ms-2" label>
              {{ statusLabel(metrics.project.status) }}
            </VChip>
          </VCardSubtitle>
          <template #append>
            <VBtn variant="outlined" size="small"
              :to="{ name: 'project-detail', params: { projectId: projectId() } }"
              prepend-icon="mdi-arrow-left">
              Proyecto
            </VBtn>
          </template>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- ── Error ─────────────────────────────────────────────────────────── -->
    <VCol v-if="hasError && !loader" cols="12">
      <VAlert type="error" variant="tonal" icon="mdi-alert-circle-outline">
        No se pudieron cargar las métricas. Verifica que tienes acceso al proyecto.
      </VAlert>
    </VCol>

    <template v-if="metrics">
      <!-- ── KPI Cards ───────────────────────────────────────────────────── -->
      <VCol cols="6" md="3">
        <VCard elevation="0" border class="text-center pa-4">
          <div class="text-h3 font-weight-bold text-primary">{{ metrics.tasks.total }}</div>
          <div class="text-caption text-medium-emphasis mt-1">TAREAS TOTALES</div>
          <VIcon icon="mdi-checkbox-marked-circle-outline" color="primary" size="24" class="mt-2" />
        </VCard>
      </VCol>

      <VCol cols="6" md="3">
        <VCard elevation="0" border class="text-center pa-4">
          <div class="text-h3 font-weight-bold text-success">{{ metrics.tasks.completed }}</div>
          <div class="text-caption text-medium-emphasis mt-1">TAREAS COMPLETADAS</div>
          <VIcon icon="mdi-check-all" color="success" size="24" class="mt-2" />
        </VCard>
      </VCol>

      <VCol cols="6" md="3">
        <VCard elevation="0" border class="text-center pa-4">
          <div class="text-h3 font-weight-bold text-warning">{{ metrics.tickets.open }}</div>
          <div class="text-caption text-medium-emphasis mt-1">TICKETS ABIERTOS</div>
          <VIcon icon="mdi-ticket-outline" color="warning" size="24" class="mt-2" />
        </VCard>
      </VCol>

      <VCol cols="6" md="3">
        <VCard elevation="0" border class="text-center pa-4">
          <div class="text-h3 font-weight-bold text-error">{{ metrics.blockers.active }}</div>
          <div class="text-caption text-medium-emphasis mt-1">BLOQUEADORES ACTIVOS</div>
          <VIcon icon="mdi-alert-circle-outline" color="error" size="24" class="mt-2" />
        </VCard>
      </VCol>

      <!-- ── Completion rate ────────────────────────────────────────────── -->
      <VCol cols="12">
        <VCard elevation="0" border>
          <VCardItem>
            <VCardTitle class="text-body-1 font-weight-medium">
              <VIcon icon="mdi-speedometer" class="me-2" />Progreso global
            </VCardTitle>
          </VCardItem>
          <VCardText class="pt-0">
            <div class="d-flex justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis">Completitud</span>
              <span class="text-body-2 font-weight-bold">{{ completionRate }}%</span>
            </div>
            <VProgressLinear
              :model-value="completionRate"
              :color="completionRate >= 75 ? 'success' : completionRate >= 40 ? 'warning' : 'error'"
              bg-color="surface-variant"
              height="12" rounded striped
            />
            <div class="d-flex justify-space-between mt-1">
              <span class="text-caption text-medium-emphasis">0%</span>
              <span class="text-caption text-medium-emphasis">100%</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- ── Distribución de tareas + Cronograma ────────────────────────── -->
      <VCol cols="12" md="5">
        <VCard elevation="0" border height="340">
          <VCardItem>
            <VCardTitle class="text-body-1 font-weight-medium">
              <VIcon icon="mdi-chart-donut" class="me-2" />Distribución de tareas
            </VCardTitle>
          </VCardItem>
          <VCardText style="height: 260px; position: relative;">
            <Doughnut :data="doughnutTasksData" :options="doughnutOptions" />
            <div style="position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
              <div class="text-h5 font-weight-bold">{{ metrics.tasks.total }}</div>
              <div class="text-caption text-medium-emphasis">total</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="7">
        <VCard elevation="0" border height="340">
          <VCardItem>
            <VCardTitle class="text-body-1 font-weight-medium">
              <VIcon icon="mdi-calendar-range" class="me-2" />Cronograma y presupuesto
            </VCardTitle>
          </VCardItem>
          <VCardText>
            <VList density="compact" lines="one">
              <VListItem prepend-icon="mdi-play-circle-outline" title="Fecha inicio"
                :subtitle="metrics.project.start_date ?? 'No definida'" />
              <VListItem prepend-icon="mdi-stop-circle-outline" title="Fecha fin"
                :subtitle="metrics.project.end_date ?? 'No definida'" />
              <VListItem prepend-icon="mdi-currency-usd" title="Presupuesto"
                :subtitle="metrics.project.budget ? `$${Number(metrics.project.budget).toLocaleString()}` : 'No definido'" />
              <VListItem prepend-icon="mdi-account" title="Responsable"
                :subtitle="metrics.project.owner?.name ?? '—'" />
            </VList>
            <div class="d-flex mt-3 ga-3">
              <VChip size="small" color="primary" variant="tonal" prepend-icon="mdi-account-group">
                {{ metrics.project.members.length }} miembros
              </VChip>
              <VChip size="small" color="success" variant="tonal" prepend-icon="mdi-flag-checkered">
                {{ metrics.milestones.completed }}/{{ metrics.milestones.total }} hitos
              </VChip>
              <VChip size="small" color="info" variant="tonal" prepend-icon="mdi-package-variant">
                {{ metrics.deliverables.approved }}/{{ metrics.deliverables.total }} entregables
              </VChip>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- ════════════ SECCIÓN SOLO PARA PROJECT MANAGER / SUPERADMIN ══════ -->
      <template v-if="canSeeManagerSection">

        <!-- ── Tareas por miembro ──────────────────────────────────────── -->
        <VCol cols="12">
          <VCard elevation="0" border>
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-account-hard-hat" class="me-2" />Tareas por miembro del equipo
              </VCardTitle>
            </VCardItem>
            <VCardText>
              <VRow>
                <VCol cols="12" md="7" style="height:280px; position:relative;">
                  <Bar :data="barMembersData" :options="barOptionsMulti" />
                </VCol>
                <VCol cols="12" md="5">
                  <VTable density="compact">
                    <thead>
                      <tr>
                        <th>Miembro</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Completadas</th>
                        <th class="text-center">Bloqueadas</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="m in metrics.tasks.by_member" :key="m.user_id">
                        <td>
                          <div class="d-flex align-center gap-2 py-1">
                            <VAvatar size="26" color="primary" variant="tonal">
                              <span class="text-caption">{{ m.name.charAt(0).toUpperCase() }}</span>
                            </VAvatar>
                            <span class="text-body-2">{{ m.name }}</span>
                          </div>
                        </td>
                        <td class="text-center">{{ m.total }}</td>
                        <td class="text-center">
                          <VChip size="x-small" color="success" variant="tonal">{{ m.completed }}</VChip>
                        </td>
                        <td class="text-center">
                          <VChip size="x-small" :color="m.blocked > 0 ? 'error' : 'default'" variant="tonal">{{ m.blocked }}</VChip>
                        </td>
                      </tr>
                      <tr v-if="metrics.tasks.by_member.length === 0">
                        <td colspan="4" class="text-center text-medium-emphasis py-4">Sin datos</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ── Tickets por estado ──────────────────────────────────────── -->
        <VCol cols="12" md="5">
          <VCard elevation="0" border height="340">
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-ticket-outline" class="me-2" />Tickets por estado
                <VChip size="x-small" class="ms-2" color="warning" variant="tonal">
                  {{ metrics.tickets.total }} totales
                </VChip>
              </VCardTitle>
            </VCardItem>
            <VCardText style="height:260px; position:relative;">
              <Doughnut :data="doughnutTicketsData" :options="doughnutOptions" />
              <div style="position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                <div class="text-h5 font-weight-bold">{{ metrics.tickets.total }}</div>
                <div class="text-caption text-medium-emphasis">tickets</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ── Riesgos ─────────────────────────────────────────────────── -->
        <VCol cols="12" md="7">
          <VCard elevation="0" border>
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-shield-alert-outline" class="me-2" />Riesgos: activos vs resueltos
              </VCardTitle>
            </VCardItem>
            <VCardText>
              <VRow>
                <VCol cols="12" sm="5" style="height:200px; position:relative;">
                  <Doughnut :data="doughnutRisksData" :options="doughnutOptions" />
                  <div style="position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                    <div class="text-h5 font-weight-bold">{{ metrics.risks.total }}</div>
                    <div class="text-caption text-medium-emphasis">riesgos</div>
                  </div>
                </VCol>
                <VCol cols="12" sm="7">
                  <div class="d-flex flex-column ga-3 mt-4">
                    <div class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center ga-2">
                        <VIcon icon="mdi-circle" color="error" size="12" />
                        <span class="text-body-2">Activos</span>
                      </div>
                      <VChip size="small" color="error" variant="tonal">{{ metrics.risks.active }}</VChip>
                    </div>
                    <div class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center ga-2">
                        <VIcon icon="mdi-circle" color="success" size="12" />
                        <span class="text-body-2">Resueltos / Mitigados</span>
                      </div>
                      <VChip size="small" color="success" variant="tonal">{{ metrics.risks.resolved }}</VChip>
                    </div>
                    <VDivider class="my-1" />
                    <div class="text-caption text-medium-emphasis mb-1">Por impacto</div>
                    <div v-for="ri in metrics.risks.by_impact" :key="ri.impact"
                         class="d-flex align-center justify-space-between">
                      <span class="text-body-2">{{ ri.label }}</span>
                      <VChip size="x-small" variant="tonal">{{ ri.count }}</VChip>
                    </div>
                    <span v-if="metrics.risks.by_impact.length === 0" class="text-caption text-medium-emphasis">Sin riesgos registrados</span>
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ── Bloqueadores ────────────────────────────────────────────── -->
        <VCol cols="12">
          <VCard elevation="0" border>
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-alert-circle-outline" class="me-2" />Bloqueadores
                <VChip size="x-small" class="ms-2" color="error" variant="tonal">
                  {{ metrics.blockers.active }} activos
                </VChip>
                <VChip size="x-small" class="ms-1" color="success" variant="tonal">
                  {{ metrics.blockers.resolved }} resueltos
                </VChip>
              </VCardTitle>
            </VCardItem>
            <VCardText>
              <VRow>
                <!-- Por severidad -->
                <VCol cols="12" md="5" style="height:220px; position:relative;">
                  <div class="text-caption text-medium-emphasis mb-2">Por severidad</div>
                  <Bar :data="barBlockersData" :options="barOptionsSingle" style="height:180px;" />
                </VCol>
                <!-- Dados de alta por -->
                <VCol cols="12" md="7">
                  <div class="text-caption text-medium-emphasis mb-2">Registrados por</div>
                  <VTable density="compact">
                    <thead>
                      <tr>
                        <th>Miembro</th>
                        <th class="text-center">Bloqueadores creados</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="bc in metrics.blockers.by_creator" :key="bc.user_id">
                        <td>
                          <div class="d-flex align-center gap-2 py-1">
                            <VAvatar size="26" color="error" variant="tonal">
                              <span class="text-caption">{{ bc.name.charAt(0).toUpperCase() }}</span>
                            </VAvatar>
                            <span class="text-body-2">{{ bc.name }}</span>
                          </div>
                        </td>
                        <td class="text-center">
                          <VChip size="x-small" color="error" variant="tonal">{{ bc.count }}</VChip>
                        </td>
                      </tr>
                      <tr v-if="metrics.blockers.by_creator.length === 0">
                        <td colspan="2" class="text-center text-medium-emphasis py-4">Sin datos</td>
                      </tr>
                    </tbody>
                  </VTable>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ── Objetivos ───────────────────────────────────────────────── -->
        <VCol cols="12" md="5">
          <VCard elevation="0" border height="340">
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-target" class="me-2" />Objetivos cumplidos
              </VCardTitle>
            </VCardItem>
            <VCardText style="height:260px; position:relative;">
              <Doughnut :data="doughnutObjectivesData" :options="doughnutOptions" />
              <div style="position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                <div class="text-h5 font-weight-bold">{{ pct(metrics.objectives.completed, metrics.objectives.total) }}%</div>
                <div class="text-caption text-medium-emphasis">cumplidos</div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- ── Objetivos por tipo + Hitos y Entregables ───────────────── -->
        <VCol cols="12" md="7">
          <VCard elevation="0" border>
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-format-list-checks" class="me-2" />Objetivos por tipo
              </VCardTitle>
            </VCardItem>
            <VCardText>
              <div v-for="ot in metrics.objectives.by_type" :key="ot.type" class="mb-3">
                <div class="d-flex justify-space-between text-body-2 mb-1">
                  <span class="font-weight-medium">{{ ot.type }}</span>
                  <span class="text-medium-emphasis">{{ ot.completed }}/{{ ot.total }}</span>
                </div>
                <VProgressLinear
                  :model-value="pct(ot.completed, ot.total)"
                  :color="pct(ot.completed, ot.total) === 100 ? 'success' : 'primary'"
                  bg-color="surface-variant"
                  height="8" rounded
                />
              </div>
              <div v-if="metrics.objectives.by_type.length === 0"
                   class="text-caption text-medium-emphasis">
                Sin objetivos registrados.
              </div>
            </VCardText>
          </VCard>

          <!-- Hitos y entregables -->
          <VCard elevation="0" border class="mt-4">
            <VCardItem>
              <VCardTitle class="text-body-1 font-weight-medium">
                <VIcon icon="mdi-flag-checkered" class="me-2" />Hitos y entregables
              </VCardTitle>
            </VCardItem>
            <VCardText>
              <VRow>
                <VCol cols="6" class="text-center">
                  <div class="text-h4 font-weight-bold text-primary">
                    {{ metrics.milestones.completed }}<span class="text-body-1 text-medium-emphasis">/{{ metrics.milestones.total }}</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mt-1">HITOS COMPLETADOS</div>
                  <VProgressLinear
                    :model-value="pct(metrics.milestones.completed, metrics.milestones.total)"
                    color="primary" bg-color="surface-variant" height="6" rounded class="mt-2"
                  />
                </VCol>
                <VCol cols="6" class="text-center">
                  <div class="text-h4 font-weight-bold text-info">
                    {{ metrics.deliverables.approved }}<span class="text-body-1 text-medium-emphasis">/{{ metrics.deliverables.total }}</span>
                  </div>
                  <div class="text-caption text-medium-emphasis mt-1">ENTREGABLES APROBADOS</div>
                  <VProgressLinear
                    :model-value="pct(metrics.deliverables.approved, metrics.deliverables.total)"
                    color="info" bg-color="surface-variant" height="6" rounded class="mt-2"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>

      </template>
      <!-- ═══════════ FIN SECCIÓN MANAGER ══════════════════════════════════ -->

    </template>
  </VRow>
</template>

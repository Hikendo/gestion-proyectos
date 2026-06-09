<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as projectsService from '@/services/projects.service';
import type { ProjectI } from '@/interfaces/ProjectI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';
import { formatDate } from '@/utils/util';
import { ProjectStatus } from "@/interfaces/enums";

const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const isDialogVisible = ref<boolean>(false);

const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<ProjectI[]>([]);

const handleGetData = async () => {
    loader.value = true;
    const response = await projectsService.index({ page: paginacionYquery.value.page, query: paginacionYquery.value.query });
    if (response.status && response.items) {
        data.value = (response.items as any).data ?? [];
        paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
    }
    loader.value = false;
};

const itemDestroy = ref<ProjectI | null>(null);

const handleDestroy = async () => {
    if (!itemDestroy.value) return;
    loader.value = true;
    const response = await projectsService.destroy(itemDestroy.value.id);
    if (response.status) {
        snackbar.value = { show: true, text: 'Proyecto eliminado', color: 'success' };
        handleGetData();
    }
    loader.value = false;
    isDialogVisible.value = false;
};

// Helper tipado con los estados reales de tu aplicación
const getStatusConfig = (status: ProjectStatus) => {
  switch (status) {
    case 'completed':
      return { color: 'success', icon: 'ri-checkbox-circle-line', text: 'Completado' };
    case 'active':
      return { color: 'info', icon: 'ri-play-circle-line', text: 'Activo' };
    case 'planning':
      return { color: 'secondary', icon: 'ri-calendar-schedule-line', text: 'Planificación' };
    case 'on_hold':
      return { color: 'warning', icon: 'ri-pause-circle-line', text: 'En Espera' };
    case 'cancelled':
      return { color: 'error', icon: 'ri-close-circle-line', text: 'Cancelado' };
    default:
      return { color: 'grey', icon: 'ri-question-line', text: status };
  }
};

watch(() => isDialogVisible.value, (val) => {
    if (!val) itemDestroy.value = null;
});
onMounted(handleGetData);
</script>

<template>
  <VRow>
    <!-- Encabezado (Se mantiene igual) -->
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Proyectos</strong></h4>
              <VBtn variant="flat" :to="{ name: 'projects-new' }" v-if="canAction('Proyecto.Store')">
                Nuevo proyecto
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- Buscador (Se mantiene igual) -->
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VRow class="d-flex align-center gap-4 mt-2">
            <VCol>
              <form @submit.prevent="() => { paginacionYquery.page = 1; handleGetData(); }">
                <VTextField label="Buscador" prepend-inner-icon="ri-search-line" type="search"
                  clearable v-model="paginacionYquery.query" />
              </form>
            </VCol>
          </VRow>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- Listado en Estilo Premium Card -->
    <VCol cols="12">
      <VRow v-if="data.length > 0">
        <VCol
          v-for="item in data"
          :key="item.id"
          cols="12"
          sm="6"
          md="4"
          lg="4"
        >
          <VCard variant="flat" border class="h-100 d-flex flex-column justify-space-between">
            <!-- Barra superior semántica discreta -->
            <div :class="`bg-${getStatusConfig(item.status).color}`" style="height: 4px;"></div>

            <!-- Encabezado de la Card estilo tu ejemplo -->
            <VCardItem class="pb-0">
              <div class="d-flex justify-space-between align-center mb-2">
                <span class="text-overline text-medium-emphasis">#{{ item.id }} - {{ item.code || 'SIN CÓDIGO' }}</span>

                <!-- Chip de Estado -->
                <VChip
                  :color="getStatusConfig(item.status).color"
                  size="x-small"
                  label
                  class="font-weight-bold"
                >
                  <VIcon start :icon="getStatusConfig(item.status).icon" size="12" />
                  {{ getStatusConfig(item.status).text }}
                </VChip>
              </div>

              <!-- Nombre del Proyecto -->
              <div class="text-h5 font-weight-bold text-truncate mb-1" :title="item.name">
                {{ item.name }}
              </div>

              <!-- Progreso en Porcentaje Grande -->
              <div :class="`text-${getStatusConfig(item.status).color} text-h3 font-weight-bold my-1`">
                {{ item.progress ?? 0 }}%
              </div>

              <!-- Subtexto de presupuesto (usando item.budget)
              <div class="text-body-1 text-medium-emphasis font-weight-regular">
                Presupuesto: {{ item.budget ? `$${Number(item.budget).toLocaleString()}` : 'No asignado' }}
              </div>-->
            </VCardItem>

            <!-- Bloque de la Barra de Progreso Avanzada -->
            <VCardText class="pt-2 mt-8 flex-grow-1 position-relative">

              <!-- Línea de revisión flotante (Simulada a la mitad (50%) para propósitos visuales dinámicos) -->
              <div
                style="right: calc(50% - 32px)"
                :class="`position-absolute mt-n7 text-caption font-weight-medium text-${getStatusConfig(item.status).color}`"
              >
                Revisión
              </div>

              <!-- Barra Lineal con Badge incrustado -->
              <VProgressLinear
                :color="getStatusConfig(item.status).color"
                height="16"
                :model-value="item.progress ?? 0"
                rounded="lg"
              >
                <VBadge
                  style="right: 50%"
                  class="position-absolute"
                  color="white"
                  dot
                  inline
                ></VBadge>
              </VProgressLinear>

              <!-- Fechas del proyecto en el espacio inferior de la barra -->
              <div class="d-flex justify-space-between py-3 text-body-2">
                <span :class="`text-${getStatusConfig(item.status).color} font-weight-medium`">
                  F. Inicio: {{ item.start_date ? formatDate(item.start_date) : 'N/A' }}
                </span>
                <span class="text-medium-emphasis">
                  F. Fin: {{ item.end_date ? formatDate(item.end_date) : 'N/A' }}
                </span>
              </div>
            </VCardText>

            <VDivider />

            <!-- Acceso inferior estilo v-list-item de tu diseño -->
            <VListItem
              append-icon="ri-arrow-right-s-line"
              lines="two"
              subtitle="Ver detalles y documentación"
              link
              :to="{ name: 'project-detail', params: { projectId: item.id } }"
              class="pe-4"
            >
              <!-- Botón de eliminar encapsulado de forma limpia a la izquierda del chevron -->
              <template v-slot:append>
                <div class="d-flex align-center gap-1">
                  <VBtn
                    icon
                    size="small"
                    variant="text"
                    v-if="canAction('Proyecto.Destroy')"
                    @click.stop.prevent="() => { itemDestroy = item; isDialogVisible = true; }"
                  >
                    <VIcon icon="ri-delete-bin-fill" color="error" />
                  </VBtn>
                  <VIcon icon="ri-arrow-right-s-line" class="ms-2" />
                </div>
              </template>
            </VListItem>
          </VCard>
        </VCol>
      </VRow>

      <!-- Estado vacío -->
      <VCard v-else class="text-center pa-6">
        <VCardText class="text-disabled">No se encontraron proyectos.</VCardText>
      </VCard>
    </VCol>

    <!-- Paginación (Se mantiene igual) -->
    <VPagination class="mt-4 mr-3" color="primary"
      v-model="paginacionYquery.page"
      :total-visible="7"
      :length="paginacionYquery.last_page"
      style="margin-left: auto;"
      @update:model-value="handleGetData" />

    <!-- Diálogo de eliminación (Se mantiene igual) -->
    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <VCard title="Eliminar Proyecto">
        <VCardText>¿Eliminar este proyecto?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </VRow>
</template>

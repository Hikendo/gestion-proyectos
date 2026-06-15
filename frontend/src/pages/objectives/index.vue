<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as objectivesService from '@/services/project-objectives.service';

useEnsureCurrentProject();
import type { ObjectiveI } from '@/interfaces/ObjectiveI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const projectId = () => Number(route.params.projectId);

const TYPE_LABELS: Record<string, string> = {
  general: 'GENERAL',
  specific: 'ESPECÍFICO',
};

const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<ObjectiveI[]>([]);

const handleGetData = async () => {
  loader.value = true;
  const response = await objectivesService.index(projectId(), { page: paginacionYquery.value.page, query: paginacionYquery.value.query });
  if (response.status && response.items) {
    data.value = (response.items as any).data ?? response.items;
    paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
  }
  loader.value = false;
};

const toggleCompleted = async (item: ObjectiveI) => {
  const newValue = !item.completed;
  const response = await objectivesService.update(projectId(), item.id, { completed: newValue });
  if (response.status) {
    item.completed = newValue;
  } else {
    snackbar.value = { show: true, text: response.message ?? 'Error al actualizar', color: 'error' };
  }
};

onMounted(handleGetData);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Objetivos</strong></h4>
              <VBtn variant="flat" :to="{ name: 'objectives-new', params: { projectId: projectId() } }"
                v-if="canAction('objective.create')">
                Nuevo objetivo
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VRow class="d-flex align-center gap-4 mt-2">
            <VCol>
              <form @submit.prevent="() => { paginacionYquery.page = 1; handleGetData(); }">
                <VTextField label="Buscador" prepend-inner-icon="ri-search-line" type="search" clearable
                  v-model="paginacionYquery.query" />
              </form>
            </VCol>
          </VRow>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-0">
          <VTable height="500" fixed-header>
            <thead>
              <tr>
                <th class="text-uppercase">Tipo</th>
                <th class="text-uppercase">Título</th>
                <th class="text-uppercase">Completado</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>
                  <VChip :color="item.completed ? 'success' : 'warning'" size="small" label>
                    {{ TYPE_LABELS[item.type] ?? item.type.toUpperCase() }}
                  </VChip>
                </td>
                <td>{{ item.title }}</td>
                <td>
                  <VSwitch :model-value="item.completed" :color="item.completed ? 'success' : 'warning'" hide-details
                    density="compact" @update:model-value="toggleCompleted(item)" />
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" variant="text"
                      :to="{ name: 'objectives-view', params: { projectId: projectId(), id: item.id } }">
                      <VIcon icon="ri-eye-line" color="primary" size="small" />
                    </VBtn>
                    <VBtn icon size="small" variant="flat"
                      :to="{ name: 'objectives-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction('objective.edit')">
                      <VIcon icon="ri-pencil-line" color="warning" />
                    </VBtn>

                  </div>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>
    </VCol>

    <VPagination class="mt-4 mr-3" color="primary" v-model="paginacionYquery.page" :total-visible="7"
      :length="paginacionYquery.last_page" style="margin-left: auto;" @update:model-value="handleGetData" />

  </VRow>
</template>

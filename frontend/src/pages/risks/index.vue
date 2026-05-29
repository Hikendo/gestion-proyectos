<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as risksService from '@/services/project-risks.service';
import type { RiskI } from '@/interfaces/RiskI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';

const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const projectId = () => Number(route.params.projectId);

const isDialogVisible = ref<boolean>(false);

const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<RiskI[]>([]);

const handleGetData = async () => {
    loader.value = true;
    const response = await risksService.index(projectId(), { page: paginacionYquery.value.page, query: paginacionYquery.value.query });
    if (response.status && response.items) {
        data.value = (response.items as any).data ?? response.items;
        paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
    }
    loader.value = false;
};

const itemDestroy = ref<RiskI | null>(null);

const handleDestroy = async () => {
    if (!itemDestroy.value) return;
    loader.value = true;
    const response = await risksService.destroy(itemDestroy.value.project_id, itemDestroy.value.id);
    if (response.status) {
        snackbar.value = { show: true, text: 'Riesgo eliminado', color: 'success' };
        handleGetData();
    }
    loader.value = false;
    isDialogVisible.value = false;
};

watch(() => isDialogVisible.value, (val) => {
    if (!val) itemDestroy.value = null;
});
onMounted(handleGetData);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Riesgos</strong></h4>
              <VBtn variant="flat"
                :to="{ name: 'risks-new', params: { projectId: projectId() } }"
                v-if="canAction('Riesgo.Store')">
                Nuevo riesgo
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
                <VTextField label="Buscador" prepend-inner-icon="mdi-magnify" type="search"
                  clearable v-model="paginacionYquery.query" />
              </form>
            </VCol>
          </VRow>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-0">
          <VTable height="500" fixed-header>
            <thead>
              <tr>
                <th class="text-uppercase">ID</th>
                <th class="text-uppercase">Título</th>
                <th class="text-uppercase">Impacto</th>
                <th class="text-uppercase">Probabilidad</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>{{ item.id }}</td>
                <td>{{ item.title }}</td>
                <td>{{ item.impact }}</td>
                <td>{{ item.probability }}</td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" variant="flat"
                      :to="{ name: 'risks-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction('Riesgo.Update')">
                      <VIcon icon="mdi-pencil" color="warning"/>
                    </VBtn>
                                        <VBtn icon size="small" variant="flat" v-if="canAction('Riesgo.Destroy')"
                      @click="() => { itemDestroy = item; isDialogVisible = true; }">
                      <VIcon icon="mdi-delete" color="error"/>
                    </VBtn>
                  </div>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>
    </VCol>

    <VPagination class="mt-4 mr-3" color="primary"
      v-model="paginacionYquery.page"
      :total-visible="7"
      :length="paginacionYquery.last_page"
      style="margin-left: auto;"
      @update:model-value="handleGetData" />

    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <VCard title="Eliminar Riesgo">
        <VCardText>¿Eliminar este riesgo?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </VRow>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as projectsService from '@/services/projects.service';
import type { ProjectI } from '@/interfaces/ProjectI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';

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
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Proyectos</strong></h4>
              <VBtn variant="flat" :to="{ name: 'projects-new' }"
                v-if="canAction('Proyecto.Store')">
                Nuevo proyecto
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
                <th class="text-uppercase">Nombre</th>
                <th class="text-uppercase">Código</th>
                <th class="text-uppercase">Estado</th>
                <th class="text-uppercase">Inicio</th>
                <th class="text-uppercase">Fin</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>{{ item.id }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.code }}</td>
                <td>{{ item.status }}</td>
                <td>{{ item.start_date }}</td>
                <td>{{ item.end_date }}</td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" color="primary"
                      :to="{ name: 'project-detail', params: { projectId: item.id } }"
                      title="Ver detalle">
                      <VIcon icon="mdi-folder-open" />
                    </VBtn>
                    <VBtn icon size="small" color="warning"
                      :to="{ name: 'project-detail', params: { projectId: item.id } }"
                      v-if="canAction('Proyecto.Update')"
                      title="Editar">
                      <VIcon icon="mdi-pencil" />
                    </VBtn>
                                        <VBtn icon size="small" color="error" v-if="canAction('Proyecto.Destroy')"
                      @click="() => { itemDestroy.value = item; isDialogVisible.value = true; }">
                      <VIcon icon="mdi-delete" />
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
      <VCard title="Eliminar Proyecto">
        <VCardText>¿Eliminar este proyecto?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible.value = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </VRow>
</template>

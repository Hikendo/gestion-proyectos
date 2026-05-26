<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as membersService from '@/services/project-members.service';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';

const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

const isDialogVisible = ref(false);
const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<any[]>([]);

const handleGetData = async () => {
    loader.value = true;
    const response = await membersService.index(projectId());
    if (response.status && response.items) {
        data.value = (response.items as any).data ?? (Array.isArray(response.items) ? response.items : []);
        paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
    }
    loader.value = false;
};

const itemDestroy = ref<any | null>(null);
const handleDestroy = async () => {
    if (!itemDestroy.value) return;
    loader.value = true;
    const response = await membersService.destroy(projectId(), itemDestroy.value.id);
    if (response.status) {
        snackbar.value = { show: true, text: 'Miembro eliminado', color: 'success' };
        handleGetData();
    }
    loader.value = false;
    isDialogVisible.value = false;
};
watch(() => isDialogVisible.value, v => { if (!v) itemDestroy.value = null; });
onMounted(handleGetData);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap me-3"><strong>Miembros del proyecto</strong></h4>
              <div class="d-flex gap-2">
                <VBtn variant="outlined" size="small"
                  :to="{ name: 'project-detail', params: { projectId: projectId() } }"
                  prepend-icon="mdi-arrow-left">
                  Proyecto
                </VBtn>
                <VBtn variant="flat"
                  :to="{ name: 'members-new', params: { projectId: projectId() } }"
                  v-if="canAction('Miembro.Store')"
                  prepend-icon="mdi-plus">
                  Nuevo
                </VBtn>
              </div>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12">
      <VCard>
        <VDivider />
        <VCardText class="pa-0">
          <VTable height="500" fixed-header>
            <thead>
              <tr>
                <th class="text-uppercase">ID</th>
                <th class="text-uppercase">Usuario</th>
                <th class="text-uppercase">Rol</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>{{ item.id }}</td>
                <td>{{ item.user?.name ?? item.user_id }}</td>
                <td>{{ item.role }}</td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" color="warning"
                      :to="{ name: 'members-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction('Miembro.Update')">
                      <VIcon icon="mdi-pencil" />
                    </VBtn>
                                        <VBtn icon size="small" color="error"
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
      :total-visible="7" :length="paginacionYquery.last_page"
      style="margin-left: auto;" @update:model-value="handleGetData" />

    <VDialog v-model="isDialogVisible" persistent class="v-dialog-sm">
      <VCard title="Eliminar Miembro">
        <VCardText>¿Eliminar este miembro?</VCardText>
        <VCardText class="d-flex justify-end flex-wrap gap-4">
          <VBtn variant="outlined" @click="isDialogVisible.value = false">Cancelar</VBtn>
          <VBtn color="error" @click="handleDestroy">Eliminar</VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </VRow>
</template>

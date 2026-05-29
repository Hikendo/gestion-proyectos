<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as deliverablesService from '@/services/project-deliverables.service';
import type { DeliverableI } from '@/interfaces/DeliverableI';
import type { PaginacionYQueryI } from '@/interfaces/PaginacionScoutI';
import { formatDate } from '@/utils/util';
const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const projectId = () => Number(route.params.projectId);



const paginacionYquery = ref<PaginacionYQueryI>({ page: 1, query: '', last_page: 1 });
const data = ref<DeliverableI[]>([]);

const handleGetData = async () => {
    loader.value = true;
    const response = await deliverablesService.index(projectId(), { page: paginacionYquery.value.page, query: paginacionYquery.value.query });
    if (response.status && response.items) {
        data.value = (response.items as any).data ?? response.items;
        paginacionYquery.value.last_page = (response.items as any).last_page ?? 1;
    }
    loader.value = false;
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
              <h4 class="text-h4 text-wrap me-3">Listado de <strong>Entregables</strong></h4>
              <VBtn variant="flat"
                :to="{ name: 'deliverables-new', params: { projectId: projectId() } }"
                v-if="canAction('Entregable.Store')">
                Nuevo entregable
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
                <th class="text-uppercase">Entrega</th>
                <th class="text-uppercase">Aprobado</th>
                <th class="text-uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data" :key="item.id">
                <td>{{ item.id }}</td>
                <td>{{ item.name }}</td>
                <td>{{ formatDate(item.delivery_date!) ?? '—' }}</td>
                <td>{{ item.approved }}</td>
                <td>
                  <div class="d-flex gap-1">
                    <VBtn icon size="small" variant="flat"
                      :to="{ name: 'deliverables-id', params: { projectId: projectId(), id: item.id } }"
                      v-if="canAction('Entregable.Update')">
                      <VIcon icon="mdi-pencil" color="warning"/>
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

  </VRow>
</template>

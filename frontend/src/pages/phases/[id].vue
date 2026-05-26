<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as phasesService from '@/services/project-phases.service';

const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

const form   = ref<any>({ name: '', order: 1, description: null, start_date: null, end_date: null });
const errors = ref<any>({});

onMounted(async () => {
    loader.value = true;
    const id = Number(route.params.id);
    const response = await phasesService.index(projectId());
    if (response.status && response.items) {
        const phase = (response.items as any[]).find((p: any) => p.id === id);
        if (phase) form.value = { ...phase };
    }
    loader.value = false;
});

async function handleUpdate() {
    errors.value = {};
    loader.value = true;
    const id = Number(route.params.id);
    const response = await phasesService.update(projectId(), id, form.value);
    if (response.status) {
        snackbar.value = { show: true, text: 'Fase actualizada', color: 'success' };
    } else {
        if ('errors' in response && response.errors) errors.value = response.errors;
        snackbar.value = { show: true, text: response.message, color: 'error' };
    }
    loader.value = false;
}

const confirmVisible = ref(false);
const pendingAction   = ref<(() => Promise<void>) | null>(null);

function requestSave(action: () => Promise<void>) {
    pendingAction.value = action;
    confirmVisible.value = true;
}

async function confirmAction() {
    confirmVisible.value = false;
    if (pendingAction.value) await pendingAction.value();
    pendingAction.value = null;
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4">Editar <strong>Fase</strong></h4>
              <VBtn variant="outlined" :to="{ name: 'phases', params: { projectId: route.params.projectId } }" prepend-icon="mdi-arrow-left">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12" md="8">
      <VCard>
        <VCardText>
          <VForm @submit.prevent="requestSave(handleUpdate)">
            <VTextField v-model="form.name" label="Nombre de la fase" variant="outlined" :error-messages="errors.name" class="mb-3" />
            <VTextField v-model.number="form.order" label="Orden" type="number" variant="outlined" class="mb-3" />
            <VTextarea v-model="form.description" label="Descripción" variant="outlined" rows="3" class="mb-3" />
            <VTextField v-model="form.start_date" label="Fecha inicio" type="date" variant="outlined" class="mb-3" />
            <VTextField v-model="form.end_date" label="Fecha fin" type="date" variant="outlined" class="mb-4" />
            <VBtn type="submit" color="primary" block :loading="loader">Guardar cambios</VBtn>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  
    <VDialog v-model="confirmVisible" persistent max-width="400">
      <VCard>
        <VCardTitle class="text-h6">Confirmar acción</VCardTitle>
        <VCardText>¿Deseas guardar los cambios?</VCardText>
        <VCardActions class="justify-end gap-2 pb-4 px-4">
          <VBtn variant="outlined" @click="confirmVisible.value = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" @click="confirmAction">Confirmar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import * as blockersService from '@/services/project-blockers.service';
import { useBlockers } from '@/composables/useBlockers';
import BlockerForm from '@/components/blockers/BlockerForm.vue';

const route  = useRoute();
const router = useRouter();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const { errores, form, handleUpdate } = useBlockers();

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

onMounted(async () => {
    loader.value = true;
    const projectId = Number(route.params.projectId);
    const id = Number(route.params.id);
    const response = await blockersService.show(projectId, id);
    if (response.status && response.items) {
        form.value = response.items;
    } else {
        snackbar.value = { show: true, text: 'Bloqueador no encontrado', color: 'error' };
        router.push({ name: 'blockers', params: { projectId } });
    }
    loader.value = false;
});
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <h4 class="text-h4 text-wrap">
              Editar <strong>Bloqueador</strong>
            </h4>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12">
      <VForm @submit.prevent="requestSave(handleUpdate)">
        <BlockerForm :form="form" :errores="errores" />
      </VForm>
    </VCol>
  
    <VDialog v-model="confirmVisible" persistent max-width="400">
      <VCard>
        <VCardTitle class="text-h6">Confirmar acción</VCardTitle>
        <VCardText>¿Deseas guardar los cambios?</VCardText>
        <VCardActions class="justify-end gap-2 pb-4 px-4">
          <VBtn variant="outlined" @click="confirmVisible = false">Cancelar</VBtn>
          <VBtn color="primary" variant="flat" @click="confirmAction">Confirmar</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VRow>
</template>

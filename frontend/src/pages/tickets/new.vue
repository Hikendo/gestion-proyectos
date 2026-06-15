<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { useTickets } from '@/composables/useTickets';
import TicketForm from '@/components/tickets/TicketForm.vue';

const route = useRoute();
const { errores, form, handleStore } = useTickets();
const pendingFiles = ref<File[]>([]);

const confirmVisible = ref(false);
const pendingAction = ref<(() => Promise<void>) | null>(null);

function onAttachmentsChanged(files: File[]): void {
  pendingFiles.value = files;
}

function requestSave(action: () => Promise<void>) {
  pendingAction.value = action;
  confirmVisible.value = true;
}

async function confirmAction() {
  confirmVisible.value = false;
  if (pendingAction.value) await pendingAction.value();
  pendingAction.value = null;
}

async function onSubmit(): Promise<void> {
  await handleStore(pendingFiles.value.length ? pendingFiles.value : undefined);
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap">
                Agregar <strong>Ticket</strong>
              </h4>
              <VBtn variant="outlined" :to="{ name: 'tickets', params: { projectId: route.params.projectId } }">
                Volver
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12">
      <VForm @submit.prevent="requestSave(onSubmit)">
        <TicketForm :form="form" :errores="errores" :project-id="Number(route.params.projectId)"
          @update:attachments="onAttachmentsChanged" />
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
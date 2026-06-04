<script setup lang="ts">
import { ref } from 'vue';
import { useProjects } from '@/composables/useProjects';
import ProjectForm from '@/components/projects/ProjectForm.vue';

const { errores, form, handleStore } = useProjects();
const pendingFiles = ref<File[]>([]);

function onAttachmentsChanged(files: File[]): void {
  pendingFiles.value = files;
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
                Agregar <strong>Proyecto</strong>
              </h4>
              <VBtn variant="outlined" :to="{ name: 'projects' }">
                Volver
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12">
      <VForm @submit.prevent="onSubmit">
        <ProjectForm :form="form" :errores="errores" @update:attachments="onAttachmentsChanged" />
      </VForm>
    </VCol>
  </VRow>
</template>
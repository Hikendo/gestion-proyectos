<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import { useProjectPhasesService } from '@/composables';
import PhaseForm from '@/components/project-phases/PhaseForm.vue';
import type { ProjectPhaseI } from '@/interfaces/ProjectPhaseI';

type PhaseFormFields = Pick<ProjectPhaseI, 'name' | 'start_date' | 'end_date'>;

const route = useRoute();
const router = useRouter();
const { loading, validationErrors, call } = useProjectPhasesService();

const projectId = () => Number(route.params.projectId);

async function handleSubmit(form: PhaseFormFields) {
  const result = await call('store', projectId(), form);
  if (result?.status) {
    router.push({ name: 'phases', params: { projectId: projectId() } });
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4">Agregar <strong>Fase</strong></h4>
              <VBtn variant="outlined" :to="{ name: 'phases', params: { projectId: route.params.projectId } }"
                prepend-icon="ri-arrow-left-line">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
  </VRow>

  <PhaseForm submit-label="Guardar fase" :errors="validationErrors" :loading="loading" @submit="handleSubmit" />
</template>
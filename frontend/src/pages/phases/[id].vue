<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useProjectPhasesService } from '@/composables';
import PhaseForm from '@/components/project-phases/PhaseForm.vue';
import { toDateInput } from '@/utils/util';
import type { ProjectPhaseI } from '@/interfaces/ProjectPhaseI';

type PhaseFormFields = Pick<ProjectPhaseI, 'name' | 'start_date' | 'end_date'>;

const route = useRoute();
const router = useRouter();
const { loading, validationErrors, call } = useProjectPhasesService();

const projectId = () => Number(route.params.projectId);
const initialData = ref<PhaseFormFields>({
  name: '',
  start_date: null,
  end_date: null,
});
const phaseProgress = ref(0);
const ready = ref(false);

onMounted(async () => {
  const id = Number(route.params.id);
  const result = await call('index', projectId());
  if (result) {
    const items = (result.items ?? []) as ProjectPhaseI[];
    const phase = items.find((p: ProjectPhaseI) => p.id === id);
    if (phase) {
      initialData.value = {
        name: phase.name,
        start_date: toDateInput(phase.start_date ?? null),
        end_date: toDateInput(phase.end_date ?? null),
      };
      phaseProgress.value = phase.progress ?? 0;
    }
  }
  ready.value = true;
});

async function handleSubmit(form: PhaseFormFields) {
  const id = Number(route.params.id);
  const result = await call('update', projectId(), id, form);
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
              <h4 class="text-h4">Editar <strong>Fase</strong></h4>
              <VBtn variant="outlined" :to="{ name: 'phases', params: { projectId: route.params.projectId } }"
                prepend-icon="ri-arrow-left-line">Volver</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
  </VRow>

  <PhaseForm v-if="ready" :initial="initialData" :progress="phaseProgress" submit-label="Guardar cambios"
    :errors="validationErrors" :loading="loading" @submit="handleSubmit" />
</template>
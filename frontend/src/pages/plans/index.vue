<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as plansService from '@/services/project-plans.service';
import type { ProjectPlanI } from '@/interfaces/ProjectPlanI';

const route    = useRoute();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

const plan    = ref<ProjectPlanI | null>(null);
const form    = ref({ scope: '', requirements: '', technical_notes: '' });
const editing = ref(false);
const errors  = ref<any>({});

const handleGetData = async () => {
    loader.value = true;
    const response = await plansService.show(projectId());
    if (response.status && response.items) {
        plan.value = response.items;
        form.value = {
            scope:           response.items.scope           ?? '',
            requirements:    response.items.requirements    ?? '',
            technical_notes: response.items.technical_notes ?? '',
        };
    }
    loader.value = false;
};

const handleSave = async () => {
    errors.value = {};
    loader.value = true;
    const response = await plansService.save(projectId(), form.value as any);
    if (response.status) {
        snackbar.value = { show: true, text: 'Plan guardado', color: 'success' };
        plan.value = response.items ?? null;
        editing.value = false;
    } else {
        if ('errors' in response && response.errors) errors.value = response.errors;
        snackbar.value = { show: true, text: response.message, color: 'error' };
    }
    loader.value = false;
};

onMounted(handleGetData);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap gap-2">
              <h4 class="text-h4"><strong>Plan del proyecto</strong></h4>
              <div class="d-flex gap-2">
                <VBtn variant="outlined" size="small"
                  :to="{ name: 'project-detail', params: { projectId: projectId() } }"
                  prepend-icon="ri-arrow-left-line">
                  Proyecto
                </VBtn>
                <VBtn v-if="!editing && canAction('Plan.Store')"
                  variant="flat" size="small" prepend-icon="ri-pencil-line"
                  @click="editing = true">
                  Editar plan
                </VBtn>
              </div>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- Vista (solo lectura) -->
    <VCol v-if="!editing" cols="12" md="8">
      <VCard>
        <VCardText v-if="plan">
          <p class="text-overline text-medium-emphasis mb-1">Alcance</p>
          <p class="mb-4">{{ plan.scope ?? '—' }}</p>
          <p class="text-overline text-medium-emphasis mb-1">Requerimientos</p>
          <p class="mb-4">{{ plan.requirements ?? '—' }}</p>
          <p class="text-overline text-medium-emphasis mb-1">Notas técnicas</p>
          <p>{{ plan.technical_notes ?? '—' }}</p>
        </VCardText>
        <VCardText v-else class="text-medium-emphasis">
          No hay plan definido aún.
          <VBtn v-if="canAction('Plan.Store')" variant="text" @click="editing = true">
            Crear plan
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Formulario de edición -->
    <VCol v-if="editing" cols="12" md="8">
      <VCard>
        <VCardText>
          <VForm @submit.prevent="handleSave">
            <VTextarea v-model="form.scope" label="Alcance" variant="outlined" rows="4"
              :error-messages="errors.scope" class="mb-3" />
            <VTextarea v-model="form.requirements" label="Requerimientos" variant="outlined" rows="4"
              :error-messages="errors.requirements" class="mb-3" />
            <VTextarea v-model="form.technical_notes" label="Notas técnicas" variant="outlined" rows="4"
              :error-messages="errors.technical_notes" class="mb-4" />
            <div class="d-flex gap-2 justify-end">
              <VBtn variant="outlined" @click="editing = false">Cancelar</VBtn>
              <VBtn type="submit" color="primary" :loading="loader">Guardar</VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

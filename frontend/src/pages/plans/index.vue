<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import * as plansService from '@/services/project-plans.service';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

useEnsureCurrentProject();
import type { ProjectPlanI } from '@/interfaces/ProjectPlanI';

const route = useRoute();
const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
const projectId = () => Number(route.params.projectId);

const plan = ref<ProjectPlanI | null>(null);
const form = ref({ scope: '', requirements: '', technical_notes: '' });
const editing = ref(false);
const errors = ref<any>({});

const handleGetData = async () => {
  loader.value = true;
  const response = await plansService.show(projectId());
  if (response.status && response.items) {
    plan.value = response.items;
    form.value = {
      scope: response.items.scope ?? '',
      requirements: response.items.requirements ?? '',
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
                <VBtn v-if="!editing && canAction('project.edit')" variant="flat" size="small"
                  prepend-icon="ri-pencil-line" @click="editing = true">
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
          <div v-if="plan.scope" v-html="plan.scope" class="mb-4 rich-view"></div>
          <p v-else class="mb-4 text-medium-emphasis">—</p>
          <p class="text-overline text-medium-emphasis mb-1">Requerimientos</p>
          <div v-if="plan.requirements" v-html="plan.requirements" class="mb-4 rich-view"></div>
          <p v-else class="mb-4 text-medium-emphasis">—</p>
          <p class="text-overline text-medium-emphasis mb-1">Notas técnicas</p>
          <div v-if="plan.technical_notes" v-html="plan.technical_notes" class="rich-view"></div>
          <p v-else class="text-medium-emphasis">—</p>
        </VCardText>
        <VCardText v-else class="text-medium-emphasis">
          No hay plan definido aún.
          <VBtn v-if="canAction('project.edit')" variant="text" @click="editing = true">
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
            <label class="v-label text-body-2 mb-1 d-block">Alcance</label>
            <RichTextEditor v-model="form.scope" class="mb-3" />
            <div v-if="errors.scope?.length" class="v-messages text-error mb-3 mt-1">{{ errors.scope[0] }}</div>

            <label class="v-label text-body-2 mb-1 d-block">Requerimientos</label>
            <RichTextEditor v-model="form.requirements" class="mb-3" />
            <div v-if="errors.requirements?.length" class="v-messages text-error mb-3 mt-1">{{ errors.requirements[0] }}
            </div>

            <label class="v-label text-body-2 mb-1 d-block">Notas técnicas</label>
            <RichTextEditor v-model="form.technical_notes" class="mb-4" />
            <div v-if="errors.technical_notes?.length" class="v-messages text-error mb-4 mt-1">{{
              errors.technical_notes[0] }}</div>
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

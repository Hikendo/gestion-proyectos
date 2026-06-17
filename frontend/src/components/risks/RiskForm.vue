<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { RiskI, RiskErroresFormI } from '@/interfaces/RiskI';
import type { RiskImpact, RiskProbability } from '@/interfaces/enums';
import * as phasesService from '@/services/project-phases.service';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

const props = defineProps<{
  form: RiskI;
  errores: RiskErroresFormI;
  projectId: number;
}>();

const impacts: { title: string; value: RiskImpact }[] = [
  { title: 'Bajo', value: 'low' },
  { title: 'Medio', value: 'medium' },
  { title: 'Alto', value: 'high' },
  { title: 'Crítico', value: 'critical' },
];

const probabilities: { title: string; value: RiskProbability }[] = [
  { title: 'Baja', value: 'low' },
  { title: 'Media', value: 'medium' },
  { title: 'Alta', value: 'high' },
];

const phases = ref<{ id: number; name: string }[]>([]);

onMounted(async () => {
  const res = await phasesService.index(props.projectId);
  if (res.status && res.items) {
    phases.value = res.items.map((p: any) => ({ id: p.id, name: p.name }));
  }
});
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Datos del riesgo</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.title" :error-messages="errores.title" name="title" label="Título"
            placeholder="Título del riesgo" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.phase_id" :error-messages="errores.phase_id" name="phase_id" :items="phases"
            item-title="name" item-value="id" label="Fase" clearable eager />
        </VCol>

        <VCol cols="12">
          <label class="v-label text-body-2 mb-1 d-block">Descripción</label>
          <RichTextEditor v-model="form.description" />
          <div v-if="errores.description?.length" class="v-messages text-error mt-1">
            {{ errores.description[0] }}
          </div>
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.impact" :error-messages="errores.impact" name="impact" :items="impacts"
            item-title="title" item-value="value" label="Impacto" eager />
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.probability" :error-messages="errores.probability" name="probability"
            :items="probabilities" item-title="title" item-value="value" label="Probabilidad" eager />
        </VCol>

        <VCol cols="12">
          <label class="v-label text-body-2 mb-1 d-block">Plan de mitigación</label>
          <RichTextEditor v-model="form.mitigation_plan" />
          <div v-if="errores.mitigation_plan?.length" class="v-messages text-error mt-1">
            {{ errores.mitigation_plan[0] }}
          </div>
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
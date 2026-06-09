<script setup lang="ts">
import type { RiskI, RiskErroresFormI } from '@/interfaces/RiskI';
import type { RiskImpact, RiskProbability } from '@/interfaces/enums';

defineProps<{
  form: RiskI;
  errores: RiskErroresFormI;
}>();

const impacts: { title: string; value: RiskImpact }[] = [
  { title: 'Bajo',     value: 'low' },
  { title: 'Medio',    value: 'medium' },
  { title: 'Alto',     value: 'high' },
  { title: 'Crítico',  value: 'critical' },
];

const probabilities: { title: string; value: RiskProbability }[] = [
  { title: 'Baja',   value: 'low' },
  { title: 'Media',  value: 'medium' },
  { title: 'Alta',   value: 'high' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Datos del riesgo</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.title" :error-messages="errores.title"
            name="title" label="Título" placeholder="Título del riesgo" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="Descripción del riesgo" rows="3" />
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.impact" :error-messages="errores.impact"
            name="impact" :items="impacts" item-title="title" item-value="value"
            label="Impacto" eager />
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.probability" :error-messages="errores.probability"
            name="probability" :items="probabilities" item-title="title" item-value="value"
            label="Probabilidad" eager />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.mitigation_plan" :error-messages="errores.mitigation_plan"
            name="mitigation_plan" label="Plan de mitigación" placeholder="Describe el plan de mitigación" rows="3" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

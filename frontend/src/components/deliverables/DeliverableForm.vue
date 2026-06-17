<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { DeliverableI, DeliverableErroresFormI } from '@/interfaces/DeliverableI';
import * as phasesService from '@/services/project-phases.service';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

const props = defineProps<{
  form: DeliverableI;
  errores: DeliverableErroresFormI;
  projectId: number;
}>();

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
      <VCardTitle class="text-h6">Datos del entregable</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="5">
          <VTextField v-model="form.name" :error-messages="errores.name" name="name" label="Nombre" variant="outlined"
            density="comfortable" placeholder="Nombre del entregable" />
        </VCol>

        <VCol cols="12" md="3">
          <VSelect v-model="form.phase_id" :error-messages="errores.phase_id" name="phase_id" :items="phases"
            item-title="name" item-value="id" label="Fase" clearable eager />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.delivery_date" :error-messages="errores.delivery_date" name="delivery_date"
            type="date" label="Fecha de entrega" />
        </VCol>

        <VCol cols="12">
          <label class="v-label text-body-2 mb-1 d-block">Descripción</label>
          <RichTextEditor v-model="form.description" />
          <div v-if="errores.description?.length" class="v-messages text-error mt-1">
            {{ errores.description[0] }}
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
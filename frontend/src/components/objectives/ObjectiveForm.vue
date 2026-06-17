<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { ObjectiveI, ObjectiveErroresFormI } from '@/interfaces/ObjectiveI';
import type { ObjectiveType } from '@/interfaces/enums';
import * as phasesService from '@/services/project-phases.service';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

const props = defineProps<{
  form: ObjectiveI;
  errores: ObjectiveErroresFormI;
  projectId: number;
}>();

const types: { title: string; value: ObjectiveType }[] = [
  { title: 'General', value: 'general' },
  { title: 'Específico', value: 'specific' },
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
      <VCardTitle class="text-h6">Datos del objetivo</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="4">
          <VSelect v-model="form.type" :error-messages="errores.type" name="type" :items="types" item-title="title"
            item-value="value" label="Tipo" eager />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.phase_id" :error-messages="errores.phase_id" name="phase_id" :items="phases"
            item-title="name" item-value="id" label="Fase" clearable eager />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.title" :error-messages="errores.title" name="title" label="Título"
            placeholder="Título del objetivo" />
        </VCol>

        <VCol cols="12">
          <label class="v-label text-body-2 mb-1 d-block">Descripción</label>
          <RichTextEditor v-model="form.description" />
          <div v-if="errores.description?.length" class="v-messages text-error mt-1">
            {{ errores.description[0] }}
          </div>
        </VCol>

        <VCol cols="12" md="6" class="d-flex align-center">
          <VSwitch v-model="form.completed" :error-messages="errores.completed" name="completed" label="Completado"
            color="success" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
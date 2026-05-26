<script setup lang="ts">
import type { DeliverableI, DeliverableErroresFormI } from '@/interfaces/DeliverableI';

defineProps<{
  form: DeliverableI;
  errores: DeliverableErroresFormI;
}>();
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Datos del entregable</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="8">
          <VTextField v-model="form.name" :error-messages="errores.name"
            name="name" label="Nombre" placeholder="Nombre del entregable" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.delivery_date" :error-messages="errores.delivery_date"
            name="delivery_date" type="date" label="Fecha de entrega" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description"
            name="description" label="Descripción" placeholder="Descripción del entregable" rows="3" />
        </VCol>

        <VCol cols="12" md="6" class="d-flex align-center">
          <VSwitch v-model="form.approved" :error-messages="errores.approved"
            name="approved" label="Aprobado" color="success" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

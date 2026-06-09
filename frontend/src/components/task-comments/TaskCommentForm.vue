<script setup lang="ts">
import type { TaskCommentI, TaskCommentErroresFormI } from '@/interfaces/TaskCommentI';

defineProps<{
  form: TaskCommentI;
  errores: TaskCommentErroresFormI;
}>();
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Nuevo comentario</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextarea v-model="form.comment" :error-messages="errores.comment"
            name="comment" label="Comentario" placeholder="Escribe tu comentario aquí" rows="4" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Comentar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

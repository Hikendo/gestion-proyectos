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
      <h5 class="text-h5 text-wrap">Nuevo comentario</h5>
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

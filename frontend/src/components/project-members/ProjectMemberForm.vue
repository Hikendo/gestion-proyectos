<script setup lang="ts">
import type { ProjectMemberI, ProjectMemberErroresFormI } from '@/interfaces/ProjectMemberI';
import type { ProjectMemberRole } from '@/interfaces/enums';

defineProps<{
  form: ProjectMemberI;
  errores: ProjectMemberErroresFormI;
}>();

const roles: { title: string; value: ProjectMemberRole }[] = [
  { title: 'Gerente',      value: 'manager' },
  { title: 'Desarrollador', value: 'developer' },
  { title: 'QA',           value: 'qa' },
  { title: 'Soporte',      value: 'support' },
  { title: 'Cliente',      value: 'client' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <h5 class="text-h5 text-wrap">Miembro del proyecto</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="6">
          <VTextField v-model="form.user_id" :error-messages="errores.user_id"
            name="user_id" type="number" label="Usuario (ID)" placeholder="ID del usuario" />
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.role" :error-messages="errores.role"
            name="role" :items="roles" item-title="title" item-value="value"
            label="Rol en el proyecto" eager />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

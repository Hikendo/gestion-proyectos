<script setup lang="ts">
import type { UserI, UserErroresFormI } from '@/interfaces/UserI';

defineProps<{
  form: UserI & { password?: string; password_confirmation?: string; role?: string };
  errores: UserErroresFormI;
}>();

const roles = [
  { title: 'Super Admin', value: 'super-admin' },
  { title: 'Project Manager', value: 'project-manager' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Datos del usuario</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12" md="6">
          <VTextField v-model="form.name" :error-messages="errores.name" name="name" label="Nombre" variant="outlined" density="comfortable"
            placeholder="Nombre completo" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.email" :error-messages="errores.email" name="email" type="email" label="Email" variant="outlined" density="comfortable"
            placeholder="email@example.com" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.password" :error-messages="errores.password" name="password" type="password"
            label="Contraseña" placeholder="Mínimo 8 caracteres" />
        </VCol>

        <VCol cols="12" md="6">
          <VTextField v-model="form.password_confirmation" :error-messages="errores.password_confirmation"
            name="password_confirmation" type="password" label="Confirmar contraseña"
            placeholder="Repetir contraseña" />
        </VCol>

        <VCol cols="12" md="6">
          <VSelect v-model="form.role" :error-messages="errores.role" name="role" :items="roles" item-title="title"
            item-value="value" label="Rol" eager />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

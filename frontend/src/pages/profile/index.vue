<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/store/useAuthStore';

const authStore = useAuthStore();
const { authUser } = storeToRefs(authStore);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <template #prepend>
            <VAvatar color="primary" variant="tonal" size="56">
              <VIcon icon="ri-user-fill" size="32" />
            </VAvatar>
          </template>
          <VCardTitle>{{ authUser?.name }}</VCardTitle>
          <VCardSubtitle>{{ authUser?.email }}</VCardSubtitle>
          <template #append>
            <VBtn variant="outlined" :to="{ name: 'profile-edit' }" prepend-icon="ri-pencil-line">
              Editar perfil
            </VBtn>
          </template>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12" md="6">
      <VCard title="Información">
        <VCardText>
          <VList>
            <VListItem title="Nombre" :subtitle="authUser?.name" />
            <VListItem title="Email" :subtitle="authUser?.email" />
            <VListItem title="Roles" :subtitle="authUser?.roles?.join(', ') ?? '—'" />
          </VList>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

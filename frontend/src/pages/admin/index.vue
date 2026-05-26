<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/store/useAuthStore';

const authStore = useAuthStore();
const { isSuperAdmin } = storeToRefs(authStore);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <template #prepend>
            <VAvatar color="primary" variant="tonal" size="48">
              <VIcon icon="mdi-shield-crown" />
            </VAvatar>
          </template>
          <VCardTitle>Panel de Administración</VCardTitle>
          <VCardSubtitle>Gestión global del sistema</VCardSubtitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol v-for="item in [
      { title: 'Usuarios', icon: 'mdi-account-multiple', route: 'admin-users', color: 'primary' },
      { title: 'Roles', icon: 'mdi-shield-key-outline', route: 'roles', color: 'info' },
    ]" :key="item.title" cols="12" sm="6" md="4">
      <VCard :to="{ name: item.route }" class="cursor-pointer">
        <VCardText class="d-flex align-center gap-4">
          <VAvatar :color="item.color" variant="tonal" size="48">
            <VIcon :icon="item.icon" />
          </VAvatar>
          <div class="text-h6">{{ item.title }}</div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

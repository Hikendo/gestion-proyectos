<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as usersService from '@/services/users.service';

const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);

const data = ref<any[]>([]);
const page = ref(1);
const lastPage = ref(1);
const query = ref('');

const handleGetData = async () => {
    loader.value = true;
    const response = await usersService.index({ page: page.value, query: query.value });
    if (response.status && response.items) {
        data.value = (response.items as any).data ?? [];
        lastPage.value = (response.items as any).last_page ?? 1;
    }
    loader.value = false;
};

onMounted(handleGetData);
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>
            <div class="d-flex justify-space-between">
              <h4 class="text-h4">Usuarios del sistema</h4>
              <VBtn variant="flat" :to="{ name: 'admin-users-new' }" prepend-icon="mdi-plus"
                v-if="canAction('User.Store')">Nuevo usuario</VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <form @submit.prevent="() => { page.value = 1; handleGetData(); }">
            <VTextField label="Buscar usuario" prepend-inner-icon="mdi-magnify" clearable
              v-model="query" variant="outlined" density="compact" />
          </form>
        </VCardItem>
        <VDivider />
        <VCardText class="pa-0">
          <VTable height="500" fixed-header>
            <thead>
              <tr>
                <th>ID</th><th>Nombre</th><th>Email</th><th>Roles</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in data" :key="user.id">
                <td>{{ user.id }}</td>
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>{{ user.roles?.join(', ') }}</td>
                <td>
                  <VBtn icon size="small" color="warning"
                    :to="{ name: 'admin-users-id', params: { id: user.id } }">
                    <VIcon icon="mdi-pencil" />
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>
    </VCol>
    <VPagination class="mt-4 mr-3" color="primary" v-model="page"
      :total-visible="7" :length="lastPage" style="margin-left:auto;"
      @update:model-value="handleGetData" />
  </VRow>
</template>

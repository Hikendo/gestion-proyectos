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
  <VRow class="bg-background">
    <!-- Encabezado -->
    <VCol cols="12">
      <VCard color="surface" variant="flat" class="border-sm border-border">
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between align-center flex-wrap gap-2">
              <h4 class="text-h4 text-text font-weight-bold">Usuarios del sistema</h4>
              <VBtn
                color="primary"

                :to="{ name: 'admin-users-new' }"
                prepend-icon="ri-add-line"
                v-if="canAction('User.Store')"
              >
                Nuevo usuario
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- Buscador -->
    <VCol cols="12">
      <VCard color="surface" variant="flat" class="border-sm border-border">
        <VCardItem>
          <form @submit.prevent="() => { page = 1; handleGetData(); }">
            <VTextField
              label="Buscar usuario"
              prepend-inner-icon="ri-search-line"
              clearable
              v-model="query"
              variant="outlined"
              density="compact"
              hide-details
              color="primary"
              class="text-text"
            />
          </form>
        </VCardItem>
      </VCard>
    </VCol>

    <!-- Tabla Estilizada con el Tema -->
    <VCol cols="12">
      <VCard color="surface" variant="flat" class="border-sm border-border">
        <VCardText class="pa-0">
          <VTable height="500" fixed-header class="bg-surface text-text">
            <thead>
              <tr class="border-bottom-sm border-border">
                <th class="text-subtitle-2 font-weight-bold text-text bg-surface">Nombre</th>
                <th class="text-subtitle-2 font-weight-bold text-text bg-surface">Email</th>
                <th class="text-subtitle-2 font-weight-bold text-text bg-surface">Roles</th>
                <th class="text-subtitle-2 font-weight-bold text-text bg-surface">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in data"
                :key="user.id"
                class="border-bottom-sm border-border"
              >
                <!-- Nombre con Avatar Inicial usando 'accent' -->
                <td class="py-2">
                  <div class="d-flex align-center gap-3">
                    <VAvatar color="accent" size="32" class="text-caption font-weight-bold text-white">
                      {{ user.name?.charAt(0).toUpperCase() }}
                    </VAvatar>
                    <span class="font-weight-medium text-text">{{ user.name }}</span>
                  </div>
                </td>

                <!-- Email -->
                <td class="text-medium-emphasis">{{ user.email }}</td>

                <!-- Roles usando Chips con color 'secondary' o 'primary' -->
                <td>
                  <div class="d-flex flex-wrap gap-1" v-if="user.roles && user.roles.length > 0">
                    <VChip
                      v-for="(role, index) in user.roles"
                      :key="index"
                      size="x-small"
                      variant="tonal"
                      color="primary"
                      class="font-weight-medium text-uppercase"
                    >
                      {{ role }}
                    </VChip>
                  </div>
                  <span v-else class="text-caption text-disabled italic">Sin roles</span>
                </td>

                <!-- Acciones -->
                <td>
                  <VBtn
                    icon
                    size="small"
                    variant="text"
                    color="warning"
                    title="Editar usuario"
                    :to="{ name: 'admin-users-id', params: { id: user.id } }"
                  >
                    <VIcon icon="ri-pencil-line"/>
                  </VBtn>
                </td>
              </tr>

              <!-- Estado vacío dentro de la tabla -->
              <tr v-if="data.length === 0">
                <td colspan="4" class="text-center text-disabled py-6">
                  No se encontraron usuarios en el sistema.
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCardText>
      </VCard>
    </VCol>

    <!-- Paginación -->
    <VPagination
      class="mt-4 mr-3"
      color="primary"
      v-model="page"
      :total-visible="7"
      :length="lastPage"
      style="margin-left:auto;"
      @update:model-value="handleGetData"
    />
  </VRow>
</template>

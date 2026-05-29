<script setup lang="ts">
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import { useEnsureCurrentProject } from '@/composables/useEnsureCurrentProject';
import { useProjects } from '@/composables/useProjects';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import * as projectsService from '@/services/projects.service';
import type { ProjectI } from '@/interfaces/ProjectI';

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const authStore = useAuthStore();
const { loader, snackbar } = storeToRefs(appStore);

const { errores, form, handleUpdate } = useProjects();
// Garantiza currentProject en el store (menú lateral) sin doble loader
useEnsureCurrentProject();

const projectId = Number(route.params.projectId);

onMounted(async () => {
    loader.value = true;
    const response = await projectsService.show(projectId);
    if (response.status && response.items) {
        form.value = response.items as ProjectI;
        // También actualizamos el store con el dato fresco
        authStore.setCurrentProject(response.items as ProjectI);
    } else {
        snackbar.value = { show: true, text: 'Proyecto no encontrado', color: 'error' };
        router.push({ name: 'projects' });
    }
    loader.value = false;
});
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem class="pb-4">
          <VCardTitle>
            <div class="d-flex justify-space-between flex-wrap">
              <h4 class="text-h4 text-wrap">
                Editar <strong>Proyecto</strong>
              </h4>
              <VBtn variant="outlined" :to="{ name: 'project-detail', params: { projectId } }">
                Volver
              </VBtn>
            </div>
          </VCardTitle>
        </VCardItem>
      </VCard>
    </VCol>
    <VCol cols="12">
      <VForm @submit.prevent="handleUpdate">
        <ProjectForm :form="form" :errores="errores" />
      </VForm>
    </VCol>
  </VRow>
</template>

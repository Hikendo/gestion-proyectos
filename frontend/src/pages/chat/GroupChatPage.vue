<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as projectsService from '@/services/projects.service';
import GroupChat from '@/components/chat/GroupChat.vue';

const route = useRoute();
const appStore = useAppStore();
const authStore = useAuthStore();

const projectId = computed(() => Number(route.params.projectId));
const project = computed(() => authStore.currentProject);

onMounted(async () => {
    if (!project.value || project.value.id !== projectId.value) {
        appStore.loader = true;
        const response = await projectsService.show(projectId.value);
        if (response.status && response.items) {
            authStore.setCurrentProject(response.items);
        }
        appStore.loader = false;
    }
});
</script>

<template>
    <div>
        <VRow class="mb-4">
            <VCol cols="12">
                <div class="d-flex align-center gap-3">
                    <VBtn variant="text" icon="ri-arrow-left-line"
                        :to="{ name: 'project-detail', params: { projectId } }" />
                    <div>
                        <h2 class="text-h5 mb-0">
                            <VIcon icon="ri-chat-1-line" class="me-2" />
                            Chat del equipo
                        </h2>
                        <p class="text-caption text-medium-emphasis mb-0" v-if="project">{{ project.name }}</p>
                    </div>
                </div>
            </VCol>
        </VRow>

        <VRow>
            <VCol cols="12">
                <VCard style="height: calc(100vh - 200px); min-height: 400px">
                    <GroupChat v-if="projectId" :project-id="projectId" />
                </VCard>
            </VCol>
        </VRow>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAppStore } from '@/store/useAppStore';
import { useAuthStore } from '@/store/useAuthStore';
import * as projectsService from '@/services/projects.service';
import * as membersService from '@/services/project-members.service';
import PrivateChat from '@/components/chat/PrivateChat.vue';

const route = useRoute();
const appStore = useAppStore();
const authStore = useAuthStore();

const projectId = computed(() => Number(route.params.projectId));
const project = computed(() => authStore.currentProject);
const projectMembers = ref<Array<{ id: number; name: string }>>([]);

onMounted(async () => {
    appStore.loader = true;
    try {
        if (!project.value || project.value.id !== projectId.value) {
            const response = await projectsService.show(projectId.value);
            if (response.status && response.items) {
                authStore.setCurrentProject(response.items);
            }
        }

        // Load project members for the "New conversation" dialog
        const membersResponse = await membersService.index(projectId.value);
        if (membersResponse.status && membersResponse.items) {
            projectMembers.value = (membersResponse.items as any[]).map((m: any) => ({
                id: m.user?.id ?? m.user_id,
                name: m.user?.name ?? `Usuario ${m.user_id}`,
            }));
        }
    } finally {
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
                            <VIcon icon="ri-message-2-line" class="me-2" />
                            Chats privados
                        </h2>
                        <p class="text-caption text-medium-emphasis mb-0" v-if="project">{{ project.name }}</p>
                    </div>
                </div>
            </VCol>
        </VRow>

        <VRow>
            <VCol cols="12">
                <VCard style="height: calc(100vh - 200px); min-height: 400px">
                    <PrivateChat v-if="projectId" :project-id="projectId" :project-members="projectMembers" />
                </VCard>
            </VCol>
        </VRow>
    </div>
</template>
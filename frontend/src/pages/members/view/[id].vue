<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { canAction } from '@/helpers/canAction';
import * as membersService from '@/services/project-members.service';
import * as usersService from '@/services/users.service';
import type { UserMetricI } from '@/interfaces/UserMetricI';

const route = useRoute();
const appStore = useAppStore();
const { loader } = storeToRefs(appStore);
const member = ref<any>(null);
const metrics = ref<UserMetricI | null>(null);
const projectId = Number(route.params.projectId);
const id = Number(route.params.id);

onMounted(async () => {
    loader.value = true;
    const r = await membersService.index(projectId);
    if (r.status && r.items) {
        const items = (r.items as any).data ?? (Array.isArray(r.items) ? r.items : []);
        member.value = items.find((m: any) => m.id === id) ?? null;
    }
    if (member.value) {
        const userId = member.value.user_id ?? member.value.user?.id;
        if (userId) {
            const m = await usersService.metrics(userId);
            if (m.status && m.items) metrics.value = m.items as UserMetricI;
        }
    }
    loader.value = false;
});
</script>

<template>
    <VRow v-if="member">
        <VCol cols="12">
            <VCard>
                <VCardItem>
                    <VCardTitle class="d-flex justify-space-between flex-wrap align-center">
                        <span class="d-flex align-center gap-2">
                            <VIcon icon="ri-group-line" color="primary" />Miembro: {{ member.user?.name ??
                            member.user_id }}
                        </span>
                        <div class="d-flex gap-2">
                            <VBtn variant="outlined" prepend-icon="ri-arrow-left-line"
                                :to="{ name: 'members', params: { projectId } }">Volver</VBtn>
                            <VBtn v-if="canAction('project.assign-members')" variant="tonal" color="warning"
                                :to="{ name: 'members-id', params: { projectId, id } }" prepend-icon="ri-pencil-line">Editar
                            </VBtn>
                        </div>
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Email</div>
                            <div class="text-body-1 mt-1">{{ member.user?.email ?? '—' }}</div>
                        </VCol>
                        <VCol cols="12" md="4">
                            <div class="text-caption text-medium-emphasis">Rol</div>
                            <VChip size="small" class="mt-1">{{ member.role }}</VChip>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
        <VCol cols="12" v-if="metrics">
            <VCard>
                <VCardItem>
                    <VCardTitle class="d-flex align-center gap-2">
                        <VIcon icon="ri-bar-chart-line" color="primary" />Métricas del miembro
                    </VCardTitle>
                </VCardItem>
                <VDivider />
                <VCardText>
                    <VRow>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Tareas asignadas</div>
                            <div class="text-h5 font-weight-bold mt-1">{{ metrics.tasks_assigned ?? 0 }}</div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Tareas completadas</div>
                            <div class="text-h5 font-weight-bold mt-1 text-success">{{ metrics.tasks_completed ?? 0 }}
                            </div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Tickets asignados</div>
                            <div class="text-h5 font-weight-bold mt-1">{{ metrics.tickets_assigned ?? 0 }}</div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Tickets resueltos</div>
                            <div class="text-h5 font-weight-bold mt-1 text-success">{{ metrics.tickets_resolved ?? 0 }}
                            </div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Bloqueadores creados</div>
                            <div class="text-h5 font-weight-bold mt-1">{{ metrics.blockers_created ?? 0 }}</div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Bloqueadores resueltos</div>
                            <div class="text-h5 font-weight-bold mt-1 text-success">{{ metrics.blockers_resolved ?? 0 }}
                            </div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Horas registradas</div>
                            <div class="text-h5 font-weight-bold mt-1">{{ metrics.total_hours ?? 0 }}h</div>
                        </VCol>
                        <VCol cols="6" md="3">
                            <div class="text-caption text-medium-emphasis">Proyectos</div>
                            <div class="text-h5 font-weight-bold mt-1">{{ metrics.projects_count ?? 0 }}</div>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>
</template>
<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const projectId = computed(() => Number(route.params.id));

const projectFeatureTabs = [
    { name: 'project-members', label: 'Members' },
    { name: 'project-phases', label: 'Phases' },
    { name: 'project-plan', label: 'Plan' },
    { name: 'project-objectives', label: 'Objectives' },
    { name: 'project-milestones', label: 'Milestones' },
    { name: 'project-deliverables', label: 'Deliverables' },
    { name: 'project-tasks', label: 'Tasks' },
    { name: 'project-tickets', label: 'Tickets' },
    { name: 'project-risks', label: 'Risks' },
    { name: 'project-blockers', label: 'Blockers' },
];
</script>

<template>
    <section class="page-grid">
        <article class="feature-panel">
            <div class="feature-panel__header">
                <div>
                    <h2>Proyecto #{{ projectId }}</h2>
                    <p>Features reales del backend para gestion de proyecto.</p>
                </div>
                <RouterLink class="button secondary" :to="{ name: 'projects' }">Volver a proyectos</RouterLink>
            </div>

            <nav class="project-tabs">
                <RouterLink
                    v-for="tab in projectFeatureTabs"
                    :key="tab.name"
                    :to="{ name: tab.name, params: { id: projectId } }"
                    class="project-tabs__link"
                >
                    {{ tab.label }}
                </RouterLink>
            </nav>
        </article>

        <RouterView />
    </section>
</template>

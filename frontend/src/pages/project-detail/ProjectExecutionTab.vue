<script setup>
import MembersFeature from '../../features/projects/members/MembersFeature.vue';
import PhasesFeature from '../../features/projects/phases/PhasesFeature.vue';
import TasksFeature from '../../features/projects/tasks/TasksFeature.vue';
import TicketsFeature from '../../features/projects/tickets/TicketsFeature.vue';

defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});
</script>

<template>
  <section class="page-grid">
    <MembersFeature :project-id="projectId" />
    <PhasesFeature :project-id="projectId" />
    <TasksFeature :project-id="projectId" />
    <TicketsFeature :project-id="projectId" />
  </section>
</template>

<script setup>
import PlansFeature from '../../features/projects/plans/PlansFeature.vue';
import ObjectivesFeature from '../../features/projects/objectives/ObjectivesFeature.vue';
import MilestonesFeature from '../../features/projects/milestones/MilestonesFeature.vue';

defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});
</script>

<template>
  <section class="page-grid">
    <PlansFeature :project-id="projectId" />
    <ObjectivesFeature :project-id="projectId" />
    <MilestonesFeature :project-id="projectId" />
  </section>
</template>

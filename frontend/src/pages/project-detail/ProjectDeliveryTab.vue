<script setup>
import DeliverablesFeature from '../../features/projects/deliverables/DeliverablesFeature.vue';
import RisksFeature from '../../features/projects/risks/RisksFeature.vue';
import BlockersFeature from '../../features/projects/blockers/BlockersFeature.vue';

defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});
</script>

<template>
  <section class="page-grid">
    <DeliverablesFeature :project-id="projectId" />
    <RisksFeature :project-id="projectId" />
    <BlockersFeature :project-id="projectId" />
  </section>
</template>

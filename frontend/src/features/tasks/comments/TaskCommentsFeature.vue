<script setup>
import ServiceProbe from '../../../components/ServiceProbe.vue';
import { useTaskCommentsService } from '../../../composables';

const { call } = useTaskCommentsService();
</script>

<template>
  <ServiceProbe
    title="Tasks / Comments"
    description="Componente dedicado al servicio de comentarios de tarea."
    primary-label="Task ID"
    :load="({ primary }) => call('list', primary)"
  />
</template>
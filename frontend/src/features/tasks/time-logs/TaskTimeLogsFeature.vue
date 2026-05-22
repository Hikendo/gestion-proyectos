<script setup>
import ServiceProbe from '../../../components/ServiceProbe.vue';
import { useTaskTimeLogsService } from '../../../composables';

const { call } = useTaskTimeLogsService();
</script>

<template>
  <ServiceProbe
    title="Tasks / Time Logs"
    description="Componente dedicado al servicio de tiempos de tarea."
    primary-label="Task ID"
    :load="({ primary }) => call('list', primary)"
  />
</template>
<script setup lang="ts">
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route  = useRoute();
const router = useRouter();

// El plan es un recurso singular; redirigir al index donde está el formulario
onMounted(() => {
    router.replace({ name: 'plans', params: { projectId: route.params.projectId } });
});
</script>

<template>
  <div />
</template>

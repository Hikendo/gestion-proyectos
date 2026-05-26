<script setup lang="ts">
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route  = useRoute();
const router = useRouter();

onMounted(() => {
    router.replace({ name: 'plans', params: { projectId: route.params.projectId } });
});
</script>

<template>
  <div />
</template>

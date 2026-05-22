<script setup>
const props = defineProps({
    errors: {
        type: Array,
        default: () => [],
    },
});

const hasErrors = computed(() => props.errors.length > 0);
</script>

<script>
import { computed } from 'vue';

export default {
    name: 'ValidationErrors',
};
</script>

<template>
  <ul v-if="hasErrors" class="validation-errors">
    <li v-for="error in errors" :key="error">{{ error }}</li>
  </ul>
</template>

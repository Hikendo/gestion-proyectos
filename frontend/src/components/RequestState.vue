<script setup>
defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    errorMessage: {
        type: String,
        default: '',
    },
    successMessage: {
        type: String,
        default: '',
    },
});
</script>

<template>
  <div class="request-state">
    <p v-if="loading" class="request-state__loading">Procesando...</p>
    <p v-if="errorMessage" class="request-state__error">{{ errorMessage }}</p>
    <p v-if="successMessage" class="request-state__success">{{ successMessage }}</p>
  </div>
</template>

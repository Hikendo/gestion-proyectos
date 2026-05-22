<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
  <section class="feature-panel">
    <header class="feature-panel__header">
      <div>
        <h2>{{ title }}</h2>
        <p v-if="description">{{ description }}</p>
      </div>

      <div class="feature-panel__actions">
        <slot name="actions" />
      </div>
    </header>

    <div class="feature-panel__body">
      <slot />
    </div>
  </section>
</template>

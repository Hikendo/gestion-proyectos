<script setup>
defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});
</script>

<template>
  <section class="page-grid">
    <p class="feature-copy">
      Usa las pestañas superiores para trabajar por secciones del proyecto {{ projectId }} sin concentrar toda la operacion en una sola pantalla.
    </p>
  </section>
</template>

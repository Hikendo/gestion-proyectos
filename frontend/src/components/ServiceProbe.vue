<script setup>
import { reactive, ref, watch } from 'vue';
import FeaturePanel from './FeaturePanel.vue';
import RequestState from './RequestState.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    primaryLabel: {
        type: String,
        default: 'Project ID',
    },
    primaryValue: {
        type: Number,
        default: 1,
    },
    showPrimaryInput: {
        type: Boolean,
        default: true,
    },
    secondaryLabel: {
        type: String,
        default: '',
    },
    secondaryValue: {
        type: Number,
        default: 1,
    },
    showSecondaryInput: {
        type: Boolean,
        default: true,
    },
    load: {
        type: Function,
        required: true,
    },
});

const state = reactive({
    primary: props.primaryValue,
    secondary: props.secondaryValue,
});

watch(
    () => props.primaryValue,
    (value) => {
        state.primary = value;
    },
);

watch(
    () => props.secondaryValue,
    (value) => {
        state.secondary = value;
    },
);

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

async function handleLoad() {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        await props.load({
            primary: state.primary,
            secondary: state.secondary,
        });
        successMessage.value = 'Consulta ejecutada correctamente.';
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'No se pudo completar la consulta.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
  <FeaturePanel :title="title" :description="description">
        <div v-if="showPrimaryInput || (secondaryLabel && showSecondaryInput)" class="inline-fields">
            <label v-if="showPrimaryInput">
        <span>{{ primaryLabel }}</span>
        <input v-model.number="state.primary" min="1" type="number">
      </label>

            <label v-if="secondaryLabel && showSecondaryInput">
        <span>{{ secondaryLabel }}</span>
        <input v-model.number="state.secondary" min="1" type="number">
      </label>
    </div>

    <button class="button primary" :disabled="loading" type="button" @click="handleLoad">Probar servicio</button>
    <RequestState :loading="loading" :error-message="errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>

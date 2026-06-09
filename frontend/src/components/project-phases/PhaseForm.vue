<script setup lang="ts">
import { ref } from 'vue';
import type { ProjectPhaseI } from '@/interfaces/ProjectPhaseI';

type PhaseFormFields = Pick<ProjectPhaseI, 'name' | 'start_date' | 'end_date' | 'progress'>;

const props = withDefaults(defineProps<{
    initial?: PhaseFormFields;
    submitLabel?: string;
    errors?: Record<string, string[]>;
    loading?: boolean;
}>(), {
    initial: () => ({ name: '', progress: 0, start_date: null, end_date: null }),
    submitLabel: 'Guardar',
    errors: () => ({}),
    loading: false,
});

const emit = defineEmits<{
    (e: 'submit', form: PhaseFormFields): void;
}>();

const form = ref<PhaseFormFields>({ ...props.initial });

const confirmVisible = ref(false);

function handleSubmit() {
    confirmVisible.value = true;
}

function confirm() {
    confirmVisible.value = false;
    emit('submit', { ...form.value });
}

function cancel() {
    confirmVisible.value = false;
}
</script>

<template>
    <VRow>
        <VCol cols="12" md="8">
            <VCard>
                <VCardText>
                    <VForm @submit.prevent="handleSubmit">
                        <VTextField v-model="form.name" label="Nombre de la fase" variant="outlined"
                            :error-messages="errors.name" class="mb-3" required />

                        <VRow>
                            <VCol cols="12" md="6">
                                <VTextField v-model="form.start_date" label="Fecha inicio" variant="outlined"
                                    density="comfortable" type="date" :error-messages="errors.start_date"
                                    class="mb-3" />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField v-model="form.end_date" label="Fecha fin" type="date" variant="outlined"
                                    :error-messages="errors.end_date" class="mb-3" />
                            </VCol>
                        </VRow>

                        <div class="mb-4">
                            <label class="text-body-2 d-block mb-1">
                                Progreso ({{ form.progress }}%)
                            </label>
                            <VSlider v-model="form.progress" min="0" max="100" step="5" thumb-label color="primary"
                                :error-messages="errors.progress" />
                        </div>

                        <VBtn type="submit" color="primary" block :loading="loading">
                            {{ submitLabel }}
                        </VBtn>
                    </VForm>
                </VCardText>
            </VCard>
        </VCol>

        <VDialog v-model="confirmVisible" persistent max-width="400">
            <VCard>
                <VCardTitle class="text-h6">Confirmar acción</VCardTitle>
                <VCardText>¿Deseas guardar los cambios?</VCardText>
                <VCardActions class="justify-end gap-2 pb-4 px-4">
                    <VBtn variant="outlined" @click="cancel">Cancelar</VBtn>
                    <VBtn color="primary" variant="flat" @click="confirm">Confirmar</VBtn>
                </VCardActions>
            </VCard>
        </VDialog>
    </VRow>
</template>
<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import FeaturePanel from '../../../components/FeaturePanel.vue';
import RequestState from '../../../components/RequestState.vue';
import ValidationErrors from '../../../components/ValidationErrors.vue';
import { useProjectDeliverablesService } from '../../../composables';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const deliverablesQuery = useProjectDeliverablesService();
const deliverablesMutation = useProjectDeliverablesService();
const deliverables = ref([]);
const successMessage = ref('');
const editingId = ref(null);
const form = reactive({
    name: '',
    description: '',
    delivery_date: '',
});

const isEditing = computed(() => editingId.value !== null);

async function loadDeliverables() {
    const response = await deliverablesQuery.call('list', props.projectId);

    if (response) {
        deliverables.value = response.data;
    }
}

function resetForm() {
    editingId.value = null;
    form.name = '';
    form.description = '';
    form.delivery_date = '';
}

function startEdit(deliverable) {
    editingId.value = deliverable.id;
    form.name = deliverable.name || '';
    form.description = deliverable.description || '';
    form.delivery_date = deliverable.delivery_date || '';
}

async function handleSubmit() {
    successMessage.value = '';

    const payload = {
        name: form.name,
        description: form.description || null,
        delivery_date: form.delivery_date || null,
    };

    const response = isEditing.value
        ? await deliverablesMutation.call('update', props.projectId, editingId.value, payload)
        : await deliverablesMutation.call('create', props.projectId, payload);

    if (response) {
        successMessage.value = isEditing.value ? 'Entregable actualizado correctamente.' : 'Entregable creado correctamente.';
        resetForm();
        await loadDeliverables();
    }
}

async function handleApprove(deliverable) {
    successMessage.value = '';
    const response = await deliverablesMutation.call('approve', props.projectId, deliverable.id);

    if (response) {
        successMessage.value = 'Entregable aprobado correctamente.';
        await loadDeliverables();
    }
}

onMounted(loadDeliverables);

watch(
    () => props.projectId,
    () => {
        resetForm();
        loadDeliverables();
    },
);
</script>

<template>
  <FeaturePanel title="Projects / Deliverables" description="Alta, edicion y aprobacion de entregables del proyecto.">
    <template #actions>
      <button class="button primary" :disabled="deliverablesQuery.loading" type="button" @click="loadDeliverables">Recargar</button>
    </template>

    <form class="form-grid" @submit.prevent="handleSubmit">
      <div class="inline-fields">
        <label>
          <span>Nombre</span>
          <input v-model="form.name" type="text" placeholder="Manual operativo v1">
          <ValidationErrors :errors="deliverablesMutation.validationErrors.name || []" />
        </label>

        <label>
          <span>Fecha de entrega</span>
          <input v-model="form.delivery_date" type="date">
          <ValidationErrors :errors="deliverablesMutation.validationErrors.delivery_date || []" />
        </label>
      </div>

      <label>
        <span>Descripcion</span>
        <textarea v-model="form.description" rows="4" />
        <ValidationErrors :errors="deliverablesMutation.validationErrors.description || []" />
      </label>

      <div class="form-actions">
        <button class="button primary" :disabled="deliverablesMutation.loading" type="submit">{{ isEditing ? 'Actualizar entregable' : 'Crear entregable' }}</button>
        <button class="button secondary" type="button" @click="resetForm">Cancelar</button>
      </div>
    </form>

    <ul v-if="deliverables.length" class="entity-list">
      <li v-for="deliverable in deliverables" :key="deliverable.id">
        <div>
          <strong>{{ deliverable.name }}</strong>
          <span>{{ deliverable.delivery_date || 'sin fecha' }} · {{ deliverable.approved ? 'aprobado' : 'pendiente' }}</span>
        </div>
        <div class="item-actions">
          <button class="button secondary" type="button" @click="startEdit(deliverable)">Editar</button>
          <button v-if="!deliverable.approved" class="button primary" :disabled="deliverablesMutation.loading" type="button" @click="handleApprove(deliverable)">Aprobar</button>
        </div>
      </li>
    </ul>

    <p class="feature-copy">La API de deliverables no expone un endpoint de eliminacion; la accion secundaria disponible es aprobar.</p>

    <p v-if="!deliverables.length && !deliverablesQuery.loading" class="feature-copy">No hay entregables registrados para este proyecto.</p>

    <RequestState :loading="deliverablesQuery.loading || deliverablesMutation.loading" :error-message="deliverablesMutation.errorMessage || deliverablesQuery.errorMessage" :success-message="successMessage" />
  </FeaturePanel>
</template>

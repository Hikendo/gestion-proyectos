<script setup lang="ts">
import { ref, onMounted, toRef, computed } from 'vue';
import type { TicketI, TicketErroresFormI } from '@/interfaces/TicketI';
import type { TicketStatus, TicketPriority } from '@/interfaces/enums';
import { membersAsUsers } from '@/services/project-members.service';
import { useFieldLock } from '@/composables/useFieldLock';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

const props = defineProps<{
  form: TicketI;
  errores: TicketErroresFormI;
  projectId: number;
}>();

const emit = defineEmits<{
  (e: 'update:attachments', files: File[]): void;
}>();

function onFilesChanged(event: Event): void {
  const input = event.target as HTMLInputElement;
  if (input.files) {
    emit('update:attachments', Array.from(input.files));
  }
}

// Cuando se crea un nuevo ticket (id === 0), no hay field_permissions del backend
const isNewTicket = computed(() => !props.form.id || props.form.id === 0);
const fieldPermissions = toRef(() => {
  if (isNewTicket.value) {
    return {
      title: true, description: true, status: true, priority: true,
      assigned_to: true,
    };
  }
  return (props.form as any).field_permissions ?? {};
});
const fl = useFieldLock(fieldPermissions);

const users = ref<{ id: number; name: string; email: string }[]>([]);

onMounted(async () => {
  const response = await membersAsUsers(props.projectId);
  if (response.status && response.items) {
    users.value = response.items;
  }
});

const statuses: { title: string; value: TicketStatus }[] = [
  { title: 'Abierto', value: 'open' },
  { title: 'En progreso', value: 'in_progress' },
  { title: 'Resuelto', value: 'resolved' },
  { title: 'Cerrado', value: 'closed' },
];

const priorities: { title: string; value: TicketPriority }[] = [
  { title: 'Baja', value: 'low' },
  { title: 'Media', value: 'medium' },
  { title: 'Alta', value: 'high' },
  { title: 'Crítica', value: 'critical' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Datos del ticket</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.subject" :error-messages="errores.subject" name="subject" label="Asunto"
            variant="outlined" density="comfortable" placeholder="Asunto del ticket" />
        </VCol>

        <VCol cols="12">
          <label class="v-label text-body-2 mb-1 d-block">Descripción</label>
          <RichTextEditor v-model="form.description" />
          <div v-if="errores.description?.length" class="v-messages text-error mt-1">
            {{ errores.description[0] }}
          </div>
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.status" :error-messages="errores.status" name="status" :items="statuses"
            item-title="title" item-value="value" label="Estado" variant="outlined" density="comfortable" eager
            :disabled="!fl.status.value" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.priority" :error-messages="errores.priority" name="priority" :items="priorities"
            item-title="title" item-value="value" label="Prioridad" variant="outlined" density="comfortable" eager
            :disabled="!fl.priority.value" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.assigned_to" :error-messages="errores.assigned_to" :items="users" item-title="name"
            item-value="id" name="assigned_to" label="Asignado a" placeholder="Selecciona un usuario" variant="outlined"
            density="comfortable" clearable eager :disabled="!fl.assigned_to.value">
            <template #item="{ item, props: ip }">
              <VListItem v-bind="ip">
                <template #prepend>
                  <VAvatar size="28" color="primary" variant="tonal">
                    <span style="font-size: 0.6rem; font-weight: 700;">
                      {{item.name.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase()}}
                    </span>
                  </VAvatar>
                </template>
                <VListItemSubtitle>{{ item.email }}</VListItemSubtitle>
              </VListItem>
            </template>
          </VSelect>
        </VCol>

        <VCol cols="12">
          <VFileInput label="Archivos adjuntos (PDF, imágenes, ZIP, DOCX)" variant="outlined" density="comfortable"
            multiple accept=".pdf,.jpeg,.jpg,.png,.zip,.docx,.xlsx" :max-file-size="10240"
            prepend-icon="ri-attachment-2" @change="onFilesChanged" />
        </VCol>

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
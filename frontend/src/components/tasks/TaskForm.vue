<script setup lang="ts">
import { ref, onMounted, toRef, computed } from 'vue';
import type { TaskI, TaskErroresFormI } from '@/interfaces/TaskI';
import type { TaskStatus, TaskPriority } from '@/interfaces/enums';
import { membersAsUsers } from '@/services/project-members.service';
import * as phasesService from '@/services/project-phases.service';
import { useFieldLock } from '@/composables/useFieldLock';

const props = defineProps<{
  form: TaskI;
  errores: TaskErroresFormI;
  projectId: number;
}>();

// When creating a new task (id === 0), there are no field_permissions from the backend.
// The user already passed StoreTaskRequest::authorize() (task.create), so all fields are editable.
const isNewTask = computed(() => !props.form.id || props.form.id === 0);
const fieldPermissions = toRef(() => {
  if (isNewTask.value) {
    return {
      title: true, description: true, status: true, priority: true,
      due_date: true, estimated_hours: true, progress: true,
      phase_id: true, assigned_to: true,
    };
  }
  return (props.form as any).field_permissions ?? {};
});
const fl = useFieldLock(fieldPermissions);

const users = ref<{ id: number; name: string; email: string }[]>([]);
const phases = ref<{ id: number; name: string }[]>([]);

onMounted(async () => {
  const [usersRes, phasesRes] = await Promise.all([
    membersAsUsers(props.projectId),
    phasesService.index(props.projectId),
  ]);
  if (usersRes.status && usersRes.items) {
    users.value = usersRes.items;
  }
  if (phasesRes.status && phasesRes.items) {
    phases.value = phasesRes.items.map(p => ({ id: p.id, name: p.name }));
  }
});

const statuses: { title: string; value: TaskStatus }[] = [
  { title: 'Pendiente', value: 'pending' },
  { title: 'En progreso', value: 'in_progress' },
  { title: 'Revisión', value: 'review' },
  { title: 'Completada', value: 'done' },
  { title: 'Bloqueada', value: 'blocked' },
];

const priorities: { title: string; value: TaskPriority }[] = [
  { title: 'Baja', value: 'low' },
  { title: 'Media', value: 'medium' },
  { title: 'Alta', value: 'high' },
  { title: 'Crítica', value: 'critical' },
];
</script>

<template>
  <VCard class="mb-4">
    <VCardItem>
      <VCardTitle class="text-h6">Datos de la tarea</VCardTitle>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.title" :error-messages="errores.title" name="title" label="Título"
            variant="outlined" density="comfortable" placeholder="Título de la tarea" :disabled="!fl.title.value" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description" name="description"
            label="Descripción" placeholder="Descripción de la tarea" rows="3" variant="outlined" density="comfortable"
            :disabled="!fl.description.value" />
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
          <VTextField v-model="form.due_date" :error-messages="errores.due_date" name="due_date" type="date"
            label="Fecha límite" :disabled="!fl.due_date.value" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.estimated_hours" :error-messages="errores.estimated_hours" name="estimated_hours"
            type="number" label="Horas estimadas" placeholder="0" min="0" step="0.5" variant="outlined"
            density="comfortable" :disabled="!fl.estimated_hours.value" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.progress" :error-messages="errores.progress" name="progress" type="number"
            label="Progreso (%)" placeholder="0" min="0" max="100" :disabled="!fl.progress.value" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.phase_id" :error-messages="errores.phase_id" :items="phases" item-title="name"
            item-value="id" name="phase_id" label="Fase" placeholder="Selecciona una fase" variant="outlined"
            density="comfortable" clearable eager :disabled="!fl.phase_id.value" />
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

        <VCol cols="12" class="d-flex gap-4">
          <VSpacer />
          <VBtn type="submit" color="primary" variant="flat" size="large">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import type { TaskI, TaskErroresFormI } from '@/interfaces/TaskI';
import type { TaskStatus, TaskPriority } from '@/interfaces/enums';
import * as usersService from '@/services/users.service';

defineProps<{
  form: TaskI;
  errores: TaskErroresFormI;
}>();

const users = ref<{ id: number; name: string; email: string }[]>([]);

onMounted(async () => {
  const response = await usersService.all();
  if (response.status && response.items) {
    users.value = response.items;
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
      <h5 class="text-h5 text-wrap">Datos de la tarea</h5>
    </VCardItem>

    <VCardText class="px-8 pb-8">
      <VRow>
        <VCol cols="12">
          <VTextField v-model="form.title" :error-messages="errores.title" name="title" label="Título"
            placeholder="Título de la tarea" />
        </VCol>

        <VCol cols="12">
          <VTextarea v-model="form.description" :error-messages="errores.description" name="description"
            label="Descripción" placeholder="Descripción de la tarea" rows="3" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.status" :error-messages="errores.status" name="status" :items="statuses"
            item-title="title" item-value="value" label="Estado" eager />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.priority" :error-messages="errores.priority" name="priority" :items="priorities"
            item-title="title" item-value="value" label="Prioridad" eager />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.due_date" :error-messages="errores.due_date" name="due_date" type="date"
            label="Fecha límite" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.estimated_hours" :error-messages="errores.estimated_hours" name="estimated_hours"
            type="number" label="Horas estimadas" placeholder="0" />
        </VCol>

        <VCol cols="12" md="4">
          <VTextField v-model="form.progress" :error-messages="errores.progress" name="progress" type="number"
            label="Progreso (%)" placeholder="0" />
        </VCol>

        <VCol cols="12" md="4">
          <VSelect v-model="form.assigned_to" :error-messages="errores.assigned_to" :items="users" item-title="name"
            item-value="id" name="assigned_to" label="Asignado a" placeholder="Selecciona un usuario" clearable eager>
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
          <VBtn type="submit">Guardar</VBtn>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
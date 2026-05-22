<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const flowMap = {
    dashboard: {
        title: 'Dashboard Principal',
        description: 'Vista ejecutiva del sistema.',
        blocks: [
            {
                title: 'Widgets',
                items: [
                    'Total proyectos',
                    'Tickets abiertos',
                    'Tickets criticos',
                    'Usuarios activos',
                    'Actividad reciente',
                    'Proyectos proximos a vencer',
                    'Grafica tickets por estado',
                    'Grafica productividad',
                ],
            },
        ],
    },
    'users-list': {
        title: 'Gestion de Usuarios',
        description: 'Listado de usuarios con control operativo.',
        blocks: [
            { title: 'Funciones', items: ['Buscar', 'Filtrar', 'Paginacion', 'Activar/desactivar', 'Ver detalle'] },
            { title: 'Columnas', items: ['Nombre', 'Email', 'Rol', 'Estado', 'Ultimo acceso'] },
        ],
    },
    'users-create': {
        title: 'Crear Usuario',
        description: 'Alta de un usuario nuevo.',
        blocks: [
            { title: 'Campos', items: ['Nombre', 'Email', 'Password', 'Rol', 'Departamento'] },
        ],
    },
    'users-detail': {
        title: `Detalle Usuario #${route.params.id ?? ''}`,
        description: 'Vista de detalle por usuario.',
        blocks: [
            { title: 'Tabs', items: ['Informacion general', 'Proyectos asignados', 'Tickets asignados', 'Actividad reciente'] },
        ],
    },
    roles: {
        title: 'Roles',
        description: 'Definicion de roles del sistema.',
        blocks: [
            { title: 'Roles ejemplo', items: ['Super Admin', 'Project Manager', 'Developer', 'QA', 'Cliente'] },
        ],
    },
    permissions: {
        title: 'Permisos',
        description: 'Permisos granulares por accion.',
        blocks: [
            { title: 'Permisos', items: ['crear proyectos', 'editar tickets', 'eliminar usuarios', 'aprobar tareas'] },
        ],
    },
    'projects-list': {
        title: 'Gestion de Proyectos',
        description: 'Lista de proyectos con diferentes modos de visualizacion.',
        blocks: [
            { title: 'Vista', items: ['Cards', 'Tabla', 'Kanban opcional'] },
            { title: 'Datos', items: ['Nombre', 'Cliente', 'Estado', 'Progreso', 'Fecha inicio', 'Fecha fin'] },
        ],
    },
    'projects-create': {
        title: 'Crear Proyecto',
        description: 'Registro de proyecto y configuracion inicial.',
        blocks: [
            { title: 'Campos', items: ['Nombre', 'Descripcion', 'Cliente', 'Prioridad', 'Equipo', 'Fechas'] },
        ],
    },
    'projects-detail': {
        title: `Detalle Proyecto #${route.params.id ?? ''}`,
        description: 'Centro de control del proyecto.',
        blocks: [
            { title: 'Tabs', items: ['Resumen', 'Miembros', 'Tareas', 'Tickets', 'Sprints', 'Archivos', 'Actividad', 'Configuracion'] },
        ],
    },
    'projects-members': {
        title: `Miembros del Proyecto #${route.params.id ?? ''}`,
        description: 'Gestion del equipo del proyecto.',
        blocks: [
            { title: 'Funciones', items: ['Agregar usuario', 'Remover usuario', 'Cambiar rol'] },
            { title: 'Roles internos', items: ['Owner', 'Manager', 'Developer', 'QA'] },
        ],
    },
    'tasks-list': {
        title: 'Tareas',
        description: 'Seguimiento de tareas operativas.',
        blocks: [
            { title: 'Filtros', items: ['Estado', 'Prioridad', 'Proyecto', 'Responsable'] },
            { title: 'Vista', items: ['Tabla', 'Kanban', 'Calendario'] },
        ],
    },
    'tasks-create': {
        title: 'Crear Tarea',
        description: 'Formulario de alta de tarea.',
        blocks: [
            { title: 'Campos', items: ['Proyecto', 'Titulo', 'Descripcion', 'Responsable', 'Prioridad', 'Fecha limite'] },
        ],
    },
    'tasks-detail': {
        title: `Detalle Tarea #${route.params.id ?? ''}`,
        description: 'Vista completa de trabajo y seguimiento.',
        blocks: [
            { title: 'Secciones', items: ['Comentarios', 'Checklist', 'Adjuntos', 'Historial', 'Time tracking'] },
        ],
    },
    'tickets-list': {
        title: 'Tickets / Incidencias',
        description: 'Control de incidencias y su flujo de estado.',
        blocks: [
            { title: 'Estados', items: ['Open', 'In Progress', 'Testing', 'Resolved', 'Closed'] },
            { title: 'Prioridades', items: ['Low', 'Medium', 'High', 'Critical'] },
        ],
    },
    'tickets-create': {
        title: 'Crear Ticket',
        description: 'Registro de incidencia.',
        blocks: [
            { title: 'Campos', items: ['Proyecto', 'Tipo', 'Severidad', 'Descripcion', 'Evidencia'] },
        ],
    },
    'tickets-detail': {
        title: `Detalle Ticket #${route.params.id ?? ''}`,
        description: 'Acciones operativas sobre la incidencia.',
        blocks: [
            { title: 'Acciones', items: ['Comentar', 'Cambiar estado', 'Asignar', 'Adjuntar archivos'] },
        ],
    },
    'sprints-list': {
        title: 'Sprints',
        description: 'Seguimiento de sprint y rendimiento.',
        blocks: [
            { title: 'Datos', items: ['Sprint actual', 'Velocity', 'Burndown'] },
        ],
    },
    'sprint-board': {
        title: `Sprint Board #${route.params.id ?? ''}`,
        description: 'Tablero de ejecucion por columnas.',
        blocks: [
            { title: 'Columnas', items: ['Backlog', 'Todo', 'Doing', 'QA', 'Done'] },
        ],
    },
    calendar: {
        title: 'Calendario',
        description: 'Vista de eventos y fechas clave.',
        blocks: [
            { title: 'Eventos', items: ['Entregas', 'Reuniones', 'Deadlines', 'Releases'] },
        ],
    },
    files: {
        title: 'Archivos',
        description: 'Repositorio de documentos del proyecto.',
        blocks: [
            { title: 'Funciones', items: ['Subir', 'Descargar', 'Versionar', 'Compartir'] },
        ],
    },
    reports: {
        title: 'Reportes',
        description: 'Analitica y exportaciones.',
        blocks: [
            { title: 'Reportes', items: ['Productividad', 'Tickets por usuario', 'SLA', 'Tiempo resolucion', 'Avance proyectos'] },
            { title: 'Exportacion', items: ['PDF', 'Excel'] },
        ],
    },
    notifications: {
        title: 'Notificaciones',
        description: 'Centro de eventos del sistema.',
        blocks: [
            { title: 'Eventos', items: ['Ticket asignado', 'Comentario nuevo', 'Proyecto actualizado', 'Deadline proximo'] },
            { title: 'Canales', items: ['In-app', 'Email', 'Push'] },
        ],
    },
    settings: {
        title: 'Configuracion',
        description: 'Ajustes generales del sistema.',
        blocks: [
            { title: 'Opciones', items: ['Preferencias globales', 'Reglas de notificacion', 'Seguridad'] },
        ],
    },
    profile: {
        title: 'Perfil',
        description: 'Configuracion personal de usuario.',
        blocks: [
            { title: 'Preferencias', items: ['Avatar', 'Password', 'Tema oscuro', 'Idioma'] },
        ],
    },
};

const currentFlow = computed(() => {
    const key = route.meta.flowKey;

    if (typeof key === 'string' && flowMap[key]) {
        return flowMap[key];
    }

    return {
        title: 'Modulo',
        description: 'Vista en construccion.',
        blocks: [],
    };
});
</script>

<template>
    <section class="flow-page">
        <header class="flow-page__header">
            <h1>{{ currentFlow.title }}</h1>
            <p>{{ currentFlow.description }}</p>
        </header>

        <div class="flow-grid">
            <article v-for="block in currentFlow.blocks" :key="block.title" class="flow-card">
                <h2>{{ block.title }}</h2>
                <ul>
                    <li v-for="item in block.items" :key="item">{{ item }}</li>
                </ul>
            </article>
        </div>
    </section>
</template>

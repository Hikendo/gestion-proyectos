<script setup>
const userActions = [
    { name: 'users-list', label: 'Listar usuarios' },
    { name: 'users-create', label: 'Crear usuario' },
];
</script>

<template>
    <section class="page-grid">
        <article class="flow-card">
            <h2>Usuarios</h2>
            <div class="feature-nav">
           
            </div>
        </article>

        <RouterView />
    </section>
</template>

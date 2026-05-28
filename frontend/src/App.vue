<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';

const appStore = useAppStore();
const { loader, snackbar } = storeToRefs(appStore);
</script>

<template>
    <VApp>
        <!-- ── Snackbar global ─────────────────────────────────────────── -->
        <VSnackbar v-model="snackbar.show" :color="snackbar.color" location="bottom right" :timeout="3500">
            {{ snackbar.text }}
            <template #actions>
                <VBtn icon="mdi-close" variant="text" @click="snackbar.show = false" />
            </template>
        </VSnackbar>

        <!-- ── Loader global ──────────────────────────────────────────── -->
        <VOverlay :model-value="loader" class="align-center justify-center" persistent>
            <VProgressCircular indeterminate color="primary" size="64" />
        </VOverlay>

        <RouterView />
    </VApp>
</template>

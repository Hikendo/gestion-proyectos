<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import * as reportsService from '../../services/project-reports.service';
import type { ReportPeriod } from '../../services/project-reports.service';

const route = useRoute();
const projectId = computed(() => Number(route.params.projectId));

const period = ref<ReportPeriod>('full');

const periodOptions = [
    { title: 'Reporte completo', value: 'full' },
    { title: 'Último mes', value: 'last_month' },
    { title: 'Último trimestre', value: 'last_quarter' },
];

const loadingExec = ref(false);
const loadingDash = ref(false);
const snackbar = ref(false);
const snackMsg = ref('');
const snackColor = ref<'error' | 'success'>('success');

async function downloadExecutive() {
    loadingExec.value = true;
    try {
        await reportsService.downloadExecutiveReport(projectId.value, period.value);
        showSnack('Reporte ejecutivo descargado correctamente.', 'success');
    } catch (err: unknown) {
        showSnack(errorMsg(err), 'error');
    } finally {
        loadingExec.value = false;
    }
}

async function downloadDashboard() {
    loadingDash.value = true;
    try {
        await reportsService.downloadDashboardReport(projectId.value, period.value);
        showSnack('Dashboard descargado correctamente.', 'success');
    } catch (err: unknown) {
        showSnack(errorMsg(err), 'error');
    } finally {
        loadingDash.value = false;
    }
}

function showSnack(msg: string, color: 'error' | 'success') {
    snackMsg.value = msg;
    snackColor.value = color;
    snackbar.value = true;
}

function errorMsg(err: unknown): string {
    if (err instanceof Error) return err.message;
    return 'Ocurrió un error al descargar el reporte.';
}
</script>

<template>
    <v-container class="pa-4" fluid>
        <v-row>
            <v-col cols="12">
                <h2 class="text-h6 font-weight-bold mb-1">Reportes del Proyecto</h2>
                <p class="text-body-2 text-medium-emphasis mb-4">
                    Genera y descarga reportes del proyecto en distintos formatos.
                </p>
            </v-col>
        </v-row>

        <!-- Period selector -->
        <v-row>
            <v-col cols="12" sm="5" md="4">
                <v-select v-model="period" :items="periodOptions" item-title="title" item-value="value"
                    label="Período del reporte" variant="outlined" density="comfortable" hide-details />
            </v-col>
        </v-row>

        <!-- Report cards -->
        <v-row class="mt-4">
            <!-- Executive DOCX -->
            <v-col cols="12" sm="6" md="5">
                <v-card variant="outlined" rounded="lg">
                    <v-card-item>
                        <template #prepend>
                            <v-icon color="primary" size="36" class="mr-2">mdi-file-word-outline</v-icon>
                        </template>
                        <v-card-title>Reporte Ejecutivo</v-card-title>
                        <v-card-subtitle>Formato DOCX — Microsoft Word</v-card-subtitle>
                    </v-card-item>

                    <v-card-text class="text-body-2 text-medium-emphasis">
                        Documento Word con resumen ejecutivo, KPIs, análisis del equipo, hitos,
                        riesgos, bloqueadores, tickets y recomendaciones.
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <v-btn color="primary" variant="flat" prepend-icon="mdi-download" :loading="loadingExec"
                            :disabled="loadingDash" @click="downloadExecutive">
                            Descargar Reporte Ejecutivo
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>

            <!-- Dashboard XLSX -->
            <v-col cols="12" sm="6" md="5">
                <v-card variant="outlined" rounded="lg">
                    <v-card-item>
                        <template #prepend>
                            <v-icon color="success" size="36" class="mr-2">mdi-microsoft-excel</v-icon>
                        </template>
                        <v-card-title>Dashboard Analítico</v-card-title>
                        <v-card-subtitle>Formato XLSX — Microsoft Excel</v-card-subtitle>
                    </v-card-item>

                    <v-card-text class="text-body-2 text-medium-emphasis">
                        Libro de Excel con hojas de Dashboard, Resumen, Tareas, Tickets, Bloqueadores,
                        Equipo, Métricas y Diagrama de Gantt con gráficos.
                    </v-card-text>

                    <v-card-actions class="pa-4 pt-0">
                        <v-btn color="success" variant="flat" prepend-icon="mdi-download" :loading="loadingDash"
                            :disabled="loadingExec" @click="downloadDashboard">
                            Descargar Dashboard
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>

        <!-- Info alert -->
        <v-row class="mt-2">
            <v-col cols="12" md="10">
                <v-alert type="info" variant="tonal" density="compact" class="text-body-2">
                    Los reportes se generan en tiempo real con los datos actuales del proyecto.
                    Dependiendo del volumen de información, la descarga puede tardar unos segundos.
                </v-alert>
            </v-col>
        </v-row>

        <!-- Snackbar feedback -->
        <v-snackbar v-model="snackbar" :color="snackColor" timeout="4000" location="bottom end">
            {{ snackMsg }}
            <template #actions>
                <v-btn variant="text" @click="snackbar = false">Cerrar</v-btn>
            </template>
        </v-snackbar>
    </v-container>
</template>

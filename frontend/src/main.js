import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createVuetify } from 'vuetify';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import App from './App.vue';
import { router } from './router';
import './style.css';
import { themes, DEFAULT_THEME_KEY, STORAGE_KEY } from './constants/themes';

// Build Vuetify themes map from the catalog
const vuetifyThemes = Object.fromEntries(
    Object.entries(themes).map(([key, t]) => [key, t.vuetify]),
);

// Read persisted theme before Vue mounts so the first render is correct
const storedTheme = localStorage.getItem(STORAGE_KEY) ?? DEFAULT_THEME_KEY;

const vuetify = createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: { mdi },
    },
    theme: {
        defaultTheme: storedTheme,
        themes: vuetifyThemes,
    },
});

const pinia = createPinia();

createApp(App).use(router).use(pinia).use(vuetify).mount('#app');

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useTheme } from 'vuetify';
import { themes, themeList, STORAGE_KEY, DEFAULT_THEME_KEY } from '@/constants/themes';
import type { ThemeMeta } from '@/constants/themes';

// ── Apply CSS variables to :root ─────────────────────────────────────────────
function applyCssVars(key: string): void {
  const theme = themes[key];
  if (!theme) return;
  const root = document.documentElement;
  for (const [varName, value] of Object.entries(theme.cssVars)) {
    root.style.setProperty(varName, value);
  }
  // Sync color-scheme so native browser elements (scrollbars, inputs) respect dark/light
  root.style.setProperty('color-scheme', theme.meta.dark ? 'dark' : 'light');
}

// ── Store ─────────────────────────────────────────────────────────────────────
export const useThemeStore = defineStore('theme', () => {
  const vuetifyTheme = useTheme();

  const currentKey = ref<string>(
    localStorage.getItem(STORAGE_KEY) ?? DEFAULT_THEME_KEY,
  );

  const currentMeta = computed<ThemeMeta>(
    () => themes[currentKey.value]?.meta ?? themes[DEFAULT_THEME_KEY].meta,
  );

  const isDark = computed(() => currentMeta.value.dark);

  function setTheme(key: string): void {
    if (!themes[key]) return;
    currentKey.value = key;
    vuetifyTheme.global.name.value = key;
    applyCssVars(key);
    localStorage.setItem(STORAGE_KEY, key);
  }

  /** Called once on app mount to sync CSS vars with the stored theme. */
  function init(): void {
    setTheme(currentKey.value);
  }

  return {
    currentKey,
    currentMeta,
    isDark,
    themeList,
    setTheme,
    init,
  };
});

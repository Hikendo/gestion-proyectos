import type { ThemeDefinition } from 'vuetify';

// ── Types ────────────────────────────────────────────────────────────────────

export interface ThemeMeta {
  key: string;
  label: string;
  description: string;
  icon: string;
  dark: boolean;
  preview: string;
}

export interface AppThemeDefinition {
  meta: ThemeMeta;
  vuetify: ThemeDefinition;
  cssVars: Record<string, string>;
}

// ── localStorage key & default ───────────────────────────────────────────────

export const STORAGE_KEY = 'app-theme';
export const DEFAULT_THEME_KEY = 'corporate';

// ── Shared semantic defaults ─────────────────────────────────────────────────

const DEF = {
  success: '#16A34A',
  warning: '#D97706',
  error: '#DC2626',
  info: '#2563EB',
};

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Retorna color de texto accesible basado en luminancia.
 * Evita combinaciones que cansen la vista.
 */
function getContrastColor(hex: string): '#FFFFFF' | '#111111' {
  const color = hex.replace('#', '');

  const r = parseInt(color.substring(0, 2), 16);
  const g = parseInt(color.substring(2, 4), 16);
  const b = parseInt(color.substring(4, 6), 16);

  const luminance =
    (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  return luminance > 0.6
    ? '#111111'
    : '#FFFFFF';
}

function createColors(colors: {
  primary: string;
  secondary: string;
  accent: string;
  background: string;
  surface: string;
  success?: string;
  warning?: string;
  error?: string;
  info?: string;
}) {
  const success = colors.success ?? DEF.success;
  const warning = colors.warning ?? DEF.warning;
  const error = colors.error ?? DEF.error;
  const info = colors.info ?? DEF.info;

  return {
    primary: colors.primary,
    'on-primary': getContrastColor(colors.primary),

    secondary: colors.secondary,
    'on-secondary': getContrastColor(colors.secondary),

    accent: colors.accent,
    'on-accent': getContrastColor(colors.accent),

    background: colors.background,
    'on-background': getContrastColor(colors.background),

    surface: colors.surface,
    'on-surface': getContrastColor(colors.surface),

    success,
    'on-success': getContrastColor(success),

    warning,
    'on-warning': getContrastColor(warning),

    error,
    'on-error': getContrastColor(error),

    info,
    'on-info': getContrastColor(info),
  };
}

// ── Theme catalog ────────────────────────────────────────────────────────────

export const themes: Record<string, AppThemeDefinition> = {

  // ── 1. Minimalista ───────────────────────────────────────────────────────

  minimal: {
    meta: {
      key: 'minimal',
      label: 'Minimalista',
      description: 'Blancos, negros y grises. Elegante y limpio.',
      icon: 'mdi-circle-half-full',
      dark: false,
      preview: '#111111',
    },

    vuetify: {
      dark: false,

      colors: createColors({
        primary: '#111111',
        secondary: '#404040',
        accent: '#525252',

        background: '#FFFFFF',
        surface: '#F5F5F5',

        info: '#525252',
      }),
    },

    cssVars: {
      '--app-bg': '#FFFFFF',
      '--app-surface': '#F5F5F5',

      '--app-primary': '#111111',
      '--app-secondary': '#404040',
      '--app-accent': '#525252',

      '--app-text': '#111111',
      '--app-muted': '#737373',
      '--app-border': '#E5E5E5',

      '--app-success': DEF.success,
      '--app-warning': DEF.warning,
      '--app-danger': DEF.error,
    },
  },

  // ── 2. Corporativo ───────────────────────────────────────────────────────

  corporate: {
    meta: {
      key: 'corporate',
      label: 'Corporativo',
      description: 'Azules y grises para finanzas y tecnología.',
      icon: 'mdi-briefcase-outline',
      dark: false,
      preview: '#1D4ED8',
    },

    vuetify: {
      dark: false,

      colors: createColors({
        primary: '#1D4ED8',
        secondary: '#2563EB',
        accent: '#60A5FA',

        background: '#F8FAFC',
        surface: '#FFFFFF',

        info: '#3B82F6',
      }),
    },

    cssVars: {
      '--app-bg': '#F8FAFC',
      '--app-surface': '#FFFFFF',

      '--app-primary': '#1D4ED8',
      '--app-secondary': '#2563EB',
      '--app-accent': '#60A5FA',

      '--app-text': '#0F172A',
      '--app-muted': '#64748B',
      '--app-border': '#CBD5E1',

      '--app-success': DEF.success,
      '--app-warning': DEF.warning,
      '--app-danger': DEF.error,
    },
  },

  // ── 3. Startup ───────────────────────────────────────────────────────────

  startup: {
    meta: {
      key: 'startup',
      label: 'Startup',
      description: 'Moderno y enérgico con colores equilibrados.',
      icon: 'mdi-rocket-launch-outline',
      dark: false,
      preview: '#2563EB',
    },

    vuetify: {
      dark: false,

      colors: createColors({
        primary: '#2563EB',

        // Menos saturado para reducir fatiga visual
        secondary: '#D97706',

        accent: '#059669',

        background: '#FFFFFF',
        surface: '#F3F4F6',

        success: '#059669',
        warning: '#D97706',
        info: '#2563EB',
      }),
    },

    cssVars: {
      '--app-bg': '#FFFFFF',
      '--app-surface': '#F3F4F6',

      '--app-primary': '#2563EB',
      '--app-secondary': '#D97706',
      '--app-accent': '#059669',

      '--app-text': '#111827',
      '--app-muted': '#6B7280',
      '--app-border': '#D1D5DB',

      '--app-success': '#059669',
      '--app-warning': '#D97706',
      '--app-danger': DEF.error,
    },
  },

  // ── 4. Naturaleza ────────────────────────────────────────────────────────

  nature: {
    meta: {
      key: 'nature',
      label: 'Naturaleza',
      description: 'Verdes orgánicos suaves y cómodos.',
      icon: 'mdi-leaf',
      dark: false,
      preview: '#2F855A',
    },

    vuetify: {
      dark: false,

      colors: createColors({
        primary: '#2F855A',

        // Mejor contraste
        secondary: '#38A169',

        accent: '#B7791F',

        background: '#F7FAF7',
        surface: '#FFFFFF',

        success: '#2F855A',
        warning: '#B7791F',
        info: '#38A169',
      }),
    },

    cssVars: {
      '--app-bg': '#F7FAF7',
      '--app-surface': '#FFFFFF',

      '--app-primary': '#2F855A',
      '--app-secondary': '#38A169',
      '--app-accent': '#B7791F',

      '--app-text': '#1A202C',
      '--app-muted': '#718096',
      '--app-border': '#C6F6D5',

      '--app-success': '#2F855A',
      '--app-warning': '#B7791F',
      '--app-danger': DEF.error,
    },
  },

  // ── 5. Tecnología / Futurista Oscuro ────────────────────────────────────

  futuristicDark: {
    meta: {
      key: 'futuristicDark',
      label: 'Tecnología',
      description: 'Oscuro moderno con menor fatiga visual.',
      icon: 'mdi-cpu-64-bit',
      dark: true,
      preview: '#7C3AED',
    },

    vuetify: {
      dark: true,

      colors: createColors({
        primary: '#7C3AED',

        // Cian menos agresivo
        secondary: '#0891B2',

        accent: '#0EA5E9',

        background: '#0F172A',
        surface: '#111827',

        success: '#10B981',
        warning: '#F59E0B',
        error: '#EF4444',
        info: '#0EA5E9',
      }),
    },

    cssVars: {
      '--app-bg': '#0F172A',
      '--app-surface': '#111827',

      '--app-primary': '#7C3AED',
      '--app-secondary': '#0891B2',
      '--app-accent': '#0EA5E9',

      '--app-text': '#F9FAFB',
      '--app-muted': '#94A3B8',
      '--app-border': '#1F2937',

      '--app-success': '#10B981',
      '--app-warning': '#F59E0B',
      '--app-danger': '#EF4444',
    },
  },
};

// ── Theme list ───────────────────────────────────────────────────────────────

export const themeList: ThemeMeta[] =
  Object.values(themes).map((t) => t.meta);

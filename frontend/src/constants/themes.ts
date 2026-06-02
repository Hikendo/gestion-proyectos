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

export interface ThemeColors {
  primary: string;
  secondary: string;
  accent: string;

  background: string;
  surface: string;

  text: string;
  textMuted: string;

  border: string;

  success: string;
  warning: string;
  danger: string;

  onPrimary: string;
  onSecondary: string;
  onAccent: string;

  onSuccess: string;
  onWarning: string;
  onDanger: string;
}

export interface AppThemeDefinition {
  meta: ThemeMeta;
  colors: ThemeColors;
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
  danger: '#DC2626',
};

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Returns WCAG-compliant text color (white or near-black) for a given
 * background color, so on-* tokens are always readable.
 */
function getContrastColor(hex: string): '#FFFFFF' | '#111111' {
  const color = hex.replace('#', '');

  const r = parseInt(color.substring(0, 2), 16);
  const g = parseInt(color.substring(2, 4), 16);
  const b = parseInt(color.substring(4, 6), 16);

  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  return luminance > 0.6 ? '#111111' : '#FFFFFF';
}

// Partial input — on* colors are auto-calculated when omitted.
type RawColors = Omit<ThemeColors, 'onPrimary' | 'onSecondary' | 'onAccent' | 'onSuccess' | 'onWarning' | 'onDanger'>
  & Partial<Pick<ThemeColors, 'onPrimary' | 'onSecondary' | 'onAccent' | 'onSuccess' | 'onWarning' | 'onDanger'>>;

function buildTheme(meta: ThemeMeta, raw: RawColors): AppThemeDefinition {
  const colors: ThemeColors = {
    ...raw,
    onPrimary:   raw.onPrimary   ?? getContrastColor(raw.primary),
    onSecondary: raw.onSecondary ?? getContrastColor(raw.secondary),
    onAccent:    raw.onAccent    ?? getContrastColor(raw.accent),
    onSuccess:   raw.onSuccess   ?? getContrastColor(raw.success),
    onWarning:   raw.onWarning   ?? getContrastColor(raw.warning),
    onDanger:    raw.onDanger    ?? getContrastColor(raw.danger),
  };

  return {
    meta,
    colors,

    vuetify: {
      dark: meta.dark,
      colors: {
        primary:          colors.primary,
        'on-primary':     colors.onPrimary,

        secondary:        colors.secondary,
        'on-secondary':   colors.onSecondary,

        accent:           colors.accent,
        'on-accent':      colors.onAccent,

        background:       colors.background,
        'on-background':  colors.text,

        surface:          colors.surface,
        'on-surface':     colors.text,

        success:          colors.success,
        'on-success':     colors.onSuccess,

        warning:          colors.warning,
        'on-warning':     colors.onWarning,

        error:            colors.danger,
        'on-error':       colors.onDanger,

        // Map info to primary so info-coloured Vuetify components use
        // the theme's brand color with correct contrast automatically.
        info:             colors.primary,
        'on-info':        colors.onPrimary,
      },
    },

    cssVars: {
      '--app-bg':      colors.background,
      '--app-surface': colors.surface,

      '--app-primary':   colors.primary,
      '--app-secondary': colors.secondary,
      '--app-accent':    colors.accent,

      '--app-text':  colors.text,
      '--app-muted': colors.textMuted,
      '--app-border': colors.border,

      '--app-success': colors.success,
      '--app-warning': colors.warning,
      '--app-danger':  colors.danger,

      // on-* tokens: text/icon color for use ON a colored background.
      // Never substitute these with --app-text.
      '--app-on-primary':   colors.onPrimary,
      '--app-on-secondary': colors.onSecondary,
      '--app-on-accent':    colors.onAccent,
      '--app-on-success':   colors.onSuccess,
      '--app-on-warning':   colors.onWarning,
      '--app-on-danger':    colors.onDanger,
    },
  };
}

// ── Theme catalog ────────────────────────────────────────────────────────────

export const themes: Record<string, AppThemeDefinition> = {

  // ── 1. Minimalista ───────────────────────────────────────────────────────

  minimal: buildTheme(
    {
      key: 'minimal',
      label: 'Minimalista',
      description: 'Blancos, negros y grises. Elegante y limpio.',
      icon: 'mdi-circle-half-full',
      dark: false,
      preview: '#111111',
    },
    {
      primary:   '#111111',
      secondary: '#404040',
      accent:    '#525252',

      background: '#FFFFFF',
      surface:    '#F5F5F5',

      text:      '#111111',
      textMuted: '#737373',
      border:    '#E5E5E5',

      success: DEF.success,
      warning: DEF.warning,
      danger:  DEF.danger,
    },
  ),

  // ── 2. Corporativo ───────────────────────────────────────────────────────

  corporate: buildTheme(
    {
      key: 'corporate',
      label: 'Corporativo',
      description: 'Azules y grises para finanzas y tecnología.',
      icon: 'mdi-briefcase-outline',
      dark: false,
      preview: '#1D4ED8',
    },
    {
      primary:   '#1D4ED8',
      secondary: '#2563EB',
      accent:    '#60A5FA',

      background: '#F8FAFC',
      surface:    '#FFFFFF',

      text:      '#0F172A',
      textMuted: '#64748B',
      border:    '#CBD5E1',

      success: DEF.success,
      warning: DEF.warning,
      danger:  DEF.danger,
    },
  ),

  // ── 3. Startup ───────────────────────────────────────────────────────────

  startup: buildTheme(
    {
      key: 'startup',
      label: 'Startup',
      description: 'Moderno y enérgico con colores equilibrados.',
      icon: 'mdi-rocket-launch-outline',
      dark: false,
      preview: '#2563EB',
    },
    {
      primary:   '#2563EB',
      secondary: '#D97706',
      accent:    '#059669',

      background: '#FFFFFF',
      surface:    '#F3F4F6',

      text:      '#111827',
      textMuted: '#6B7280',
      border:    '#D1D5DB',

      success: '#059669',
      warning: '#D97706',
      danger:  DEF.danger,
    },
  ),

  // ── 4. Naturaleza ────────────────────────────────────────────────────────

  nature: buildTheme(
    {
      key: 'nature',
      label: 'Naturaleza',
      description: 'Verdes orgánicos suaves y cómodos.',
      icon: 'mdi-leaf',
      dark: false,
      preview: '#2F855A',
    },
    {
      primary:   '#2F855A',
      secondary: '#38A169',
      accent:    '#B7791F',

      background: '#F7FAF7',
      surface:    '#FFFFFF',

      text:      '#1A202C',
      textMuted: '#718096',
      border:    '#C6F6D5',

      success: '#2F855A',
      warning: '#B7791F',
      danger:  DEF.danger,
    },
  ),

  // ── 5. Tecnología / Futurista Oscuro ────────────────────────────────────

  futuristicDark: buildTheme(
    {
      key: 'futuristicDark',
      label: 'Tecnología',
      description: 'Oscuro moderno con menor fatiga visual.',
      icon: 'mdi-cpu-64-bit',
      dark: true,
      preview: '#7C3AED',
    },
    {
      primary:   '#7C3AED',
      secondary: '#0891B2',
      accent:    '#0EA5E9',

      background: '#0F172A',
      surface:    '#111827',

      text:      '#F9FAFB',
      textMuted: '#94A3B8',
      border:    '#1F2937',

      success: '#10B981',
      warning: '#F59E0B',
      danger:  '#EF4444',
    },
  ),
};

// ── Theme list ───────────────────────────────────────────────────────────────

export const themeList: ThemeMeta[] =
  Object.values(themes).map((t) => t.meta);

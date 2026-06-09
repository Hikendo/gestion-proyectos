import { config } from '@vue/test-utils';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { vi } from 'vitest';

const vuetify = createVuetify({ components, directives });

config.global.plugins = [vuetify];

// Mock Firebase Messaging — requires browser APIs not available in Node.js/ happy-dom
vi.mock('@/services/firebase', () => ({
  messaging: {},
  requestNotificationPermission: vi.fn(() => Promise.resolve('unsupported' as const)),
  listenForegroundNotifications: vi.fn(),
}));

// Mock WindowEvent for firebase messaging (uses window.addEventListener at module level)
if (!globalThis.window) {
  (globalThis as any).window = globalThis;
}
if (!(globalThis.window as any).addEventListener) {
  (globalThis.window as any).addEventListener = vi.fn();
}
if (!(globalThis.window as any).removeEventListener) {
  (globalThis.window as any).removeEventListener = vi.fn();
}
if (!(globalThis.window as any).dispatchEvent) {
  (globalThis.window as any).dispatchEvent = vi.fn();
}

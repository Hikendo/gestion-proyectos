import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { usePermissionStore } from '@/store/usePermissionStore';

describe('usePermissionStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('starts with empty permissions and loaded=false', () => {
    const store = usePermissionStore();
    expect(store.permissions).toEqual([]);
    expect(store.loaded).toBe(false);
  });

  it('setPermissions loads permissions and marks loaded=true', () => {
    const store = usePermissionStore();
    store.setPermissions(['task.view', 'task.create']);
    expect(store.permissions).toEqual(['task.view', 'task.create']);
    expect(store.loaded).toBe(true);
  });

  it('hasPermission returns true for existing permission', () => {
    const store = usePermissionStore();
    store.setPermissions(['project.view', 'task.edit-own']);
    expect(store.hasPermission('project.view')).toBe(true);
  });

  it('hasPermission returns false for missing permission', () => {
    const store = usePermissionStore();
    store.setPermissions(['project.view']);
    expect(store.hasPermission('project.delete')).toBe(false);
  });

  it('clearPermissions resets state', () => {
    const store = usePermissionStore();
    store.setPermissions(['project.create']);
    store.clearPermissions();
    expect(store.permissions).toEqual([]);
    expect(store.loaded).toBe(false);
  });

  it('refreshPermissions stays empty on network error (no backend)', async () => {
    const store = usePermissionStore();
    // No backend available in test; should catch error and keep defaults
    await store.refreshPermissions();
    // Permissions remain empty but loaded stays false (error path)
    expect(store.permissions).toEqual([]);
    expect(store.loaded).toBe(false);
  });
});
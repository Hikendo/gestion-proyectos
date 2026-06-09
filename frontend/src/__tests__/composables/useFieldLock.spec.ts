import { describe, it, expect } from 'vitest';
import { ref } from 'vue';
import { useFieldLock, useField } from '@/composables/useFieldLock';

describe('useFieldLock', () => {
  it('returns computed booleans for each field in permissions', () => {
    const permissions = ref({ title: true, description: false, status: true });
    const locks = useFieldLock(permissions);

    expect(locks.title.value).toBe(true);
    expect(locks.description.value).toBe(false);
    expect(locks.status.value).toBe(true);
  });

  it('returns false for fields not in the permissions object', () => {
    const permissions = ref({ title: true });
    const locks = useFieldLock(permissions);

    expect(locks.unknownField.value).toBe(false);
  });

  it('reacts to changes in field_permissions', () => {
    const permissions = ref({ title: true });
    const locks = useFieldLock(permissions);

    expect(locks.title.value).toBe(true);

    permissions.value = { title: false };
    expect(locks.title.value).toBe(false);
  });

  it('handles null/undefined permissions gracefully', () => {
    const permissions = ref(null as any);
    const locks = useFieldLock(permissions);

    expect(locks.anyField.value).toBe(false);
  });

  it('useField returns boolean computed for a specific field', () => {
    const permissions = ref({ title: true, description: false });
    const canEditTitle = useField(permissions, 'title');
    const canEditDesc = useField(permissions, 'description');

    expect(canEditTitle.value).toBe(true);
    expect(canEditDesc.value).toBe(false);

    permissions.value = { title: false, description: true };
    expect(canEditTitle.value).toBe(false);
    expect(canEditDesc.value).toBe(true);
  });
});
import { computed, type Ref, type ComputedRef } from 'vue';

/**
 * Reactive field-locking composable.
 *
 * Replaces the old `v-can-action` directive.
 * Usage:
 *   const { canEditTitle } = useFieldLock(fieldPermissions);
 *   <VTextField :disabled="!canEditTitle" ... />
 *
 * @param fieldPermissions - A Ref or ComputedRef of `{ [field: string]: boolean }`
 *                           returned by the backend for a given resource.
 */
export function useFieldLock(
  fieldPermissions: Ref<Record<string, boolean>> | ComputedRef<Record<string, boolean>>
): Record<string, ComputedRef<boolean>> {
  const locks: Record<string, ComputedRef<boolean>> = {};

  return new Proxy(locks, {
    get(_target, prop: string) {
      // Create computed on first access and cache it
      if (!locks[prop]) {
        locks[prop] = computed(() => {
          const fp = fieldPermissions.value;
          if (!fp || typeof fp !== 'object') return false;
          return fp[prop] === true;
        });
      }
      return locks[prop];
    },
  }) as Record<string, ComputedRef<boolean>>;
}

/**
 * Convenience: returns a boolean computed for a specific field.
 *
 * Usage:
 *   const canEditTitle = useField(fieldPermissions, 'title');
 *   <VTextField :disabled="!canEditTitle" ... />
 */
export function useField(
  fieldPermissions: Ref<Record<string, boolean>> | ComputedRef<Record<string, boolean>>,
  fieldName: string,
): ComputedRef<boolean> {
  return computed(() => {
    const fp = fieldPermissions.value;
    if (!fp || typeof fp !== 'object') return false;
    return fp[fieldName] === true;
  });
}
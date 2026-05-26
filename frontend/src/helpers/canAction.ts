import { getAuthToken } from '@/services/http';

/**
 * Verifica si el usuario autenticado tiene permiso para ejecutar una acción.
 * La lógica de permisos se implementará según la configuración de roles/permisos del backend.
 * Por ahora retorna true para todas las acciones (sin restricciones).
 */
export function canAction(_action: string): boolean {
    // Verificar que hay sesión activa
    if (!getAuthToken()) return false;

    // TODO: implementar lógica real de permisos según roles del usuario
    return true;
}


/**
 * Formatea una fecha en formato string a una representación legible (Ej: "28 de mayo de 2026")
 */
export const formatDate = (date: string): string => {
    if (!date) return '';

    const options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };

    return new Date(date).toLocaleDateString(undefined, options);
};

/**
 * Convierte una fecha ISO 8601 (con o sin T/Z) al formato YYYY-MM-DD requerido
 * por los inputs HTML type="date".
 *
 * Ejemplo: "2026-06-15T00:00:00.000000Z" → "2026-06-15"
 */
export const toDateInput = (date?: string | null): string => {
    if (!date) return '';

    // Si ya está en formato YYYY-MM-DD, devolver tal cual
    if (/^\d{4}-\d{2}-\d{2}$/.test(date)) return date;

    try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');

        return `${yyyy}-${mm}-${dd}`;
    } catch {
        return '';
    }
};

// src/utils/util.ts

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

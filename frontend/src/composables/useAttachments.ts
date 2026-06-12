import { ref } from 'vue';
import { apiWithToken } from '@/services/http';
import type { AttachmentI } from '@/interfaces/AttachmentI';

const BASE = '/attachments';

export function useAttachments() {
  const uploading = ref(false);
  const error = ref<string | null>(null);

  function clearError() {
    error.value = null;
  }

  /**
   * Descarga un archivo adjunto usando su UUID.
   *
   * Usa apiWithToken (axios) para que el interceptor de peticiones
   * inyecte el header Authorization: Bearer automáticamente.
   * Esto es necesario porque la ruta /attachments/download/{uuid}
   * está protegida con el middleware auth:sanctum.
   */
  async function download(attachment: AttachmentI): Promise<void> {
    try {
      // download_url es absoluto (http://nginx/api/v1/attachments/...)
      // Extraemos solo el path y removemos /api/v1 para que axios
      // use su baseURL configurado (/api/v1).
      const absoluteUrl = attachment.download_url;
      let path = absoluteUrl;
      try {
        path = new URL(absoluteUrl).pathname;
      } catch { /* si no es parseable, lo usamos tal cual */ }
      const relativePath = path.replace(/^\/api\/v1/, '') || path;
      const response = await apiWithToken.get(relativePath, { responseType: 'blob' });

      // Extraer el nombre de archivo del Content-Disposition o usar el original
      const disposition = (response.headers['content-disposition'] ?? '') as string;
      const match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
      const filename = match
        ? match[1].replace(/['"]/g, '')
        : (attachment.original_name || 'archivo');

      // Crear blob URL y disparar descarga
      const blob = new Blob([response.data]);
      const blobUrl = window.URL.createObjectURL(blob);

      const link = document.createElement('a');
      link.href = blobUrl;
      link.download = decodeURIComponent(filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      // Liberar memoria del blob URL después de un momento
      setTimeout(() => window.URL.revokeObjectURL(blobUrl), 1000);
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Error al descargar el archivo.';
      error.value = message;
    }
  }

  /**
   * Obtiene el tipo de icono según el mime_type del archivo.
   */
  function getFileIcon(mimeType: string | null): string {
    if (!mimeType) return 'ri-file-text-line';

    if (mimeType.includes('pdf')) return 'ri-file-pdf-line';
    if (mimeType.includes('image')) return 'ri-image-line';
    if (mimeType.includes('zip') || mimeType.includes('rar')) return 'ri-folder-line-zip-outline';
    // Excel/PowerPoint deben evaluarse antes que Word porque los mimes Office Open XML
    // contienen "document" en "officedocument" y causarían falsos positivos con Word
    if (mimeType.includes('excel') || mimeType.includes('sheet')) return 'ri-file-excel-line';
    if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'ri-file-ppt-line';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'ri-file-word-line';

    return 'ri-file-text-line';
  }

  /**
   * Formatea el tamaño del archivo en formato legible (KB, MB).
   */
  function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  }

  /**
   * Sube múltiples archivos a un recurso padre (Project, Task, Ticket, Blocker).
   * @param parentType - 'projects' | 'tasks' | 'tickets' | 'blockers'
   * @param parentId   - ID del recurso padre
   * @param files      - Archivos a subir
   */
  async function upload(parentType: string, parentId: number, files: File[]): Promise<AttachmentI[]> {
    clearError();
    uploading.value = true;
    try {
      const fd = new FormData();
      files.forEach((file) => fd.append('attachments[]', file));

      const response = await apiWithToken.post<{ data: AttachmentI[] }>(
        `/${parentType}/${parentId}/attachments`,
        fd,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      return response.data.data;
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Error al subir archivos.';
      error.value = message;
      throw e;
    } finally {
      uploading.value = false;
    }
  }

  /**
   * Elimina un adjunto por UUID.
   */
  async function remove(attachment: AttachmentI): Promise<boolean> {
    clearError();
    try {
      await apiWithToken.delete(`${BASE}/${attachment.uuid}`);
      return true;
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Error al eliminar el adjunto.';
      error.value = message;
      return false;
    }
  }

  /**
   * Reemplaza el archivo físico de un adjunto manteniendo el mismo UUID.
   */
  async function replace(attachment: AttachmentI, file: File): Promise<AttachmentI | null> {
    clearError();
    uploading.value = true;
    try {
      const fd = new FormData();
      fd.append('file', file);

      const response = await apiWithToken.post<{ data: AttachmentI }>(
        `${BASE}/${attachment.uuid}/replace`,
        fd,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      return response.data.data;
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Error al reemplazar el archivo.';
      error.value = message;
      return null;
    } finally {
      uploading.value = false;
    }
  }

  return {
    uploading,
    error,
    clearError,
    download,
    getFileIcon,
    formatSize,
    upload,
    remove,
    replace,
  };
}

/**
 * Construye un FormData a partir de un objeto plano y una lista de archivos.
 */
export function buildFormData(payload: Record<string, unknown>, files: File[]): FormData {
  const fd = new FormData();

  Object.entries(payload).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      fd.append(key, String(value));
    }
  });

  files.forEach((file) => {
    fd.append('attachments[]', file);
  });

  return fd;
}
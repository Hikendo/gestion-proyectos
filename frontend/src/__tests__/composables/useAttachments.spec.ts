import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAttachments, buildFormData } from '@/composables/useAttachments';
import { apiWithToken } from '@/services/http';
import type { AttachmentI } from '@/interfaces/AttachmentI';

function makeAttachment(overrides: Partial<AttachmentI> = {}): AttachmentI {
  return {
    id: 1,
    uuid: 'abc-123',
    original_name: 'document.pdf',
    disk_path: '/projects/uuid-1/abc-123.pdf',
    mime_type: 'application/pdf',
    size: 102400,
    download_url: 'https://example.com/api/v1/attachments/download/abc-123',
    ...overrides,
  };
}

describe('useAttachments', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('getFileIcon', () => {
    const { getFileIcon } = useAttachments();

    it('devuelve icono PDF para mime application/pdf', () => {
      expect(getFileIcon('application/pdf')).toBe('ri-file-pdf-line');
    });

    it('devuelve icono imagen para mime image/png', () => {
      expect(getFileIcon('image/png')).toBe('ri-image-line');
    });

    it('devuelve icono imagen para mime image/jpeg', () => {
      expect(getFileIcon('image/jpeg')).toBe('ri-image-line');
    });

    it('devuelve icono ZIP para mime application/zip', () => {
      expect(getFileIcon('application/zip')).toBe('ri-folder-line-zip-outline');
    });

    it('devuelve icono ZIP para mime application/x-rar-compressed', () => {
      expect(getFileIcon('application/x-rar-compressed')).toBe('ri-folder-line-zip-outline');
    });

    it('devuelve icono Word para mime con word', () => {
      expect(getFileIcon('application/msword')).toBe('ri-file-word-line');
      expect(getFileIcon('application/vnd.openxmlformats-officedocument.wordprocessingml.document')).toBe('ri-file-word-line');
    });

    it('devuelve icono Excel para mime con excel o sheet', () => {
      expect(getFileIcon('application/vnd.ms-excel')).toBe('ri-file-excel-line');
      expect(getFileIcon('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')).toBe('ri-file-excel-line');
    });

    it('devuelve icono PowerPoint para mime con powerpoint o presentation', () => {
      expect(getFileIcon('application/vnd.ms-powerpoint')).toBe('ri-file-ppt-line');
      expect(getFileIcon('application/vnd.openxmlformats-officedocument.presentationml.presentation')).toBe('ri-file-ppt-line');
    });

    it('devuelve icono genérico para mime null', () => {
      expect(getFileIcon(null)).toBe('ri-file-text-line');
    });

    it('devuelve icono genérico para mime desconocido', () => {
      expect(getFileIcon('text/plain')).toBe('ri-file-text-line');
    });
  });

  describe('formatSize', () => {
    const { formatSize } = useAttachments();

    it('formatea bytes', () => {
      expect(formatSize(0)).toBe('0 B');
      expect(formatSize(500)).toBe('500 B');
      expect(formatSize(1023)).toBe('1023 B');
    });

    it('formatea kilobytes', () => {
      expect(formatSize(1024)).toBe('1.0 KB');
      expect(formatSize(1536)).toBe('1.5 KB');
      expect(formatSize(102400)).toBe('100.0 KB');
    });

    it('formatea megabytes', () => {
      expect(formatSize(1048576)).toBe('1.0 MB');
      expect(formatSize(5242880)).toBe('5.0 MB');
      expect(formatSize(15728640)).toBe('15.0 MB');
    });
  });

  describe('upload', () => {
    it('sube archivos y devuelve AttachmentI[]', async () => {
      const attachments = [makeAttachment({ id: 1 }), makeAttachment({ id: 2 })];
      const postSpy = vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { data: attachments } });

      const { upload } = useAttachments();
      const file1 = new File(['content1'], 'file1.pdf', { type: 'application/pdf' });
      const file2 = new File(['content2'], 'file2.docx', { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });

      const result = await upload('projects', 1, [file1, file2]);

      expect(postSpy).toHaveBeenCalledWith(
        '/projects/1/attachments',
        expect.any(FormData),
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      expect(result).toHaveLength(2);
      expect(result[0].id).toBe(1);
    });

    it('setea uploading a true durante la subida y a false al terminar', async () => {
      vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { data: [] } });
      const { upload, uploading } = useAttachments();

      const promise = upload('tasks', 5, [new File([''], 'test.txt')]);
      expect(uploading.value).toBe(true);
      await promise;
      expect(uploading.value).toBe(false);
    });

    it('setea error en fallo', async () => {
      vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('Upload failed'));
      const { upload } = useAttachments();

      await expect(upload('projects', 1, [new File([''], 'test.txt')])).rejects.toThrow('Upload failed');
    });

    it('setea uploading a false tras fallo', async () => {
      vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('fail'));
      const { upload, uploading } = useAttachments();

      try { await upload('projects', 1, [new File([''], 'test.txt')]); } catch { /* expected */ }
      expect(uploading.value).toBe(false);
    });

    it('soporta parentType tickets', async () => {
      const postSpy = vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { data: [] } });
      const { upload } = useAttachments();

      await upload('tickets', 10, [new File([''], 'ticket-file.pdf')]);

      expect(postSpy).toHaveBeenCalledWith(
        '/tickets/10/attachments',
        expect.any(FormData),
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
    });

    it('soporta parentType blockers', async () => {
      const postSpy = vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { data: [] } });
      const { upload } = useAttachments();

      await upload('blockers', 3, [new File([''], 'blocker-doc.pdf')]);

      expect(postSpy).toHaveBeenCalledWith(
        '/blockers/3/attachments',
        expect.any(FormData),
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
    });
  });

  describe('remove', () => {
    it('elimina adjunto por UUID y devuelve true', async () => {
      const deleteSpy = vi.spyOn(apiWithToken, 'delete').mockResolvedValueOnce({});
      const { remove } = useAttachments();
      const attachment = makeAttachment({ uuid: 'uuid-to-delete' });

      const result = await remove(attachment);

      expect(deleteSpy).toHaveBeenCalledWith('/attachments/uuid-to-delete');
      expect(result).toBe(true);
    });

    it('devuelve false en error', async () => {
      vi.spyOn(apiWithToken, 'delete').mockRejectedValueOnce(new Error('Fail'));
      const { remove } = useAttachments();

      const result = await remove(makeAttachment());

      expect(result).toBe(false);
    });
  });

  describe('replace', () => {
    it('reemplaza archivo manteniendo UUID', async () => {
      const replaced = makeAttachment({ id: 1, uuid: 'keep-uuid', original_name: 'nuevo.pdf' });
      const postSpy = vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { data: replaced } });
      const { replace } = useAttachments();
      const newFile = new File(['nuevo contenido'], 'nuevo.pdf', { type: 'application/pdf' });

      const result = await replace(makeAttachment({ uuid: 'keep-uuid' }), newFile);

      expect(postSpy).toHaveBeenCalledWith(
        '/attachments/keep-uuid/replace',
        expect.any(FormData),
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      expect(result).not.toBeNull();
      expect(result!.original_name).toBe('nuevo.pdf');
    });

    it('setea uploading durante el reemplazo', async () => {
      vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { data: makeAttachment() } });
      const { replace, uploading } = useAttachments();

      const promise = replace(makeAttachment(), new File([''], 'f.txt'));
      expect(uploading.value).toBe(true);
      await promise;
      expect(uploading.value).toBe(false);
    });

    it('devuelve null en error', async () => {
      vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('Replace failed'));
      const { replace } = useAttachments();

      const result = await replace(makeAttachment(), new File([''], 'f.txt'));

      expect(result).toBeNull();
    });
  });

  describe('download', () => {
    it('crea un enlace temporal y hace click', () => {
      const { download } = useAttachments();
      const attachment = makeAttachment({ download_url: 'https://example.com/dl/abc-123' });

      // Espiamos createElement y click
      const mockAnchor = {
        href: '',
        setAttribute: vi.fn(),
        click: vi.fn(),
      } as unknown as HTMLAnchorElement;
      const createElementSpy = vi.spyOn(document, 'createElement').mockReturnValueOnce(mockAnchor);
      const appendChildSpy = vi.spyOn(document.body, 'appendChild').mockImplementation(vi.fn());
      const removeChildSpy = vi.spyOn(document.body, 'removeChild').mockImplementation(vi.fn());

      download(attachment);

      expect(createElementSpy).toHaveBeenCalledWith('a');
      expect(mockAnchor.href).toBe('https://example.com/dl/abc-123');
      expect(mockAnchor.setAttribute).toHaveBeenCalledWith('target', '_blank');
      expect(mockAnchor.setAttribute).toHaveBeenCalledWith('rel', 'noopener noreferrer');
      expect(appendChildSpy).toHaveBeenCalledWith(mockAnchor);
      expect(mockAnchor.click).toHaveBeenCalled();
      expect(removeChildSpy).toHaveBeenCalledWith(mockAnchor);
    });
  });

  describe('clearError', () => {
    it('limpia el mensaje de error', () => {
      const { error, clearError } = useAttachments();
      error.value = 'Algo salió mal';
      clearError();
      expect(error.value).toBeNull();
    });
  });
});

describe('buildFormData', () => {
  it('construye FormData con campos planos y archivos', () => {
    const payload = { name: 'Proyecto X', status: 'planning', owner_id: 1 };
    const files = [new File(['a'], 'a.pdf'), new File(['b'], 'b.docx')];

    const fd = buildFormData(payload, files);

    expect(fd.get('name')).toBe('Proyecto X');
    expect(fd.get('status')).toBe('planning');
    expect(fd.get('owner_id')).toBe('1');

    const allAttachments = fd.getAll('attachments[]');
    expect(allAttachments).toHaveLength(2);
  });

  it('omite campos con valor null o undefined', () => {
    const payload = { name: 'X', description: null, budget: undefined };
    const fd = buildFormData(payload, []);

    expect(fd.get('name')).toBe('X');
    expect(fd.get('description')).toBeNull();
    expect(fd.get('budget')).toBeNull();
  });

  it('maneja payload vacío y sin archivos', () => {
    const fd = buildFormData({}, []);
    // No debe tener entradas
    const entries: string[] = [];
    fd.forEach((_value, key) => entries.push(key));
    expect(entries).toHaveLength(0);
  });
});
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DocumentManager from '@/components/common/DocumentManager.vue';
import type { AttachmentI } from '@/interfaces/AttachmentI';

const attachment: AttachmentI = {
  id: 1,
  uuid: 'abc-123',
  original_name: 'document.pdf',
  disk_path: '/projects/uuid-1/abc-123.pdf',
  mime_type: 'application/pdf',
  size: 102400,
  download_url: 'https://example.com/api/v1/attachments/download/abc-123',
};

function factory(props: Record<string, unknown> = {}) {
  return mount(DocumentManager, {
    props: { parentType: 'tickets', parentId: 1, attachments: [attachment], ...props },
  });
}

describe('DocumentManager · canUpload vs canManage', () => {
  it('permite subir con canUpload=true aunque canManage=false (cliente adjunta evidencia)', () => {
    const wrapper = factory({ canUpload: true, canManage: false });

    expect(wrapper.text()).toContain('Subir archivos');
    // Sin permiso de gestión: no debe ofrecer reemplazar ni eliminar.
    expect(wrapper.html()).not.toContain('ri-file-transfer-line');
    expect(wrapper.html()).not.toContain('ri-delete-bin-line');
  });

  it('oculta el botón de subida con canUpload=false', () => {
    const wrapper = factory({ canUpload: false, canManage: false });
    expect(wrapper.text()).not.toContain('Subir archivos');
  });

  it('hereda canUpload de canManage cuando no se especifica (compatibilidad)', () => {
    const wrapper = factory({ canManage: true });
    expect(wrapper.text()).toContain('Subir archivos');
    expect(wrapper.html()).toContain('ri-file-transfer-line');
  });

  it('con canManage=true muestra las acciones de gestión (reemplazar/eliminar)', () => {
    const wrapper = factory({ canManage: true, canUpload: true });
    expect(wrapper.text()).toContain('Subir archivos');
    expect(wrapper.html()).toContain('ri-file-transfer-line');
    expect(wrapper.html()).toContain('ri-delete-bin-line');
  });
});

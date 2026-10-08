import { describe, it, expect } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import RichTextEditor from '@/components/common/RichTextEditor.vue';

function editorHtml(wrapper: ReturnType<typeof mount>): string {
  return wrapper.find('.ProseMirror').html();
}

// `EditorContent` inserta el DOM de ProseMirror dentro de un `nextTick` anidado,
// así que hay que vaciar la cola de microtareas antes de leer el contenido.
async function settle(): Promise<void> {
  await flushPromises();
}

describe('RichTextEditor · sincronización de modelValue', () => {
  it('renderiza el contenido inicial recibido por modelValue', async () => {
    const wrapper = mount(RichTextEditor, {
      props: { modelValue: '<p>Texto inicial</p>' },
    });
    await settle();

    expect(editorHtml(wrapper)).toContain('Texto inicial');
  });

  it('carga el texto cuando modelValue llega después del montaje (edición async)', async () => {
    // Al abrir la edición, el formulario arranca vacío y los datos llegan luego.
    const wrapper = mount(RichTextEditor, { props: { modelValue: '' } });
    await settle();
    expect(editorHtml(wrapper)).not.toContain('Descripción guardada del ticket');

    // Simula `onMounted → form.value = response.items`.
    await wrapper.setProps({ modelValue: '<p>Descripción guardada del ticket</p>' });
    await settle();

    expect(editorHtml(wrapper)).toContain('Descripción guardada del ticket');
  });

  it('no re-emite update:modelValue al sincronizar un cambio externo', async () => {
    const wrapper = mount(RichTextEditor, { props: { modelValue: '' } });
    await settle();

    await wrapper.setProps({ modelValue: '<p>Contenido cargado</p>' });
    await settle();

    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
  });

  it('limpia el editor cuando modelValue pasa a null/vacío', async () => {
    const wrapper = mount(RichTextEditor, { props: { modelValue: '<p>Algo</p>' } });
    await settle();
    expect(editorHtml(wrapper)).toContain('Algo');

    await wrapper.setProps({ modelValue: null });
    await settle();

    expect(editorHtml(wrapper)).not.toContain('Algo');
  });
});

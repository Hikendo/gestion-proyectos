<script setup lang="ts">
import { watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';

const props = defineProps<{
    modelValue: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [StarterKit],
    editable: !props.disabled,
    editorProps: {
        attributes: {
            class: 'rich-text-editor-content',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.disabled, (val) => {
    editor.value?.setEditable(!val);
});
</script>

<template>
    <div class="rich-text-editor-wrapper" :class="{ 'rich-text-editor-disabled': disabled }">
        <div v-if="editor && !disabled" class="rich-text-editor-toolbar">
            <button type="button" @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'is-active': editor.isActive('bold') }" title="Negrita">
                <strong>B</strong>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'is-active': editor.isActive('italic') }" title="Cursiva">
                <em>I</em>
            </button>
            <button type="button" @click="editor.chain().focus().toggleStrike().run()"
                :class="{ 'is-active': editor.isActive('strike') }" title="Tachado">
                <s>S</s>
            </button>
            <span class="toolbar-divider" />
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }" title="Título 1">
                H1
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" title="Título 2">
                H2
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" title="Título 3">
                H3
            </button>
            <span class="toolbar-divider" />
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'is-active': editor.isActive('bulletList') }" title="Lista con viñetas">
                &bull; Lista
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'is-active': editor.isActive('orderedList') }" title="Lista numerada">
                1. Lista
            </button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{ 'is-active': editor.isActive('blockquote') }" title="Cita">
                &ldquo; Cita
            </button>
            <span class="toolbar-divider" />
            <button type="button" @click="editor.chain().focus().undo().run()" title="Deshacer">
                &#x21A9;
            </button>
            <button type="button" @click="editor.chain().focus().redo().run()" title="Rehacer">
                &#x21AA;
            </button>
        </div>
        <EditorContent :editor="editor" class="rich-text-editor-body" />
    </div>
</template>

<style scoped>
.rich-text-editor-wrapper {
    border: 1px solid rgba(var(--v-border-color), 0.42);
    border-radius: 4px;
    overflow: hidden;
}

.rich-text-editor-wrapper:focus-within {
    border-color: rgb(var(--v-theme-primary));
}

.rich-text-editor-disabled {
    opacity: 0.6;
    pointer-events: none;
}

.rich-text-editor-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 6px 8px;
    background: rgba(var(--v-theme-surface-variant), 0.3);
    border-bottom: 1px solid rgba(var(--v-border-color), 0.42);
}

.rich-text-editor-toolbar button {
    background: transparent;
    border: 1px solid transparent;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 13px;
    color: rgba(var(--v-theme-on-surface), 0.87);
    min-width: 32px;
    text-align: center;
    transition: background 0.15s;
}

.rich-text-editor-toolbar button:hover {
    background: rgba(var(--v-theme-on-surface), 0.08);
}

.rich-text-editor-toolbar button.is-active {
    background: rgba(var(--v-theme-primary), 0.12);
    border-color: rgb(var(--v-theme-primary));
    color: rgb(var(--v-theme-primary));
}

.toolbar-divider {
    width: 1px;
    background: rgba(var(--v-border-color), 0.42);
    margin: 0 4px;
    align-self: stretch;
}

.rich-text-editor-body {
    padding: 8px 12px;
    min-height: 120px;
    max-height: 400px;
    overflow-y: auto;
}

.rich-text-editor-body :deep(.ProseMirror) {
    outline: none;
    min-height: 100px;
    font-size: 14px;
    line-height: 1.6;
}

.rich-text-editor-body :deep(.ProseMirror p) {
    margin: 0 0 8px 0;
}

.rich-text-editor-body :deep(.ProseMirror h1) {
    font-size: 1.5em;
    margin: 12px 0 4px;
}

.rich-text-editor-body :deep(.ProseMirror h2) {
    font-size: 1.3em;
    margin: 10px 0 4px;
}

.rich-text-editor-body :deep(.ProseMirror h3) {
    font-size: 1.15em;
    margin: 8px 0 4px;
}

.rich-text-editor-body :deep(.ProseMirror ul),
.rich-text-editor-body :deep(.ProseMirror ol) {
    padding-left: 20px;
    margin: 0 0 8px 0;
}

.rich-text-editor-body :deep(.ProseMirror blockquote) {
    border-left: 3px solid rgba(var(--v-border-color), 0.7);
    padding-left: 12px;
    margin: 0 0 8px 0;
    color: rgba(var(--v-theme-on-surface), 0.6);
}
</style>
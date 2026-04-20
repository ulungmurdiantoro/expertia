<script setup>
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Tulis konten di sini...',
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit,
        Underline,
        Link.configure({
            openOnClick: false,
            autolink: true,
            defaultProtocol: 'https',
        }),
        Image,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'tiptap-editor min-h-[420px] w-full p-5 text-base leading-8 text-zinc-700 focus:outline-none',
        },
    },
    onUpdate: ({ editor: editorInstance }) => {
        emit('update:modelValue', editorInstance.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;

        const html = value || '';
        if (editor.value.getHTML() !== html) {
            editor.value.commands.setContent(html, false);
        }
    }
);

const setLink = () => {
    if (!editor.value) return;

    const previousUrl = editor.value.getAttributes('link').href;
    const url = window.prompt('Masukkan URL', previousUrl || 'https://');

    if (url === null) return;

    if (url.trim() === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};

const insertImageByUrl = () => {
    if (!editor.value) return;

    const url = window.prompt('Masukkan URL gambar', 'https://');
    if (!url || !url.trim()) return;

    editor.value.chain().focus().setImage({ src: url.trim() }).run();
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <div v-if="editor" class="flex flex-wrap items-center gap-2 border-b border-zinc-200 bg-zinc-50 p-3 text-sm">
            <button type="button" @click="editor.chain().focus().undo().run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Undo">Undo</button>
            <button type="button" @click="editor.chain().focus().redo().run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Redo">Redo</button>

            <span class="mx-1 h-6 w-px bg-zinc-300"></span>

            <button type="button" @click="editor.chain().focus().setParagraph().run()" :class="editor.isActive('paragraph') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Paragraph">P</button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="editor.isActive('heading', { level: 2 }) ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Heading 2">H2</button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="editor.isActive('heading', { level: 3 }) ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Heading 3">H3</button>

            <span class="mx-1 h-6 w-px bg-zinc-300"></span>

            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="editor.isActive('bold') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 font-bold text-zinc-700 hover:bg-zinc-100" title="Bold">B</button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="editor.isActive('italic') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 italic text-zinc-700 hover:bg-zinc-100" title="Italic">I</button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="editor.isActive('underline') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Underline">U</button>
            <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="editor.isActive('strike') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Strikethrough">S</button>

            <span class="mx-1 h-6 w-px bg-zinc-300"></span>

            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="editor.isActive('bulletList') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Bullet List">• List</button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="editor.isActive('orderedList') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Numbered List">1. List</button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="editor.isActive('blockquote') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Quote">Quote</button>
            <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()" :class="editor.isActive('codeBlock') ? 'bg-zinc-200' : 'bg-white'" class="rounded-md border border-zinc-300 px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Code Block">Code</button>

            <span class="mx-1 h-6 w-px bg-zinc-300"></span>

            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Align Left">Left</button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Align Center">Center</button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Align Right">Right</button>

            <span class="mx-1 h-6 w-px bg-zinc-300"></span>

            <button type="button" @click="setLink" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Set Link">Link</button>
            <button type="button" @click="editor.chain().focus().unsetLink().run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Remove Link">Unlink</button>
            <button type="button" @click="insertImageByUrl" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Insert Image URL">Image URL</button>
            <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Horizontal Rule">HR</button>
            <button type="button" @click="editor.chain().focus().clearNodes().unsetAllMarks().run()" class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-zinc-700 hover:bg-zinc-100" title="Clear Formatting">Clear</button>
        </div>

        <EditorContent v-if="editor" :editor="editor" />
    </div>
</template>

<style scoped>
:deep(.tiptap-editor > * + *) {
    margin-top: 0.85em;
}

:deep(.tiptap-editor h2) {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.3;
}

:deep(.tiptap-editor h3) {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.35;
}

:deep(.tiptap-editor ul),
:deep(.tiptap-editor ol) {
    padding-left: 1.3rem;
    color: #3f3f46;
}

:deep(.tiptap-editor ul) {
    list-style-type: disc;
}

:deep(.tiptap-editor ol) {
    list-style-type: decimal;
}

:deep(.tiptap-editor li::marker) {
    color: #3f3f46;
}

:deep(.tiptap-editor blockquote) {
    border-left: 3px solid #d4d4d8;
    padding-left: 0.85rem;
    color: #52525b;
}

:deep(.tiptap-editor pre) {
    background: #18181b;
    color: #fafafa;
    border-radius: 0.5rem;
    padding: 0.75rem;
    white-space: pre-wrap;
}

:deep(.tiptap-editor p.is-editor-empty:first-child::before) {
    color: #a1a1aa;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}
</style>

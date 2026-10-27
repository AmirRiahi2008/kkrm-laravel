import { Editor, Node } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Image from "@tiptap/extension-image";
import { TableKit } from "@tiptap/extension-table";
const Gallery = Node.create({
    name: "imageGallery",
    group: "block",
    atom: true,
    selectable: true,
    draggable: true,
    addAttributes() {
        return {
            images: { default: [] },
            title: { default: "" },
            columns: { default: 3 },
        };
    },
    parseHTML() {
        return [
            {
                tag: 'figure[data-kkr-gallery="1"]',
                getAttrs: (element) => ({
                    columns: Number(element.dataset.columns) || 3,
                    title:
                        element.querySelector("figcaption")?.textContent || "",
                    images: Array.from(element.querySelectorAll("img")).map(
                        (i) => ({ src: i.getAttribute("src"), alt: i.alt }),
                    ),
                }),
            },
        ];
    },
    renderHTML({ node }) {
        return [
            "figure",
            { "data-kkr-gallery": "1", "data-columns": node.attrs.columns },
            ...node.attrs.images.map((image) => [
                "a",
                { href: image.src },
                [
                    "img",
                    { src: image.src, alt: image.alt || "", loading: "lazy" },
                ],
            ]),
            ...(node.attrs.title ? [["figcaption", {}, node.attrs.title]] : []),
        ];
    },
});
export function registerEditor(Alpine, { api, mediaUrl, notify, safeHref }) {
    Alpine.data("richEditor", (key) => {
        let editor;
        return {
            init() {
                editor = new Editor({
                    element: this.$refs.editor,
                    extensions: [
                        StarterKit.configure({
                            link: {
                                openOnClick: false,
                                protocols: ["https", "http"],
                            },
                        }),
                        Image,
                        TableKit.configure({ table: { resizable: true } }),
                        Gallery,
                    ],
                    content: this.values[key] || "",
                    onUpdate: ({ editor: e }) => {
                        this.values[key] = e.getHTML();
                    },
                    editorProps: {
                        attributes: {
                            dir: "rtl",
                            class: "prose-editor",
                            role: "textbox",
                            "aria-multiline": "true",
                            "aria-label": "ویرایش متن",
                        },
                    },
                });
            },
            destroy() {
                editor?.destroy();
            },
            update(html) {
                if (editor && !editor.isDestroyed && editor.getHTML() !== html)
                    editor.commands.setContent(html, { emitUpdate: false });
            },
            command(name) {
                if (!editor || editor.isDestroyed) return;
                const chain = editor.chain().focus();
                const commands = {
                    bold: () => chain.toggleBold().run(),
                    italic: () => chain.toggleItalic().run(),
                    heading: () => chain.toggleHeading({ level: 2 }).run(),
                    bulletList: () => chain.toggleBulletList().run(),
                    orderedList: () => chain.toggleOrderedList().run(),
                    blockquote: () => chain.toggleBlockquote().run(),
                    unlink: () => chain.unsetLink().run(),
                    undo: () => chain.undo().run(),
                    redo: () => chain.redo().run(),
                    table: () =>
                        chain
                            .insertTable({
                                rows: 3,
                                cols: 3,
                                withHeaderRow: true,
                            })
                            .run(),
                    addRow: () => chain.addRowAfter().run(),
                    addColumn: () => chain.addColumnAfter().run(),
                    deleteRow: () => chain.deleteRow().run(),
                    deleteColumn: () => chain.deleteColumn().run(),
                    mergeCells: () => chain.mergeCells().run(),
                    splitCell: () => chain.splitCell().run(),
                    deleteTable: () => chain.deleteTable().run(),
                    link: () => {
                        const href = prompt(
                            "نشانی لینک",
                            editor.getAttributes("link").href || "",
                        );
                        if (href === null) return;
                        if (href === "") chain.unsetLink().run();
                        else if (safeHref(href))
                            chain
                                .extendMarkRange("link")
                                .setLink({ href: safeHref(href) })
                                .run();
                        else notify("نشانی معتبر وارد کنید.", "error");
                    },
                };
                commands[name]?.();
            },
            async upload(event, kind) {
                const files = Array.from(event.target.files);
                event.target.value = "";
                if (!files.length || !editor || editor.isDestroyed) return;
                const max = 50 * 1024 * 1024;
                for (const file of files)
                    if (
                        ![
                            "image/jpeg",
                            "image/png",
                            "image/webp",
                            "application/pdf",
                        ].includes(file.type) ||
                        file.size > max
                    ) {
                        notify(
                            "فایل باید تصویر یا PDF و حداکثر ۵۰ مگابایت باشد.",
                            "error",
                        );
                        return;
                    }
                const position = editor.state.selection.from;
                this.uploads++;
                editor.setEditable(false);
                try {
                    const loaded = [];
                    for (const file of files) {
                        const body = new FormData();
                        body.set("file", file);
                        body.set("alt", file.name);
                        const r = await api("/api/v1/admin/media", {
                            method: "POST",
                            body,
                        });
                        loaded.push({
                            src: mediaUrl(r.data),
                            alt: file.name,
                            mime: r.data.mime_type,
                        });
                    }
                    if (editor.isDestroyed) return;
                    editor.setEditable(true);
                    let content;
                    if (kind === "gallery")
                        content = [
                            {
                                type: "imageGallery",
                                attrs: {
                                    images: loaded,
                                    title: "",
                                    columns: 3,
                                },
                            },
                            { type: "paragraph" },
                        ];
                    else if (kind === "file")
                        content = loaded.map((file) => ({
                            type: "paragraph",
                            content: [
                                {
                                    type: "text",
                                    text: file.alt,
                                    marks: [
                                        {
                                            type: "link",
                                            attrs: {
                                                href: file.src,
                                                target: "_blank",
                                                rel: "noopener noreferrer",
                                            },
                                        },
                                    ],
                                },
                            ],
                        }));
                    else
                        content = loaded.flatMap((file) => [
                            {
                                type: "image",
                                attrs: { src: file.src, alt: file.alt },
                            },
                            { type: "paragraph" },
                        ]);
                    editor
                        .chain()
                        .focus()
                        .insertContentAt(position, content)
                        .run();
                    this.values[key] = editor.getHTML();
                    notify("فایل درج شد؛ مطلب را ذخیره کنید.");
                } catch (e) {
                    notify(e.message, "error");
                } finally {
                    if (editor && !editor.isDestroyed) editor.setEditable(true);
                    this.uploads = Math.max(0, this.uploads - 1);
                }
            },
        };
    });
}

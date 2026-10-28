import { Editor, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';

const ImageGallery = Node.create({
    name: 'imageGallery', group: 'block', atom: true, selectable: true, draggable: true,
    addAttributes() {
        return { images: {default: [], rendered: false}, title: {default: '', rendered: false}, columns: {default: 3, rendered: false} };
    },
    parseHTML() {
        return [{tag:'figure[data-kkr-gallery="1"]',getAttrs:element=>({
            columns: [2,3,4].includes(Number(element.dataset.columns)) ? Number(element.dataset.columns) : 3,
            title: element.querySelector('figcaption')?.textContent||'',
            images: Array.from(element.querySelectorAll('img')).map(image=>({src:image.getAttribute('src'),alt:image.alt||''}))
        })}];
    },
    renderHTML({node}) {
        return ['figure',{'data-kkr-gallery':'1','data-columns':String(node.attrs.columns)},
            ...node.attrs.images.map(image=>['a',{href:image.src,title:image.alt||'نمایش تصویر بزرگ‌تر'},['img',{src:image.src,alt:image.alt||'',loading:'lazy'}]]),
            ...(node.attrs.title?[['figcaption',{},node.attrs.title]]:[])];
    }
});

export function registerEditor(Alpine,{api,mediaUrl,notify,safeHref}) {
    Alpine.data('richEditor',key=>{
        let editor;
        let bookmark;
        let galleryPosition=null;
        return {
            rows:3, columns:6, uploading:false, editorError:'',
            active:{bold:false,italic:false,table:false,gallery:false},
            gallery:{title:'',columns:3,images:[]},
            getValue() { return key.startsWith('english.') ? this.english[key.slice(8)]||'' : this.values[key]||''; },
            setValue(html) { if(key.startsWith('english.'))this.english[key.slice(8)]=html;else this.values[key]=html; },
            init() {
                editor=new Editor({element:this.$refs.editor,
                    extensions:[StarterKit.configure({link:{openOnClick:false,protocols:['http','https']}}),Image,TableKit.configure({table:{resizable:false}}),ImageGallery],
                    content:this.getValue(),
                    onUpdate:({editor})=>{this.setValue(editor.getHTML());this.refresh();},
                    onSelectionUpdate:()=>this.refresh(),
                    editorProps:{attributes:{class:'prose-editor',dir:key.startsWith('english.')?'ltr':'rtl',role:'textbox','aria-multiline':'true','aria-label':'ویرایش متن'}}
                });
                this.$watch(key.startsWith('english.')?key:'values.'+key,html=>this.update(html||''));
                this.refresh();
            },
            refresh() {
                if(editor&&!editor.isDestroyed)this.active={bold:editor.isActive('bold'),italic:editor.isActive('italic'),table:editor.isActive('table'),gallery:editor.isActive('imageGallery')};
            },
            update(html) { if(editor&&!editor.isDestroyed&&editor.getHTML()!==html)editor.commands.setContent(html,{emitUpdate:false}); },
            destroy() { editor?.destroy(); },
            command(name) {
                if(!editor||editor.isDestroyed||this.uploading)return;
                const chain=editor.chain().focus();
                const commands={bold:'toggleBold',italic:'toggleItalic',bulletList:'toggleBulletList',orderedList:'toggleOrderedList',blockquote:'toggleBlockquote',unlink:'unsetLink',undo:'undo',redo:'redo'};
                if(commands[name])chain[commands[name]]().run();
                else if(name==='heading')chain.toggleHeading({level:2}).run();
                else if(name==='table')chain.insertTable({rows:Math.min(30,Math.max(1,Number(this.rows)||3)),cols:Math.min(12,Math.max(1,Number(this.columns)||6)),withHeaderRow:true}).run();
                else if(name==='link') {
                    const href=prompt('نشانی لینک',editor.getAttributes('link').href||'');
                    if(href===null)return;
                    if(!href)chain.unsetLink().run();
                    else if(safeHref(href))chain.extendMarkRange('link').setLink({href:safeHref(href)}).run();
                    else this.editorError='نشانی معتبر وارد کنید.';
                } else if(this.active.table&&typeof chain[name]==='function')chain[name]().run();
                this.refresh();
            },
            pick(kind) { bookmark=editor.state.selection.getBookmark();this.$refs[kind].click(); },
            openGallery() {
                if(!editor||this.uploading)return;
                bookmark=editor.state.selection.getBookmark();
                galleryPosition=editor.isActive('imageGallery')?editor.state.selection.from:null;
                const attrs=galleryPosition!==null?editor.getAttributes('imageGallery'):{title:'',columns:3,images:[]};
                this.gallery={title:attrs.title||'',columns:attrs.columns||3,images:(attrs.images||[]).map(image=>({...image,key:crypto.randomUUID()}))};
                this.$refs.galleryDialog.showModal();
            },
            moveImage(index,delta) {
                const target=index+delta;
                if(target<0||target>=this.gallery.images.length)return;
                const [image]=this.gallery.images.splice(index,1);this.gallery.images.splice(target,0,image);
            },
            saveGallery() {
                if(!editor||this.uploading||!this.gallery.images.length)return;
                const attrs={title:this.gallery.title,columns:this.gallery.columns,images:this.gallery.images.map(({src,alt})=>({src,alt}))};
                if(galleryPosition!==null&&editor.state.doc.nodeAt(galleryPosition)?.type.name==='imageGallery')editor.chain().focus().setNodeSelection(galleryPosition).updateAttributes('imageGallery',attrs).run();
                else {
                    const selection=bookmark?.resolve(editor.state.doc)||editor.state.selection;
                    editor.chain().command(({tr})=>{tr.setSelection(selection);return true;}).insertContent([{type:'imageGallery',attrs},{type:'paragraph'}]).focus().run();
                }
                this.$refs.galleryDialog.close();this.refresh();
            },
            async upload(event,kind) {
                const files=Array.from(event.target.files||[]);event.target.value='';
                if(!files.length||!editor||editor.isDestroyed||this.uploading)return;
                this.editorError='';
                const allowed=kind==='file'?['application/pdf']:['image/jpeg','image/png','image/webp'];
                if(files.some(file=>!allowed.includes(file.type)||!file.size||file.size>50*1024*1024)) {this.editorError='فایل معتبر و حداکثر ۵۰ مگابایت انتخاب کنید.';return;}
                bookmark??=editor.state.selection.getBookmark();
                this.uploading=true;this.uploads++;editor.setEditable(false);
                try {
                    const loaded=[];
                    for(const file of files) {
                        const body=new FormData();body.set('file',file);body.set('alt',file.name.slice(0,250));
                        const {data}=await api('/api/v1/admin/media',{method:'POST',body});
                        loaded.push({src:mediaUrl(data),alt:file.name});
                    }
                    if(editor.isDestroyed)return;
                    if(kind==='gallery')this.gallery.images.push(...loaded.map(image=>({...image,key:crypto.randomUUID()})));
                    else {
                        const selection=bookmark.resolve(editor.state.doc);
                        const content=kind==='file'?loaded.map(file=>({type:'text',text:'دریافت PDF: '+file.alt,marks:[{type:'link',attrs:{href:file.src,target:'_blank',rel:'noopener noreferrer'}}]})):loaded.flatMap(image=>[{type:'image',attrs:image},{type:'paragraph'}]);
                        editor.chain().command(({tr})=>{tr.setSelection(selection);return true;}).insertContent(content).focus().run();bookmark=null;
                    }
                } catch(error) {this.editorError=error.message;}
                finally {this.uploading=false;this.uploads=Math.max(0,this.uploads-1);if(!editor.isDestroyed)editor.setEditable(true);}
            }
        };
    });
}

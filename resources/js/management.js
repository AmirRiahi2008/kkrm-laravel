export function registerManagement(Alpine, helpers) {
    const {
        api,
        title,
        mediaUrl,
        formatDate,
        formatNumber,
        digits,
        publicPath,
        notify,
        safeHref,
    } = helpers;
    const clone = (value) => JSON.parse(JSON.stringify(value));
    const rules = () => ({
        base_fee: 0,
        minimum_fee: 0,
        maximum_fee: null,
        tiers: [{ up_to: null, rate_bps: 0 }],
        panel_multipliers: { 1: 10000, 2: 10000, 3: 10000, 4: 10000, 5: 10000 },
    });
    Alpine.data("resourceTable", (resource, config) => ({
        resource,
        config,
        items: [],
        pagination: {},
        page: 1,
        q: "",
        status: "",
        type: "",
        trash: false,
        error: "",
        loading: false,
        audit: "",
        title,
        formatDate,
        formatNumber,
        pageNumbers() { return Array.from({length:Math.max(0,Math.min(this.pagination.last_page||1,this.page+2)-Math.max(1,this.page-2)+1)},(_,i)=>Math.max(1,this.page-2)+i); },
        statusLabel(item) { return ({draft:"پیش‌نویس",review:"در انتظار بررسی",published:"منتشرشده",archived:"بایگانی"})[item.status] || item.type || item.role || (item.is_active===undefined?"—":item.is_active?"فعال":"غیرفعال"); },
        publicPath: (item) => publicPath(resource, item),
        placement(item) { return item.location === "header" ? ({main:"ناوبری اصلی",both:"ناوبری اصلی و مگامنو",mega:"داخل مگامنو"}[item.display_mode]||"داخل مگامنو") : ({footer:"پایین سایت (فوتر)",mobile:"منوی اختصاصی موبایل",quick:"دسترسی سریع"}[item.location]||""); },
        async init() {
            const p = new URLSearchParams(location.search);
            this.page = Math.max(1,Number(p.get("page"))||1);
            this.type = p.get("type") || "";
            this.q = p.get("q") || "";
            this.status = p.get("status") || "";
            this.trash = p.get("trash") === "1";
            await this.load();
        },
        async load() {
            this.loading = true;
            this.error = "";
            try {
                const q = new URLSearchParams({
                    page: String(this.page),
                    per_page: "15",
                    ...(this.q ? { q: this.q } : {}),
                    ...(this.status ? { status: this.status } : {}),
                    ...(this.type ? { type: this.type } : {}),
                    ...(this.trash ? { trash: "1" } : {}),
                });
                const r = await api("/api/v1/admin/" + resource + "?" + q);
                this.items = r.data;
                this.pagination = r.pagination || {};
                history.replaceState(null,"",location.pathname+"?"+q);
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
        async mutate(item) {
            if (
                !confirm(
                    (this.trash ? "بازیابی" : "حذف") +
                        " «" +
                        title(item) +
                        "»؟",
                )
            )
                return;
            try {
                await api(
                    "/api/v1/admin/" +
                        resource +
                        "/" +
                        item.id +
                        (this.trash ? "/restore" : ""),
                    { method: this.trash ? "POST" : "DELETE" },
                );
                notify("تغییرات ذخیره شد.");
                await this.load();
            } catch (e) {
                this.error = e.message;
            }
        },
        showAudit(item) {
            this.audit = JSON.stringify(item, null, 2);
            this.$refs.audit.showModal();
        },
    }));
    Alpine.data("resourceForm", (resource, config, initial, role = "relations_manager") => ({
        resource,
        config,
        record: initial,
        role,
        previewIntent: false,
        automaticPath: !initial,
        lastAutoSlug: "",
        albumChoice: "",
        values: {},
        baseline: {},
        english: clone(initial?.translations?.en || {}),
        englishBaseline: clone(initial?.translations?.en || {}),
        references: {},
        referencesLoading: false,
        selectedMedia: {},
        uploads: 0,
        busy: false,
        error: "",
        errors: {},
        albumItems: (initial?.items || []).map((i) => ({
            ...i,
            key: crypto.randomUUID(),
        })),
        title,
        mediaUrl,
        formatNumber,
        pagePath() { return this.record ? publicPath("pages",this.record) : ""; },
        menuDestination() { return this.values.location==="header" ? (["main","both"].includes(this.values.display_mode)?this.values.display_mode:"mega") : this.values.location||"main"; },
        destinationBlocked(key) { const location=["main","mega","both"].includes(key)?"header":key;return !!this.record&&(this.references["menu-items"]||[]).some(item=>Number(item.parent_id)===Number(this.record.id))&&(location!==this.values.location||key==="main"); },
        chooseDestination(key) { const location=["main","mega","both"].includes(key)?"header":key;const same=this.values.location===location;this.values.parent_id=same&&!["main","both","footer"].includes(key)?this.values.parent_id||null:null;this.values.location=location;this.values.display_mode=["main","both"].includes(key)?key:"mega"; },
        menuParentLabel(entry) { const parts=[title(entry)];const seen=new Set([Number(entry.id)]);let parent=entry.parent_id;while(parent&&!seen.has(Number(parent))) { seen.add(Number(parent));const found=(this.references["menu-items"]||[]).find(item=>Number(item.id)===Number(parent));if(!found)break;parts.unshift(title(found));parent=found.parent_id; } return parts.join(" ← "); },
        menuPreview() { const name={main:"ناوبری اصلی",mega:"مگامنو بخش‌های کانون",both:"ناوبری اصلی و مگامنو",footer:"پایین سایت (فوتر)",mobile:"منوی موبایل",quick:"دسترسی سریع"}[this.menuDestination()]||"";const parent=(this.references["menu-items"]||[]).find(item=>Number(item.id)===Number(this.values.parent_id));return [name,parent?this.menuParentLabel(parent):"",this.values.title||"لینک شما"].filter(Boolean).join(" ← "); },
        addAlbumChoice() { const item=(this.references.media||[]).find(item=>String(item.id)===String(this.albumChoice));if(item&&!this.albumItems.some(entry=>entry.media.id===item.id))this.albumItems.push({media:item,caption:"",caption_en:"",key:crypto.randomUUID()});this.albumChoice=""; },
        init() {
            for (const field of config.fields) {
                let value = initial?.[field.key];
                const relation = {
                    tag_ids: "tags",
                    attachment_ids: "attachments",
                    qualification_ids: "qualifications",
                }[field.key];
                if (relation)
                    value = (initial?.[relation] || []).map((i) =>
                        String(i.id),
                    );
                if (value == null)
                    value =
                        field.type === "boolean"
                            ? ["is_active", "is_published"].includes(field.key)
                            : field.type === "multi"
                              ? []
                              : field.type === "rules"
                                ? rules()
                                : field.type === "number"
                                  ? 0
                                  : field.type === "select"
                                    ? field.options?.draft
                                        ? "draft"
                                        : Object.keys(field.options || {})[0] ||
                                          ""
                                    : "";
                if (["ref", "media"].includes(field.type) && value !== "")
                    value = String(value);
                this.values[field.key] = value;
                if (field.type === "media") {
                    const name = field.key.replace(/_id$/, "");
                    this.selectedMedia[field.key] = initial?.[name] || null;
                }
            }
            if (!initial && resource === "menu-items") {
                this.values.location = "header";
                this.values.display_mode = "main";
                this.values.parent_id = null;
            }
            if (!initial && resource === "posts") {
                const type = new URLSearchParams(location.search).get("type");
                if (
                    config.fields.find((f) => f.key === "type")?.options?.[type]
                )
                    this.values.type = type;
            }
            this.lastAutoSlug=this.values.slug||"";
            this.baseline = clone(this.values);
            this.loadReferences().then(()=>{if(!initial&&resource==="menu-items") { const id=new URLSearchParams(location.search).get("page_id");if(id&&(this.references.pages||[]).some(item=>String(item.id)===id)){this.values.page_id=id;this.choosePage();this.baseline=clone(this.values);} } });
            window.addEventListener("beforeunload", (event) => {
                if (
                    !this.busy &&
                    (JSON.stringify(this.values) !==
                        JSON.stringify(this.baseline) || JSON.stringify(this.english)!==JSON.stringify(this.englishBaseline))
                ) {
                    event.preventDefault();
                    event.returnValue = "";
                }
            });
        },
        async loadReferences() {
            this.referencesLoading = true;
            const sources = [
                ...new Set(
                    config.fields
                        .filter((f) => f.source || f.type === "media")
                        .map((f) => f.source || "media").concat(resource==="albums"?["media"]:[]),
                ),
            ];
            await Promise.all(
                sources.map(async (source) => {
                    try {
                        let page = 1,
                            items = [],
                            last = 1;
                        do {
                            const r = await api(
                                (role==="editor"&&source!=="media"?"/api/v1/":"/api/v1/admin/") +
                                    source +
                                    "?per_page=50&page=" +
                                    page,
                                { background: true },
                            );
                            items.push(...r.data);
                            last = r.pagination?.last_page || 1;
                            page++;
                        } while (page <= last);
                        this.references[source] = items;
                    } catch (e) {
                        this.error = e.message;
                    }
                }),
            ).finally(() => {
                this.referencesLoading = false;
            });
        },
        choices(source, key) {
            let items = this.references[source] || [];
            if (resource === "menu-items" && key === "parent_id") {
                const descendants = new Set(
                    this.record ? [Number(this.record.id)] : [],
                );
                let changed = true;
                while (changed) {
                    changed = false;
                    for (const item of items)
                        if (
                            item.parent_id &&
                            descendants.has(Number(item.parent_id)) &&
                            !descendants.has(Number(item.id))
                        ) {
                            descendants.add(Number(item.id));
                            changed = true;
                        }
                }
                items = items.filter(
                    (i) =>
                        i.location === this.values.location &&
                        !descendants.has(Number(i.id)),
                );
            }
            return items;
        },
        changed(key) {
            if (!this.record && ['title','name'].includes(key) && 'slug' in this.values) {
                const slug = String(this.values[key]||'').normalize('NFKC').trim().toLowerCase().replace(/[^\p{L}\p{N}]+/gu,'-').replace(/^-|-$/g,'').slice(0,180);
                if (!this.values.slug || this.values.slug===this.lastAutoSlug) {
                    this.values.slug=slug;
                    this.lastAutoSlug=slug;
                }
                if (resource==='pages'&&this.automaticPath) this.values.path='/'+slug;
            }
        },
        choosePage() {
            const page = (this.references.pages || []).find(
                (i) => String(i.id) === String(this.values.page_id),
            );
            if (page) {
                this.values.url =
                    page.public_path || page.path || "/pages/" + page.slug;
                if (!this.values.title) this.values.title = title(page);
            }
        },
        async uploadField(event, key) {
            const file = event.target.files[0];
            event.target.value = "";
            if (!file) return;
            this.uploads++;
            try {
                const body = new FormData();
                body.set("file", file);
                body.set("alt", file.name);
                const r = await api("/api/v1/admin/media", {
                    method: "POST",
                    body,
                });
                this.values[key] = String(r.data.id);
                this.selectedMedia[key] = r.data;
                this.references.media = [
                    r.data,
                    ...(this.references.media || []),
                ];
                notify("فایل بارگذاری شد؛ تغییرات را ذخیره کنید.");
            } catch (e) {
                this.error = e.message;
            } finally {
                this.uploads--;
            }
        },
        async save(preview = false) {
            if (this.busy || this.uploads > 0) return;
            const dates = this.$el.querySelectorAll(".admin-jalali-field");
            const invalidDate = Array.from(dates).some((field) => {
                const parts = Object.fromEntries(
                    Array.from(field.querySelectorAll("input[data-date-part]")).map((input) => [
                        input.dataset.datePart,
                        input.value,
                    ]),
                );
                return !Alpine.$data(field).commitParts(parts);
            });
            if (invalidDate) {
                this.error = "تاریخ و ساعت را کامل و معتبر وارد کنید.";
                return;
            }
            this.busy = true;
            this.errors = {};
            this.error = "";
            const payload = {};
            for (const field of config.fields) {
                if (!Object.hasOwn(this.values, field.key)) continue;
                let value = this.values[field.key];
                if (
                    this.record &&
                    JSON.stringify(value) ===
                        JSON.stringify(this.baseline[field.key])
                )
                    continue;
                if (field.type === "password" && !value) continue;
                if (
                    !this.record &&
                    (value === "" || value === null) &&
                    !field.required
                )
                    continue;
                if (["date", "datetime", "ref", "media"].includes(field.type))
                    value =
                        value === "" || value === null
                            ? null
                            : ["ref", "media"].includes(field.type)
                              ? Number(value)
                              : value;
                if (field.type === "number") value = Number(value);
                if (field.type === "multi") value = value.map(Number);
                payload[field.key] = value;
            }
            if (resource === "posts" && this.record)
                payload.lock_version = this.record.lock_version;
            if (resource === "menu-items") {
                payload.location = this.values.location;
                payload.display_mode = this.values.display_mode;
                payload.parent_id = this.values.parent_id
                    ? Number(this.values.parent_id)
                    : null;
            }
            if (resource === "tariffs" && payload.rules) {
                payload.rules = clone(payload.rules);
                payload.rules.maximum_fee =
                    payload.rules.maximum_fee === "" ||
                    payload.rules.maximum_fee === null
                        ? null
                        : Number(payload.rules.maximum_fee);
                payload.rules.tiers = payload.rules.tiers.map((t) => ({
                    up_to:
                        t.up_to === "" || t.up_to === null
                            ? null
                            : Number(t.up_to),
                    rate_bps: Number(t.rate_bps),
                }));
            }
            if (payload.status_code)
                payload.status_code = Number(payload.status_code);
            const translated = Object.fromEntries(
                Object.entries(this.english).filter(
                    ([key, value]) => value !== this.englishBaseline[key],
                ),
            );
            if (Object.keys(translated).length)
                payload.translations = { en: translated };
            try {
                const r = await api(
                    "/api/v1/admin/" +
                        resource +
                        (this.record ? "/" + this.record.id : ""),
                    {
                        method: this.record ? "PATCH" : "POST",
                        body: JSON.stringify(payload),
                    },
                );
                this.record = r.data;
                this.baseline = clone(this.values);
                this.englishBaseline = clone(this.english);
                notify("تغییرات ذخیره شد.");
                if (preview && resource === "pages")
                    location.assign("/admin/pages/" + r.data.id + "/preview");
                else if (resource === "albums" || resource === "pages")
                    history.replaceState(
                        null,
                        "",
                        "/admin/" + resource + "/" + r.data.id,
                    );
                else location.assign("/admin/" + resource);
            } catch (e) {
                this.error = e.message;
                this.errors = e.errors || {};
                if (e.status === 409)
                    this.error +=
                        " نسخه مطلب تغییر کرده؛ قبل از ادامه صفحه را تازه کنید.";
            } finally {
                this.busy = false;
            }
        },
        preview() {
            this.save(true);
        },
        async copyPage() {
            if (this.record)
                try {
                    await navigator.clipboard.writeText(
                        new URL(
                            publicPath("pages", this.record),
                            location.origin,
                        ).href,
                    );
                    notify("لینک کپی شد.");
                } catch {
                    notify("کپی لینک انجام نشد.", "error");
                }
        },
        async uploadAlbum(event) {
            const files = Array.from(event.target.files);
            event.target.value = "";
            this.uploads++;
            try {
                for (const file of files) {
                    const body = new FormData();
                    body.set("file", file);
                    body.set("alt", file.name);
                    const r = await api("/api/v1/admin/media", {
                        method: "POST",
                        body,
                    });
                    this.albumItems.push({
                        media: r.data,
                        caption: "",
                        caption_en: "",
                        key: crypto.randomUUID(),
                    });
                }
            } catch (e) {
                this.error = e.message;
            } finally {
                this.uploads--;
            }
        },
        moveAlbum(index, delta) {
            const next = index + delta;
            if (next < 0 || next >= this.albumItems.length) return;
            const [item] = this.albumItems.splice(index, 1);
            this.albumItems.splice(next, 0, item);
        },
        async saveAlbum() {
            if (!this.record || this.uploads) return;
            try {
                await api("/api/v1/admin/albums/" + this.record.id + "/items", {
                    method: "PUT",
                    body: JSON.stringify({
                        items: this.albumItems.map((item, index) => ({
                            media_id: item.media.id,
                            caption: item.caption,
                            caption_en: item.caption_en,
                            sort_order: index,
                        })),
                    }),
                });
                notify("تصاویر آلبوم ذخیره شد.");
            } catch (e) {
                this.error = e.message;
            }
        },
    }));
    Alpine.data("mediaLibrary", () => ({
        items: [],
        pagination: { total: 0 },
        page: 1,
        busy: false,
        error: "",
        mediaUrl,
        formatNumber,
        async init() {
            await this.load();
        },
        async load() {
            try {
                const r = await api(
                    "/api/v1/admin/media?page=" + this.page + "&per_page=18",
                );
                this.items = r.data;
                this.pagination = r.pagination || {
                    total: r.data.length,
                    last_page: 1,
                };
            } catch (e) {
                this.error = e.message;
            }
        },
        async upload(event) {
            this.busy = true;
            this.error = "";
            try {
                await api("/api/v1/admin/media", {
                    method: "POST",
                    body: new FormData(event.target),
                });
                event.target.reset();
                this.page = 1;
                await this.load();
                notify("فایل بارگذاری شد.");
            } catch (e) {
                this.error =
                    Object.values(e.errors || {})
                        .flat()
                        .join(" ") || e.message;
            } finally {
                this.busy = false;
            }
        },
        async remove(item) {
            if (!confirm("این فایل حذف شود؟")) return;
            try {
                await api("/api/v1/admin/media/" + item.id, {
                    method: "DELETE",
                });
                await this.load();
            } catch (e) {
                this.error = e.message;
            }
        },
    }));
    Alpine.data("settingsForm", () => ({
        values: {},
        errors: {},
        references: {media:[]},
        selectedMedia: {},
        uploads: 0,
        mediaUrl,
        baseline: {},
        busy: false,
        error: "",
        async init() {
            try {
                const r = await api("/api/v1/admin/settings");
                this.values = r.data;
                this.baseline = clone(r.data);
                let page=1,last=1,pool=[];
                do {const media=await api("/api/v1/admin/media?per_page=50&page="+page);pool.push(...media.data);last=media.pagination?.last_page||1;page++;} while(page<=last);
                this.references.media=pool;
                for(const key of ["logo_media_id","favicon_media_id"]) this.selectedMedia[key]=pool.find(item=>String(item.id)===String(this.values[key]))||null;
            } catch (e) {
                this.error = e.message;
            }
        },
        async uploadField(event,key) {
            const file=event.target.files?.[0];event.target.value="";if(!file||this.uploads)return;this.uploads++;
            try {const body=new FormData();body.set("file",file);body.set("alt",file.name);const {data}=await api("/api/v1/admin/media",{method:"POST",body});this.values[key]=data.id;this.selectedMedia[key]=data;this.references.media.unshift(data);} catch(error) {this.error=error.message;} finally {this.uploads--;}
        },
        async save() {
            if(this.busy||this.uploads)return;
            this.error="";this.errors={};
            this.busy = true;
            try {
                const values = Object.fromEntries(
                    Object.entries(this.values)
                        .filter(([k, v]) => v !== this.baseline[k])
                        .map(([k, v]) => [
                            k,
                            v === ""
                                ? null
                                : k.endsWith("_media_id") ||
                                    k.startsWith("map_")
                                  ? Number(v)
                                  : v,
                        ]),
                );
                await api("/api/v1/admin/settings", {
                    method: "PUT",
                    body: JSON.stringify({ settings: values }),
                });
                this.baseline = clone(this.values);
                notify("تنظیمات ذخیره شد.");
            } catch (e) {
                this.errors=e.errors||{};
                this.error =
                    Object.values(e.errors || {})
                        .flat()
                        .join(" ") || e.message;
            } finally {
                this.busy = false;
            }
        },
    }));
    Alpine.data("passwordForm", () => ({
        current: "",
        password: "",
        confirmation: "",
        busy: false,
        error: "",
        async save() {
            this.busy = true;
            this.error = "";
            try {
                await api("/api/v1/auth/password", {
                    method: "PUT",
                    body: JSON.stringify({
                        current_password: this.current,
                        password: this.password,
                        password_confirmation: this.confirmation,
                    }),
                });
                location.assign("/admin/login");
            } catch (e) {
                this.error =
                    Object.values(e.errors || {})
                        .flat()
                        .join(" ") || e.message;
            } finally {
                this.busy = false;
            }
        },
    }));
    Alpine.data("notifications", () => ({
        open: false,
        formatNumber,
        formatDate,
        items: [],
        unread: 0,
        timer: null,
        async init() {
            await this.load();
            this.timer = setInterval(() => {
                if (!document.hidden) this.load();
            }, 60000);
        },
        destroy() {
            clearInterval(this.timer);
        },
        async load() {
            try {
                const r = await api("/api/v1/admin/notifications", { background: true });
                this.items = r.data.items || r.data.notifications || [];
                this.unread = r.data.unread_count || r.data.unread || 0;
            } catch {}
        },
        async read(entry) {
            try {
                await api("/api/v1/admin/notifications/" + entry.id + "/read", {
                    method: "PATCH",
                });
                location.assign(safeHref(entry.url) || "/admin");
            } catch (e) {
                notify(e.message, "error");
            }
        },
        async readAll() {
            try {
                await api("/api/v1/admin/notifications/read-all", {
                    method: "PATCH",
                });
                await this.load();
            } catch (e) {
                notify(e.message, "error");
            }
        },
    }));
    Alpine.data("revisions", (id) => ({
        items: [],
        formatDate,
        init() { this.load(); },
        async load() {
            try {
                this.items = (
                    await api("/api/v1/admin/posts/" + id + "/revisions")
                ).data;
            } catch (e) {
                notify(e.message, "error");
            }
        },
    }));
}

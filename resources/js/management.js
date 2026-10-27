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
        trash: false,
        error: "",
        loading: false,
        audit: "",
        title,
        formatDate,
        publicPath: (item) => publicPath(resource, item),
        placement: (item) =>
            (item.location || "") +
            " · " +
            (item.parent_id ? "زیرمنو" : "لینک اصلی"),
        async init() {
            const p = new URLSearchParams(location.search);
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
                    ...(this.trash ? { trash: "1" } : {}),
                });
                const r = await api("/api/v1/admin/" + resource + "?" + q);
                this.items = r.data;
                this.pagination = r.pagination || {};
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
    Alpine.data("resourceForm", (resource, config, initial) => ({
        resource,
        config,
        record: initial,
        values: {},
        baseline: {},
        english: clone(initial?.translations?.en || {}),
        englishBaseline: clone(initial?.translations?.en || {}),
        references: {},
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
            this.baseline = clone(this.values);
            this.loadReferences();
            window.addEventListener("beforeunload", (event) => {
                if (
                    !this.busy &&
                    JSON.stringify(this.values) !==
                        JSON.stringify(this.baseline)
                ) {
                    event.preventDefault();
                    event.returnValue = "";
                }
            });
        },
        async loadReferences() {
            const sources = [
                ...new Set(
                    config.fields
                        .filter((f) => f.source || f.type === "media")
                        .map((f) => f.source || "media"),
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
                                "/api/v1/admin/" +
                                    source +
                                    "?per_page=50&page=" +
                                    page,
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
            );
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
            if (
                !this.record &&
                ["title", "name"].includes(key) &&
                "slug" in this.values
            ) {
                const slug = (s) =>
                    String(s || "")
                        .normalize("NFKC")
                        .trim()
                        .toLowerCase()
                        .replace(/[^\p{L}\p{N}]+/gu, "-")
                        .replace(/^-|-$/g, "")
                        .slice(0, 180);
                if (
                    !this.values.slug ||
                    this.values.slug === slug(this.baseline[key])
                )
                    this.values.slug = slug(this.values[key]);
            }
            if (
                !this.record &&
                resource === "pages" &&
                key === "title" &&
                !this.values.path
            )
                this.values.path = "/" + this.values.slug;
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
        baseline: {},
        busy: false,
        error: "",
        async init() {
            try {
                const r = await api("/api/v1/admin/settings");
                this.values = r.data;
                this.baseline = clone(r.data);
            } catch (e) {
                this.error = e.message;
            }
        },
        async save() {
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
                const r = await api("/api/v1/admin/notifications");
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

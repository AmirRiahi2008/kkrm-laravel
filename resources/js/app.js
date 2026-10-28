import Alpine from "alpinejs";
import focus from "@alpinejs/focus";
import jalaali from "jalaali-js";
import { registerManagement } from "./management.js";

export const title = (item) =>
    item?.title ||
    item?.name ||
    item?.question ||
    item?.subject ||
    item?.bank_name ||
    [item?.first_name, item?.last_name].filter(Boolean).join(" ") ||
    "";
export const mediaUrl = (item) =>
    item?.id ? "/api/v1/media/" + Number(item.id) : "";
export const digits = (value) =>
    String(value ?? "")
        .replace(/[۰-۹]/g, (c) => "۰۱۲۳۴۵۶۷۸۹".indexOf(c))
        .replace(/[٠-٩]/g, (c) => "٠١٢٣٤٥٦٧٨٩".indexOf(c));
export const formatDate = (value) => {
    if (!value) return "";
    const d = new Date(value);
    return isNaN(d)
        ? ""
        : new Intl.DateTimeFormat(document.documentElement.lang === "en" ? "en-GB" : "fa-IR-u-ca-persian", {
              timeZone: "Asia/Tehran", year:"numeric", month:"2-digit", day:"2-digit",
              ...(/^\d{4}-\d{2}-\d{2}$/.test(value) ? {} : {hour:"2-digit",minute:"2-digit",hourCycle:"h23"}),
          }).format(d);
};
export const formatNumber = (value) =>
    new Intl.NumberFormat(
        document.documentElement.lang === "en" ? "en" : "fa",
    ).format(value || 0);
export const safeHref = (value) => {
    if (typeof value !== "string" || /[\\\u0000-\u001f]/.test(value))
        return null;
    if (/^\/(?!\/)/.test(value)) return value;
    try {
        const u = new URL(value);
        return ["https:", "http:"].includes(u.protocol) &&
            !u.username &&
            !u.password
            ? u.href
            : null;
    } catch {
        return null;
    }
};
export function publicPath(resource, item) {
    if (resource === "posts")
        resource = item.type === "article" ? "articles" : "news";
    if (resource === "pages" && safeHref(item.public_path || item.path))
        return item.public_path || item.path;
    return [
        "news",
        "articles",
        "pages",
        "documents",
        "events",
        "albums",
        "board-members",
        "experts",
        "agreements",
        "expert-groups",
        "disciplines",
        "qualifications",
    ].includes(resource)
        ? "/" + resource + "/" + encodeURIComponent(item.slug || item.id)
        : "";
}
export async function api(path, options = {}) {
    if (!path.startsWith("/api/v1/") || /[\\\u0000-\u001f]/.test(path))
        throw new Error("مسیر درخواست معتبر نیست.");
    const { background = false, signal, ...requestOptions } = options;
    const controller = new AbortController();
    const abort = () => controller.abort();
    let timedOut = false;
    if (signal?.aborted) abort();
    else signal?.addEventListener("abort", abort, { once: true });
    const timer = setTimeout(() => {
        timedOut = true;
        controller.abort();
    }, 45000);
    if (!background) Alpine.store("loading").count++;
    try {
        const headers = new Headers(requestOptions.headers);
        headers.set("Accept", "application/json");
        headers.set("X-Requested-With", "XMLHttpRequest");
        headers.set(
            "X-CSRF-TOKEN",
            document.querySelector('meta[name="csrf-token"]').content,
        );
        if (requestOptions.body && !(requestOptions.body instanceof FormData))
            headers.set("Content-Type", "application/json");
        const response = await fetch(path, {
            ...requestOptions,
            headers,
            signal: controller.signal,
            credentials: "same-origin",
            cache: "no-store",
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.message || "درخواست انجام نشد.");
            error.errors = data.errors || {};
            error.status = response.status;
            if (
                response.status === 401 &&
                location.pathname.startsWith("/admin") &&
                !location.pathname.startsWith("/admin/login")
            )
                location.assign("/admin/login");
            throw error;
        }
        if (data.success !== true || !("data" in data))
            throw new Error("پاسخ سرور معتبر نیست.");
        return data;
    } catch (error) {
        if (timedOut)
            throw new Error("پاسخ سرور بیش از حد طول کشید. دوباره تلاش کنید.");
        throw error;
    } finally {
        clearTimeout(timer);
        signal?.removeEventListener("abort", abort);
        if (!background)
            Alpine.store("loading").count = Math.max(
                0,
                Alpine.store("loading").count - 1,
            );
    }
}
export function notify(message, kind = "success") {
    Alpine.store("toasts").add(message, kind);
}
let installation = null;
window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    installation = event;
});
Alpine.plugin(focus);
Alpine.store("loading", { count: 0 });
Alpine.store("theme", {
    dark: document.documentElement.dataset.theme === "dark",
    toggle() {
        this.dark = !this.dark;
        document.documentElement.dataset.theme = this.dark ? "dark" : "light";
        try {
            localStorage.setItem("kkrm-theme", this.dark ? "dark" : "light");
        } catch {}
    },
});
Alpine.store("toasts", {
    items: [],
    add(message, kind) {
        const id = crypto.randomUUID();
        this.items.push({ id, message, kind });
        setTimeout(() => this.remove(id), 6000);
    },
    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});
Alpine.data("appShell", () => ({
    galleryImages: [],
    galleryIndex: 0,
    galleryTitle: "",
    galleryZoom: false,
    galleryFailed: false,
    formatNumber,
    formatDate,
    init() {
        window.addEventListener(
            "pageshow",
            () => (Alpine.store("loading").count = 0),
        );
        document.addEventListener("submit", (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            const handledByAlpine = form.getAttributeNames().some(
                (name) =>
                    /^(?:@|x-on:)submit(?:\.|$)/.test(name) &&
                    name.split(".").includes("prevent"),
            );
            if (
                event.defaultPrevented ||
                form.method.toLowerCase() !== "get" ||
                handledByAlpine
            ) return;
            queueMicrotask(() => {
                if (!event.defaultPrevented)
                    Alpine.store("loading").count++;
            });
        });
    },
    async logout() {
        try {
            await api("/api/v1/auth/logout", { method: "POST" });
            location.assign("/admin/login");
        } catch (e) {
            notify(e.message, "error");
        }
    },
    openAlbum(items, index) {
        this.galleryImages = items
            .filter((i) => i.media)
            .map((i) => ({ src: mediaUrl(i.media), alt: i.caption || "" }));
        this.galleryIndex = Math.min(index, this.galleryImages.length - 1);
        if (this.galleryImages.length) {
            this.galleryZoom = false;
            this.$refs.albumViewer.showModal();
            document.body.style.overflow = "hidden";
        }
    },
    richImage(event) {
        const image = event.target.closest("img");
        if (!image) return;
        const group =
            image.closest("figure[data-kkr-gallery]") ||
            image.closest(".rich-content");
        if (!group) return;
        event.preventDefault();
        const images = Array.from(group.querySelectorAll("img"));
        this.galleryImages = images.map((i) => ({
            src: i.currentSrc || i.src,
            alt: i.alt,
        }));
        this.galleryIndex = images.indexOf(image);
        this.galleryTitle = group.querySelector("figcaption")?.textContent || "آلبوم تصاویر";
        this.galleryFailed = false;
        this.$refs.lightbox.showModal();
        document.body.style.overflow = "hidden";
    },
    moveGallery(delta) {
        this.galleryZoom = false;
        this.galleryFailed = false;
        if (this.galleryImages.length)
            this.galleryIndex =
                (this.galleryIndex + delta + this.galleryImages.length) %
                this.galleryImages.length;
    },
    closeGallery() {
        this.$refs.lightbox.close();
        this.$refs.albumViewer.close();
        this.galleryZoom = false;
        document.body.style.overflow = "";
    },
    async share(text) {
        try {
            if (navigator.share)
                await navigator.share({ title: text, url: location.href });
            else {
                await navigator.clipboard.writeText(location.href);
                notify("لینک کپی شد.");
            }
        } catch (e) {
            if (e.name !== "AbortError")
                notify("اشتراک‌گذاری انجام نشد.", "error");
        }
    },
}));
Alpine.data("carousel", (count) => ({
    index: 0,
    paused: false,
    hovered: false,
    focused: false,
    touchX: 0,
    timer: null,
    init() {
        this.timer = setInterval(() => {
            if (
                !this.paused &&
                !this.hovered &&
                !this.focused &&
                !document.hidden &&
                !matchMedia("(prefers-reduced-motion:reduce)").matches
            )
                this.move(1);
        }, 6000);
    },
    move(delta) {
        if (count) this.index = (this.index + delta + count) % count;
    },
    destroy() {
        clearInterval(this.timer);
    },
}));
Alpine.data("pointerCard", () => ({
    move(event) {
        if (
            event.pointerType !== "mouse" ||
            matchMedia("(prefers-reduced-motion:reduce)").matches
        )
            return;
        const r = this.$el.getBoundingClientRect(),
            x = (event.clientX - r.left) / r.width,
            y = (event.clientY - r.top) / r.height;
        this.$el.style.setProperty("--pointer-x", x * 100 + "%");
        this.$el.style.setProperty("--pointer-y", y * 100 + "%");
        this.$el.style.transform = `perspective(700px) rotateX(${(0.5 - y) * 7}deg) rotateY(${(x - 0.5) * 7}deg) translateY(-3px)`;
    },
    reset() {
        this.$el.style.transform = "";
    },
}));
Alpine.data("expertFilters", (data) => ({
    group: new URLSearchParams(location.search).get("expert_group_id") || "",
    discipline: new URLSearchParams(location.search).get("discipline_id") || "",
    qualification:
        new URLSearchParams(location.search).get("qualification_id") || "",
    loading: false,
    error: "",
    disciplines: data.disciplines || [],
    qualifications: data.qualifications || [],
    async refresh() {
        this.loading = true;
        this.error = "";
        this.discipline = "";
        this.qualification = "";
        try {
            const r = await api(
                "/api/v1/experts/filters" +
                    (this.group ? "?expert_group_id=" + this.group : ""),
            );
            this.disciplines = r.data.disciplines;
            this.qualifications = [];
        } catch (e) {
            this.error = e.message;
        } finally { this.loading = false; }
    },
    async refreshQualifications() {
        this.error = "";
        this.qualification = "";
        if (!this.discipline) {
            this.qualifications = [];
            return;
        }
        this.loading = true;
        try {
            const r = await api(
                "/api/v1/experts/filters?discipline_id=" +
                    this.discipline +
                    (this.group ? "&expert_group_id=" + this.group : ""),
            );
            this.qualifications = r.data.qualifications;
        } catch (e) {
            this.error = e.message;
        } finally { this.loading = false; }
    },
}));
Alpine.data("contentTabs", () => ({
    index: 1,
    select(index, focus = false) {
        this.index = index;
        const tab = this.$el.querySelectorAll('.hub-tab')[index];
        if (focus) tab?.focus({preventScroll: true});
        tab?.scrollIntoView({block: 'nearest', inline: 'nearest'});
    },
    key(event, index) {
        const direction = document.documentElement.dir === 'ltr' ? 1 : -1;
        let next;
        if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = 4;
        else if (event.key === 'ArrowRight') next = index + direction;
        else if (event.key === 'ArrowLeft') next = index - direction;
        else return;
        event.preventDefault();
        this.select((next + 5) % 5, true);
    }
}));
Alpine.data("jalaliField", (key, withTime) => ({
    opened: false,
    year: 1405,
    mon: 1,
    parts: { y: '', m: '', d: '', h: '', i: '' },
    weeks: [],
    dateError: '',
    formatNumber,
    digits,
    local(value) { return document.documentElement.lang === "fa" ? String(value).replace(/[0-9]/g,digit=>"۰۱۲۳۴۵۶۷۸۹"[digit]) : String(value); },
    init() {
        const today = jalaali.toJalaali(new Date());
        this.year = today.jy;
        this.mon = today.jm;
        this.updateFromValue();
        this.$watch('values.'+key, () => this.updateFromValue());
        this.render();
    },
    updateFromValue() {
        const value = this.values[key];
        if (!value) {
            this.parts = { y: '', m: '', d: '', h: '', i: '' };
            return;
        }
        const date = new Date(value);
        if (isNaN(date)) return;
        const parts = new Intl.DateTimeFormat('en-US-u-ca-persian', {
            timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
        }).formatToParts(date);
        const values = Object.fromEntries(parts.map(part=>[part.type,part.value]));
        this.parts = { y: this.local(values.year), m: this.local(values.month), d: this.local(values.day), h: this.local(values.hour), i: this.local(values.minute) };
        this.year = Number(values.year);
        this.mon = Number(values.month);
        this.render();
    },
    commitParts(input = this.parts) {
        this.parts = { ...this.parts, ...input };
        this.dateError = '';
        const p = Object.fromEntries(Object.entries(this.parts).map(([key,value])=>[key,digits(value)]));
        if (!p.y && !p.m && !p.d && (!withTime || (!p.h && !p.i))) {
            this.values[key] = null;
            return true;
        }
        if (!p.y || !p.m || !p.d || !jalaali.isValidJalaaliDate(+p.y,+p.m,+p.d) ||
            (withTime && (!/^\d{1,2}$/.test(p.h) || !/^\d{1,2}$/.test(p.i) || +p.h>23 || +p.i>59))) {
            this.dateError = 'تاریخ و ساعت را کامل و معتبر وارد کنید.';
            return false;
        }
        const g = jalaali.toGregorian(+p.y,+p.m,+p.d);
        const date = [g.gy,String(g.gm).padStart(2,'0'),String(g.gd).padStart(2,'0')].join('-');
        this.values[key] = withTime ? new Date(date+'T'+p.h.padStart(2,'0')+':'+p.i.padStart(2,'0')+':00+03:30').toISOString() : date;
        this.year = +p.y;
        this.mon = +p.m;
        this.render();
        return true;
    },
    toggleCalendar() { this.opened = !this.opened; this.render(); },
    render() {
        const g = jalaali.toGregorian(this.year,this.mon,1);
        const offset = (new Date(Date.UTC(g.gy,g.gm-1,g.gd)).getUTCDay()+1)%7;
        const days = Array(offset).fill(null).concat(Array.from({length:jalaali.jalaaliMonthLength(this.year,this.mon)},(_,i)=>i+1));
        while (days.length%7) days.push(null);
        this.weeks = Array.from({length:days.length/7},(_,i)=>days.slice(i*7,i*7+7));
    },
    month(delta) {
        this.mon += delta;
        if (this.mon>12) { this.mon=1;this.year++; }
        if (this.mon<1) { this.mon=12;this.year--; }
        this.render();
    },
    monthLabel() {
        return ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'][this.mon-1]+' '+formatNumber(this.year).replace(/[,٬]/g,'');
    },
    select(day) {
        this.parts = {...this.parts,y:String(this.year),m:String(this.mon).padStart(2,'0'),d:String(day).padStart(2,'0'),h:this.parts.h||'00',i:this.parts.i||'00'};
        this.commitParts();
        this.opened = false;
    },
    today() {
        const now = new Date();
        const j = jalaali.toJalaali(now);
        this.year=j.jy; this.mon=j.jm;
        const time = new Intl.DateTimeFormat('en-GB',{timeZone:'Asia/Tehran',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).format(now).split(':');
        this.parts.h=time[0];this.parts.i=time[1];
        this.select(j.jd);
    },
    step(part, delta) {
        const limits = {y:[1200,1600],m:[1,12],d:[1,31],h:[0,23],i:[0,59]};
        const [min,max]=limits[part];
        this.parts[part]=String(Math.min(max,Math.max(min,Number(digits(this.parts[part])||min)+delta))).padStart(part==='y'?4:2,'0');
        this.commitParts();
    },
    clear() { this.values[key]=null; this.parts={y:'',m:'',d:'',h:'',i:''};this.opened=false;this.dateError=''; }
}));
Alpine.data("authForm", (mode) => ({
    show: false,
    email: new URLSearchParams(location.search).get("email") || "",
    password: "",
    confirmation: "",
    busy: false,
    error: "",
    message: "",
    async submit() {
        if (this.busy) return;
        this.busy = true;
        this.error = "";
        try {
            const payload = {
                email: this.email,
                password: this.password,
                password_confirmation: this.confirmation,
                token: new URLSearchParams(location.search).get("token") || "",
            };
            if (mode === "login") {
                delete payload.password_confirmation;
                delete payload.token;
            }
            if (mode === "forgot-password") {
                delete payload.password;
                delete payload.password_confirmation;
                delete payload.token;
            }
            const r = await api("/api/v1/auth/" + mode, {
                method: "POST",
                body: JSON.stringify(payload),
            });
            if (mode === "login") location.assign("/admin");
            else if (mode === "reset-password") location.assign("/admin/login");
            else this.message = r.data.message;
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
Alpine.data("contactForm", () => ({
    busy: false,
    error: "",
    message: "",
    async submit(event) {
        this.busy = true;
        this.error = "";
        try {
            const body = Object.fromEntries(new FormData(event.target));
            delete body.consent;
            const r = await api("/api/v1/contact", {
                method: "POST",
                body: JSON.stringify(body),
            });
            this.message = r.data.message || "پیام شما ثبت شد.";
            event.target.reset();
            notify(this.message);
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
Alpine.data("feeCalculator", (versions = []) => ({
    get currentTariff() { return versions.find((entry) => entry.id === this.result?.tariff_id) || null; },
    safeHref,
    digits,
    amount: "",
    unit: "IRR",
    count: 1,
    tariff: "",
    values: {
        date: new Intl.DateTimeFormat("en-CA", {
            timeZone: "Asia/Tehran",
        }).format(new Date()),
    },
    busy: false,
    error: "",
    result: null,
    formatNumber,
    reset() {
        this.amount = "";
        this.unit = "IRR";
        this.count = 1;
        this.tariff = "";
        this.values.date = new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Tehran" }).format(new Date());
        this.result = null;
        this.error = "";
    },
    async copyResult() {
        if (!this.result) return;
        try {
            await navigator.clipboard.writeText(this.result.tariff_name + "\n" + this.result.total + " IRR");
            notify("نتیجه کپی شد.");
        } catch {
            this.error = "کپی نتیجه انجام نشد.";
        }
    },
    async calculate() {
        if(this.busy) return;
        this.error = "";
        this.result = null;
        const clean = digits(this.amount).replace(/[,٬\s]/g, "");
        if (!/^\d{1,16}$/.test(clean)) {
            this.error = "مبلغ معتبر وارد کنید.";
            return;
        }
        this.busy = true;
        try {
            const body = {
                value: Number(clean) * (this.unit === "toman" ? 10 : 1),
                expert_count: Number(this.count),
                date: this.values.date,
                ...(this.tariff ? { tariff_id: Number(this.tariff) } : {}),
            };
            const r = await api("/api/v1/tariffs/calculate", {
                method: "POST",
                body: JSON.stringify(body),
            });
            this.result = r.data;
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
Alpine.data("downloadApp", () => ({
    hint: "",
    platform: /iPhone|iPad/.test(navigator.userAgent)?"ios":/Android/.test(navigator.userAgent)?"android":"desktop",
    canInstall: !!installation,
    init() { window.addEventListener("beforeinstallprompt",()=>{this.canInstall=true}); },
    platformKey(event,index) { const keys=["android","ios","desktop"];let next;if(event.key==="Home")next=0;else if(event.key==="End")next=2;else if(event.key==="ArrowRight")next=index+(document.documentElement.dir==="ltr"?1:-1);else if(event.key==="ArrowLeft")next=index+(document.documentElement.dir==="ltr"?-1:1);else return;event.preventDefault();this.platform=keys[(next+3)%3];document.getElementById("install-tab-"+this.platform)?.focus(); },
    installed: matchMedia("(display-mode:standalone)").matches,
    async install() {
        if(this.installed) { location.assign("/?source=pwa");return; }
        if (installation) {
            await installation.prompt();
            const r = await installation.userChoice;
            if (r.outcome === "accepted") this.installed = true;
            installation = null;
        } else {
            this.hint = /iPhone|iPad/.test(navigator.userAgent) ? "در Safari از منوی Share گزینه Add to Home Screen را بزنید." : "از منوی مرورگر گزینه نصب برنامه یا افزودن به صفحه اصلی را انتخاب کنید.";
            document.getElementById("installation-guide")?.scrollIntoView({behavior:matchMedia("(prefers-reduced-motion: reduce)").matches?"auto":"smooth",block:"start"});
        }
    },
}));
registerManagement(Alpine, {
    api,
    title,
    mediaUrl,
    formatDate,
    formatNumber,
    digits,
    publicPath,
    notify,
    safeHref,
});
window.Alpine = Alpine;
async function boot() {
    if (document.querySelector("[data-rich-editor]")) {
        const { registerEditor } = await import("./editor.js");
        registerEditor(Alpine, { api, mediaUrl, notify, safeHref });
    }
    Alpine.start();
}
boot();
if (
    "serviceWorker" in navigator &&
    location.protocol === "https:" &&
    !location.pathname.startsWith("/admin")
)
    navigator.serviceWorker.register("/sw.js").catch(() => {});

import Alpine from "alpinejs";
import focus from "@alpinejs/focus";
import {
    createIcons,
    House,
    Phone,
    Network,
    ArrowUpRight,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Search,
    CalendarDays,
    Menu,
    LayoutGrid,
    Smartphone,
    Calculator,
    SearchCheck,
    BookOpen,
    Users,
    Building2,
    Scale,
    Link,
    Sun,
    Moon,
    ShieldCheck,
    ImageOff,
    Plus,
    Save,
    FileText,
    FilePlus,
    Files,
    Images,
    Upload,
    ExternalLink,
    LayoutDashboard,
    Bell,
    LogOut,
    MessageCircle,
    CircleHelp,
    Clock,
    Mail,
    MapPin,
    Share2,
} from "lucide";
const icons = {
    House,
    Phone,
    Network,
    ArrowUpRight,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Search,
    CalendarDays,
    Menu,
    LayoutGrid,
    Smartphone,
    Calculator,
    SearchCheck,
    BookOpen,
    Users,
    Building2,
    Scale,
    Link,
    Sun,
    Moon,
    ShieldCheck,
    ImageOff,
    Plus,
    Save,
    FileText,
    FilePlus,
    Files,
    Images,
    Upload,
    ExternalLink,
    LayoutDashboard,
    Bell,
    LogOut,
    MessageCircle,
    CircleHelp,
    Clock,
    Mail,
    MapPin,
    Share2,
};
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
        : new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
              timeZone: "Asia/Tehran",
              dateStyle: "medium",
              timeStyle: "short",
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
    Alpine.store("loading").count++;
    try {
        const headers = new Headers(options.headers);
        headers.set("Accept", "application/json");
        headers.set("X-Requested-With", "XMLHttpRequest");
        headers.set(
            "X-CSRF-TOKEN",
            document.querySelector('meta[name="csrf-token"]').content,
        );
        if (options.body && !(options.body instanceof FormData))
            headers.set("Content-Type", "application/json");
        const response = await fetch(path, {
            ...options,
            headers,
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
    } finally {
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
    init() {
        createIcons({ icons });
        window.addEventListener(
            "pageshow",
            () => (Alpine.store("loading").count = 0),
        );
        document.addEventListener("submit", (event) => {
            if (
                !event.defaultPrevented &&
                event.target.method?.toLowerCase() === "get"
            )
                Alpine.store("loading").count++;
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
            this.$refs.lightbox.showModal();
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
        this.$refs.lightbox.showModal();
        document.body.style.overflow = "hidden";
    },
    moveGallery(delta) {
        if (this.galleryImages.length)
            this.galleryIndex =
                (this.galleryIndex + delta + this.galleryImages.length) %
                this.galleryImages.length;
    },
    closeGallery() {
        this.$refs.lightbox.close();
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
    disciplines: data.disciplines || [],
    qualifications: data.qualifications || [],
    async refresh() {
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
            notify(e.message, "error");
        }
    },
    async refreshQualifications() {
        this.qualification = "";
        if (!this.discipline) {
            this.qualifications = [];
            return;
        }
        try {
            const r = await api(
                "/api/v1/experts/filters?discipline_id=" +
                    this.discipline +
                    (this.group ? "&expert_group_id=" + this.group : ""),
            );
            this.qualifications = r.data.qualifications;
        } catch (e) {
            notify(e.message, "error");
        }
    },
}));
Alpine.data("jalaliField", (key, withTime) => ({
    display: "",
    opened: false,
    year: 1405,
    mon: 1,
    days: [],
    init() {
        this.updateFromValue();
        this.$watch("values." + key, () => this.updateFromValue());
        const now = jalaali.toJalaali(new Date());
        this.year = now.jy;
        this.mon = now.jm;
        this.render();
    },
    updateFromValue() {
        const value = this.values[key];
        if (!value) {
            this.display = "";
            return;
        }
        const d = new Date(value);
        if (isNaN(d)) return;
        const parts = new Intl.DateTimeFormat("en-US-u-ca-persian", {
            timeZone: "Asia/Tehran",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            hourCycle: "h23",
        }).formatToParts(d);
        const p = Object.fromEntries(parts.map((i) => [i.type, i.value]));
        this.display =
            p.year +
            "/" +
            p.month +
            "/" +
            p.day +
            (withTime ? " " + p.hour + ":" + p.minute : "");
    },
    commit() {
        const raw = digits(this.display).trim();
        if (!raw) {
            this.values[key] = null;
            return;
        }
        const m = raw.match(
            /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$/,
        );
        if (
            !m ||
            !jalaali.isValidJalaaliDate(+m[1], +m[2], +m[3]) ||
            +(m[4] || 0) > 23 ||
            +(m[5] || 0) > 59
        ) {
            notify("تاریخ شمسی معتبر وارد کنید؛ مانند ۱۴۰۵/۰۸/۰۴", "error");
            this.values[key] = null;
            return;
        }
        const g = jalaali.toGregorian(+m[1], +m[2], +m[3]);
        const date = [
            g.gy,
            String(g.gm).padStart(2, "0"),
            String(g.gd).padStart(2, "0"),
        ].join("-");
        this.values[key] = withTime
            ? new Date(
                  date +
                      "T" +
                      (m[4] || "00").padStart(2, "0") +
                      ":" +
                      (m[5] || "00") +
                      ":00+03:30",
              ).toISOString()
            : date;
    },
    render() {
        this.days = Array.from(
            { length: jalaali.jalaaliMonthLength(this.year, this.mon) },
            (_, i) => i + 1,
        );
    },
    month(delta) {
        this.mon += delta;
        if (this.mon > 12) {
            this.mon = 1;
            this.year++;
        }
        if (this.mon < 1) {
            this.mon = 12;
            this.year--;
        }
        this.render();
    },
    select(day) {
        this.display =
            this.year +
            "/" +
            String(this.mon).padStart(2, "0") +
            "/" +
            String(day).padStart(2, "0") +
            (withTime ? " 09:00" : "");
        this.commit();
        this.opened = false;
    },
    clear() {
        this.display = "";
        this.values[key] = null;
        this.opened = false;
    },
}));
Alpine.data("authForm", (mode) => ({
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
Alpine.data("feeCalculator", () => ({
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
    async calculate() {
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
    installed: matchMedia("(display-mode:standalone)").matches,
    async install() {
        if (installation) {
            await installation.prompt();
            const r = await installation.userChoice;
            if (r.outcome === "accepted") this.installed = true;
            installation = null;
        } else
            this.hint = /iPhone|iPad/.test(navigator.userAgent)
                ? "در Safari از منوی Share گزینه Add to Home Screen را بزنید."
                : "از منوی مرورگر گزینه نصب برنامه یا افزودن به صفحه اصلی را انتخاب کنید.";
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

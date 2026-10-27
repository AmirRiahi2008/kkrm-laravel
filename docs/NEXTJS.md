# اتصال به Next.js

کلاینت نمونه [examples/api-client.ts](examples/api-client.ts) را در پروژه Next.js قرار دهید. این فایل در بسته صرفاً نمونه مستندات است؛ فرانت موجود تغییر نکرده است.

متغیر محیطی فرانت:

```dotenv
NEXT_PUBLIC_API_URL=http://localhost:8000
```

با fetch مرورگر، ورود و عملیات نوشتن را از همان کلاینت انجام دهید:

```typescript
import { apiFetch, login, uploadMedia } from "@/lib/api-client";

await login("editor@example.test", "DemoEditor!2026");
const me = await apiFetch("/api/v1/auth/me");
const news = await apiFetch("/api/v1/posts?type=news&per_page=12");
const media = await uploadMedia(file, "تصویر خبر");

const draft = await apiFetch("/api/v1/admin/posts", {
  method: "POST",
  body: JSON.stringify({
    title: "خبر تازه",
    slug: "new-news",
    type: "news",
    body: "<p>متن خبر</p>",
    cover_id: media.data.id,
    status: "draft",
  }),
});
```

کوکی نشست HttpOnly است و به localStorage منتقل نمی‌شود. کلاینت قبل از درخواست نوشتن، CSRF را راه‌اندازی می‌کند. این الزام برای درخواست عمومی POST از مبدا مجاز، مثل فرم تماس و محاسبه تعرفه، هم برقرار است.

در 401 صفحه ورود نمایش دهید. در 403 پیام نبود مجوز؛ در 419 csrf-cookie را دوباره دریافت کنید و درخواست را مجدداً انجام دهید؛ در 409 نسخه خبر را بارگیری کنید تا کاربر تغییرات را ادغام کند؛ errors خطای 422 را کنار فیلدها نمایش دهید. فایل نمونه ApiError با status و errors دارد.

دکمه «ورود کارشناسان» از `links?group=systems` یا navigation به `https://my.kkrm.ir/` می‌رود. ورود داشبورد صفحه داخلی ورود مدیر/روابط عمومی است و endpoint auth/login را فراخوانی می‌کند.

## رندر عمومی روی سرور

برای home، اخبار، صفحات و sitemap در Server Component از fetch سرور استفاده کنید:

```typescript
const response = await fetch(
  process.env.API_URL + "/api/v1/home",
  {
    headers: { Accept: "application/json" },
    cache: "no-store",
  },
);
if (!response.ok) throw new Error("دریافت صفحه اول ناموفق بود.");
const { data } = await response.json();
```

`API_URL` متغیر سرور است. برای GET عمومی نیازی به کوکی نیست. برای درخواست خصوصی SSR فقط کوکی کاربر جاری و Origin معتبر خود فرانت را به API ثابت و متعلق به پروژه منتقل کنید؛ داده خصوصی را cache نکنید. فایل client نمونه برای مرورگر است و نباید مستقیم روی سرور اجرا شود.

تاریخ‌ها میلادی/UTC هستند. زمان نمایشی با Intl.DateTimeFormat و locale `fa-IR-u-ca-persian` و timeZone `Asia/Tehran` تبدیل شود. ورودی API را به تاریخ میلادی/ISO تبدیل کنید. مبلغ ریال است؛ در صورت نمایش تومان، تقسیم بر ۱۰ در فرانت انجام شود.

## دامنه و کوکی

برای local، هر دو localhost باشند؛ localhost و 127.0.0.1 را ترکیب نکنید.

برای دامنه واقعی، نمونه تنظیم‌ها:

```dotenv
APP_URL=https://api.kkrm.ir
FRONTEND_URL=https://kkrm.ir
CORS_ALLOWED_ORIGINS=https://kkrm.ir
SANCTUM_STATEFUL_DOMAINS=kkrm.ir,api.kkrm.ir
SESSION_DOMAIN=.kkrm.ir
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

دامنه front/API باید تحت دامنه اصلی مشترک باشند تا الگوی SPA Sanctum و خواندن XSRF-TOKEN درست کار کند. آدرس‌های بالا مثال استقرارند و در این تحویل منتشر نشده‌اند. در Vercel برای فرانت از دامنه سفارشی تحت همان دامنه استفاده کنید. خود Laravel روی PHP/FPM یا محیط Laravel جدا اجرا می‌شود.

با تغییر env، config:cache را بازسازی و worker را restart کنید. CORS wildcard برای ورود کوکی مناسب نیست. SESSION_DOMAIN باید دقیقاً با دامنه منتخب و زیردامنه‌های تحت کنترل شما هماهنگ باشد.

## Postman

docs/postman-collection.json را Import کنید. ابتدا CSRF-cookie و سپس Login را اجرا کنید. Cookie Jar فعال باشد. token کوکی به‌صورت خودکار از Set-Cookie در متغیر xsrf_token ثبت می‌شود. مقدار id/key و bodyها را براساس پاسخ‌های واقعی تغییر دهید؛ اجرای کل collection روی دیتابیس واقعی مناسب نیست چون درخواست‌های نوشتن دارد.

قرارداد تمام عملیات، فیلدهای ضروری و enumها در openapi.json است. شناسه‌های نمونه و payloadهای Postman برای شروع‌اند و تضمین نمی‌کنند تمام درخواست‌های CRUD بدون تغییر با هم اجرا شوند.

## آیکون جستجو

در این نسخه فرانت تغییر نکرده است. پس از کلیک آیکون و وارد کردن حداقل دو حرف، `GET /api/v1/search?q=...` را فراخوانی کنید. خروجی `data.results.news`، `data.results.announcement` و `data.results.resolution` دارد؛ هر گروه شامل `items`، `total`، `pagination` و `archive_path` است. برای صفحه بعد یک گروه، همان درخواست را با `type=news&page=2` و عبارت قبلی بفرستید. عبارت را با URLSearchParams کدگذاری کنید.

```typescript
const query = new URLSearchParams({ q: searchTerm });
const response = await apiFetch(`/api/v1/search?${query}`);
const groups = response.data.results;
```

ورود عمومی سایت به سامانه کارشناسان لینک می‌شود؛ این فرم یا دکمه نباید `auth/login` را فراخوانی کند. این endpoint فقط برای داشبورد داخلی است و هیچ endpoint ثبت‌نام وجود ندارد.

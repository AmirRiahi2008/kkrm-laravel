# پورتال کانون — Laravel Blade

این نسخه از سورس `Accelerator-number-one/kkrm` در commit `61cd03f99f881bd8f1d46fa7c79e2c5039e01408` تبدیل شده است. سایت و مدیریت در یک برنامهٔ Laravel با Blade، Tailwind CSS و Alpine.js اجرا می‌شوند. React و Next.js در این نسخه وابستگی اجرایی ندارند. APIهای قبلی حفظ شده‌اند؛ قالب‌های عمومی داده را مستقیماً از سرویس‌های Laravel می‌خوانند.

## پیش‌نیاز

PHP 8.3 یا جدیدتر مطابق composer.lock، Composer 2، MySQL 8 و افزونه‌های PDO MySQL، intl، mbstring، GD، fileinfo، DOM/XML، curl و zip. برای ساخت دارایی‌ها Node.js 22.12 یا جدیدتر سازگار با Vite لازم است؛ Node.js 22 یا 24 توصیه می‌شود. در سرور Node دائماً اجرا نمی‌شود.

## نصب محلی

راهنمای کامل کلون، تنظیم `.env`، بازیابی SQL، انتقال عکس‌ها و رفع خطاها در [docs/LOCAL.fa.md](docs/LOCAL.fa.md) است. سایت و پنل مدیریت هر دو روی `http://127.0.0.1:8000` اجرا می‌شوند؛ اجرای Next.js یا سرور جداگانهٔ فرانت لازم نیست.

```bash
git clone https://github.com/AmirRiahi2008/kkrm-laravel.git
cd kkrm-laravel
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
cp .env.example .env
composer install
php artisan key:generate
```

دیتابیس MySQL به نام `kkrm` بسازید و نام کاربری، رمز و پورت واقعی MySQL لوکال را در `.env` وارد کنید. `APP_URL` و `FRONTEND_URL` هر دو `http://127.0.0.1:8000` هستند. `PUBLIC_SITE_URL` و `ASSET_URL` برای لوکال خالی می‌مانند.

اگر بکاپ سایت فعلی را دارید، ابتدا **کل SQL را در دیتابیس خالی import کنید** و سپس دستورهای زیر را اجرا کنید. قبل از import، `migrate` اجرا نکنید. `db:seed`، `migrate:fresh` و `migrate:refresh` برای این مسیر نصب لازم نیستند. فایل‌های رسانهٔ هاست را نیز مطابق راهنمای محلی جداگانه کپی کنید.

```bash
php artisan optimize:clear
php artisan migrate
php artisan storage:link
npm ci
rm -f public/hot
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

حساب‌های مدیریت موجود در بکاپ با همان رمز قبلی قابل استفاده‌اند. فقط برای نصب کاملاً جدید و بدون بکاپ، پس از `migrate` می‌توانید `php artisan db:seed --class=SiteStructureSeeder` و `php artisan app:create-admin admin@example.com` اجرا کنید؛ فرمان ساخت مدیر رمز را مخفیانه دریافت می‌کند. برای دادهٔ نمونه در دیتابیس آزمایشی جداگانه می‌توان `php artisan db:seed` اجرا کرد؛ این کار حساب‌های نمایشی با رمز شناخته‌شده می‌سازد و برای production یا بکاپ واقعی مناسب نیست.

ورود مدیریت: `/admin/login`، سایت فارسی: `/`، انگلیسی: `/en`، دانلود و نصب PWA: `/download`، تعرفه: `/tariffs/calculator`، جستجوی کارشناس: `/experts`.

## اجرای استایل‌ها و JavaScript

خروجی `public/build` در Git ثبت نمی‌شود و پس از کلون باید ساخته شود. برای استفاده از خروجی ساخته‌شده، ابتدا پردازش `npm run dev` را متوقف کنید و دستورهای زیر را از ریشهٔ پروژه اجرا کنید:

```bash
npm ci
rm -f public/hot
npm run build
php artisan config:clear
php artisan view:clear
php artisan serve --host=127.0.0.1 --port=8000
```

سایت را در `http://127.0.0.1:8000` باز کنید. ریشهٔ پروژه یا فایل Blade را مستقیم در مرورگر باز نکنید. `APP_URL` و `FRONTEND_URL` در `.env` نیز باید همین نشانی باشند و `ASSET_URL` را خالی بگذارید. `public/build/manifest.json` و تمام فایل‌های `public/build/assets` باید با هم منتقل شوند. فایل `public/hot` متعلق به Vite در حال توسعه است و نباید همراه خروجی نهایی منتقل شود؛ اگر پس از توقف Vite باقی مانده است، پیش از build با دستور بالا حذفش کنید.

برای ویرایش زنده، `php artisan serve` را در یک ترمینال و `npm run dev` را در ترمینال دیگری اجرا کنید. هر دو پردازش باید فعال بمانند. دسترسی فایل‌ها و اتصال Vite در تنظیمات امنیتی فقط برای `APP_ENV=local` مجاز می‌شود؛ در production از `npm run build` استفاده کنید.

## انتقال سایت فعلی با حفظ اطلاعات

۱. از دیتابیس فعلی و تمام فایل‌های رسانه نسخهٔ پشتیبان بگیرید.
۲. دیتابیس فعلی را به دیتابیس مقصد import کنید؛ جداول با backend قبلی سازگارند.
۳. پوشهٔ رسانه در `storage/app` قبلی را در محل متناظر این پروژه کپی کنید. فایل ZIP حاوی عکس‌های آپلودشدهٔ سایت فعلی و دیتابیس واقعی آن نیست.
۴. هنگام جابه‌جایی خود سایت عملیاتی، کلید `APP_KEY` قبلی را نگه دارید تا نشست‌ها و داده‌های رمزگذاری‌شده قابل استفاده بمانند. برای نسخهٔ مستقل لوکال این پروژه، مطابق راهنمای محلی کلید تازه بسازید؛ رمزهای هش‌شدهٔ کاربران به APP_KEY وابسته نیستند. نشست‌ها و jobهای رمزگذاری‌شدهٔ قدیمی با کلید تازه قابل استفاده نیستند؛ لوکال از کوکی و پیشوند کش جدا و صف `sync` استفاده می‌کند و نباید worker صف عملیاتی را اجرا کند.
۵. `.env.production.example` را مبنای تنظیمات سرور قرار دهید. دامنه را در `APP_URL`، `FRONTEND_URL`، `CORS_ALLOWED_ORIGINS` و `SANCTUM_STATEFUL_DOMAINS` تنظیم کنید. مقدار آخر فقط hostname است.
۶. `php artisan migrate --force` اجرا کنید؛ از `migrate:fresh` و DemoSeeder روی دادهٔ واقعی استفاده نکنید.

در این معماری متغیرهای `API_URL`، `SITE_URL` و `NEXT_PUBLIC_API_URL` نیاز نیستند. درخواست مرورگر به `/api/v1` همان دامنه ارسال می‌شود و پروکسی Next.js حذف شده است. مسیر قدیمی نسبی `/backend/api/v1/media/{id}` برای سازگاری متن‌های ذخیره‌شده حفظ شده است. نشانی‌های مطلق متعلق به دامنهٔ قبلی در محتوای دیتابیس باید هنگام انتقال بازبینی شوند.

## استقرار

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

پس از کلون و با هر تغییر JS/CSS، `npm ci` و `npm run build` را پیش از انتقال اجرا کنید و خروجی `public/build` را همراه پروژه منتقل کنید. وب‌سرور باید فقط پوشهٔ `public` را سرو کند؛ ریشهٔ پروژه و فایل `.env` نباید در دسترس وب قرار گیرند. نمونهٔ Nginx در `deploy/nginx.conf` است؛ مسیر پروژه، دامنه، گواهی و socket واقعی PHP-FPM را تنظیم کنید. `storage` و `bootstrap/cache` باید برای کاربر PHP قابل نوشتن باشند؛ دسترسی 777 ندهید.

برای صف، نمونهٔ Supervisor در `deploy/queue-worker.conf` موجود است. Scheduler:

```cron
* * * * * cd /var/www/kkrm && php artisan schedule:run >> /dev/null 2>&1
```

## امکانات و محل کدها

- مسیرهای سایت و مدیریت: `routes/web.php`؛ APIهای قبلی: `routes/api.php` و فایل‌های routes وابسته.
- کنترلرهای وب: `app/Http/Controllers/Web`؛ داده و اعتبارسنجی: سرویس‌ها و Requestهای backend قبلی.
- قالب عمومی: `resources/views/layouts/site.blade.php`؛ هدر و فوتر: `resources/views/components`.
- مدیریت و فرم‌ها: `resources/views/admin` و `resources/js/management.js`.
- ویرایشگر جدول، تصویر، آلبوم و PDF: `resources/js/editor.js`؛ بارگذاری‌ها از API امن رسانه انجام می‌شوند.
- تعاملات، تم، اسلایدر، تقویم شمسی و لایت‌باکس: `resources/js/app.js`.
- CSS اصلی و Tailwind: `resources/css/app.css`.
- پیکربندی فیلدهای مدیریت و ترجمه‌های رابط: `resources/data/*.json`.
- دریافت مستقیم اپلیکیشن: متغیر `APP_DOWNLOAD_URL` در `.env`؛ فعلاً خالی است.
- دادهٔ فارسی و انگلیسی محتوا جدا ذخیره می‌شود؛ نبود ترجمه باعث استفاده از متن اصلی می‌شود.

## بررسی اجرا

جزئیات بررسی‌ها و محدودیت‌های تطبیق در `MIGRATION.md` ثبت شده است. برنامه نیاز به تست‌های ذخیره‌شده در پروژه ندارد؛ اسکریپت‌های بررسی موقت در بسته قرار نگرفته‌اند.

## آماده‌سازی Vercel

در روش فعلی، Laravel، Blade، MySQL و فایل‌های `storage` روی هاست لاراول اجرا می‌شوند و Vercel درخواست‌ها را به همان برنامه می‌فرستد. این روش به AWS یا S3 نیاز ندارد. تمام رابط سایت و داشبورد همچنان Blade است و Next.js اضافه نشده است. راهنمای کامل در [deploy/vercel/README.fa.md](deploy/vercel/README.fa.md) قرار دارد.

در Vercel فقط دو مقدار Config لازم است: `LARAVEL_ORIGIN` برای آدرس HTTPS سرور لاراول و `PUBLIC_SITE_URL` برای آدرس نهایی سایت. رمز دیتابیس و APP_KEY فقط روی هاست لاراول می‌مانند. `vercel.json` و `deploy/vercel/build.mjs` مسیرهای پراکسی را می‌سازند. نمونهٔ تنظیمات هاست: `.env.production.example`؛ نمونهٔ تنظیمات ورسل: `.env.vercel.example`.

## بررسی و انتقال عکس‌ها

در نسخهٔ گیت‌هاب مبنا (`51a066b8e3cba1aa4564fa0fd20d6c2c2e9034ee`) هیچ فایل رسانه‌ای زیر `storage` ثبت نشده بود. وجود رکورد در جدول `media` به معنای همراه بودن فایل عکس با SQL یا Git نیست. مسیر پیش‌فرض فایل با `disk=local`، برابر `storage/app/private/` به‌علاوهٔ مقدار `media.path` است. `storage:link` فقط دیسک public را لینک می‌کند. عکس‌های خصوصی و عمومی این برنامه از `/api/v1/media/{id}` با کنترل دسترسی سرو می‌شوند.

```bash
php artisan config:clear
php artisan media:audit --verify
```

این فرمان فقط می‌خواند و فایل‌ها و دیتابیس را تغییر نمی‌دهد. برای اصلاح محل فایل‌ها و توضیح `--source-root`، [راهنمای رسانه](docs/MEDIA.fa.md) را ببینید. در روش فعلی، فایل‌ها در storage همان هاست لاراول باقی می‌مانند و به سرویس دیگری منتقل نمی‌شوند.

قالب‌های Blade باید در Git ثبت باشند؛ `resources/views/.gitignore` نباید شامل `*` باشد. فایل‌های خصوصی `storage` را در ریپازیتوری عمومی commit نکنید.

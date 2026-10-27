# پورتال کانون — Laravel Blade

این نسخه از سورس `Accelerator-number-one/kkrm` در commit `61cd03f99f881bd8f1d46fa7c79e2c5039e01408` تبدیل شده است. سایت و مدیریت در یک برنامهٔ Laravel با Blade، Tailwind CSS و Alpine.js اجرا می‌شوند. React و Next.js در این نسخه وابستگی اجرایی ندارند. APIهای قبلی حفظ شده‌اند؛ قالب‌های عمومی داده را مستقیماً از سرویس‌های Laravel می‌خوانند.

## پیش‌نیاز

PHP 8.3 یا جدیدتر مطابق composer.lock، Composer 2، MySQL 8 یا MariaDB سازگار و افزونه‌های PDO MySQL، intl، mbstring، GD، fileinfo، DOM/XML، curl و zip. Node.js 22 برای ساخت دارایی‌ها توصیه می‌شود؛ در سرور Node دائماً اجرا نمی‌شود.

## نصب محلی

```bash
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
composer install
cp .env.example .env
php artisan key:generate
```

دیتابیس MySQL به نام `kkr` بسازید و اطلاعات `DB_*` را در `.env` وارد کنید. `APP_URL` و `FRONTEND_URL` را هر دو `http://127.0.0.1:8000` قرار دهید.

```bash
php artisan migrate
php artisan db:seed --class=SiteStructureSeeder
php artisan app:create-admin admin@example.com
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

برای دادهٔ نمونه فقط در محیط local می‌توانید `php artisan db:seed` اجرا کنید؛ این کار حساب‌های نمایشی با رمز شناخته‌شده می‌سازد و برای production مناسب نیست.

ورود مدیریت: `/admin/login`، سایت فارسی: `/`، انگلیسی: `/en`، دانلود و نصب PWA: `/download`، تعرفه: `/tariffs/calculator`، جستجوی کارشناس: `/experts`.

## اجرای استایل‌ها و JavaScript

برای استفاده از خروجی آماده، ابتدا پردازش `npm run dev` را متوقف کنید و دستورهای زیر را از ریشهٔ پروژه اجرا کنید:

```bash
npm ci
npm run build
php artisan config:clear
php artisan view:clear
php artisan serve --host=127.0.0.1 --port=8000
```

سایت را در `http://127.0.0.1:8000` باز کنید. ریشهٔ پروژه یا فایل Blade را مستقیم در مرورگر باز نکنید. `APP_URL` و `FRONTEND_URL` در `.env` نیز باید همین نشانی باشند و `ASSET_URL` را خالی بگذارید. `public/build/manifest.json` و تمام فایل‌های `public/build/assets` باید با هم منتقل شوند. فایل `public/hot` متعلق به Vite در حال توسعه است و نباید همراه خروجی نهایی منتقل شود؛ `npm run build` آن را حذف می‌کند.

برای ویرایش زنده، `php artisan serve` را در یک ترمینال و `npm run dev` را در ترمینال دیگری اجرا کنید. هر دو پردازش باید فعال بمانند. دسترسی فایل‌ها و اتصال Vite در تنظیمات امنیتی فقط برای `APP_ENV=local` مجاز می‌شود؛ در production از `npm run build` استفاده کنید.

## انتقال سایت فعلی با حفظ اطلاعات

۱. از دیتابیس فعلی و تمام فایل‌های رسانه نسخهٔ پشتیبان بگیرید.
۲. دیتابیس فعلی را به دیتابیس مقصد import کنید؛ جداول با backend قبلی سازگارند.
۳. پوشهٔ رسانه در `storage/app` قبلی را در محل متناظر این پروژه کپی کنید. فایل ZIP حاوی عکس‌های آپلودشدهٔ سایت فعلی و دیتابیس واقعی آن نیست.
۴. کلید `APP_KEY` قبلی را نگه دارید؛ کلید جدید برای دیتابیس انتقال‌یافته تولید نکنید.
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

فایل‌های `public/build` آمادهٔ استفاده‌اند. با تغییر JS/CSS، `npm ci && npm run build` را پیش از انتقال اجرا کنید. وب‌سرور باید فقط پوشهٔ `public` را سرو کند؛ ریشهٔ پروژه و فایل `.env` نباید در دسترس وب قرار گیرند. نمونهٔ Nginx در `deploy/nginx.conf` است؛ مسیر پروژه، دامنه، گواهی و socket واقعی PHP-FPM را تنظیم کنید. `storage` و `bootstrap/cache` باید برای کاربر PHP قابل نوشتن باشند؛ دسترسی 777 ندهید.

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

فایل‌های اجرای Container در `Dockerfile.vercel` و `deploy/vercel` موجودند. راهنمای متغیرهای دیتابیس و فضای فایل در `deploy/vercel/README.fa.md` است. فایل‌های اجرای ورسل اختیاری هستند. ادامهٔ دیپلوی به درخواست کاربر متوقف شده است؛ این بسته برای اجرای مستقل لاراول نیز قابل استفاده است. این کانتینر فقط ورودی HTTPS ورسل را فرض می‌کند.

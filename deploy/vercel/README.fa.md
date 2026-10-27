# استقرار بدون AWS؛ لاراول روی هاست و اتصال از Vercel

این روش از همان الگوی استقرار استانداری استفاده می‌کند: برنامهٔ Laravel و فایل‌های آپلودشده روی هاست لاراول هستند و Vercel به سرور برنامه متصل می‌شود. در این پروژه رابط سایت و داشبورد Blade باقی مانده‌اند؛ Vercel درخواست صفحه، API، فایل و دارایی را به Laravel می‌فرستد. React یا Next.js اضافه نشده است.

فایل‌های کاربر در `storage` هاست لاراول می‌مانند. این روش به AWS، S3، bucket یا انتقال عکس‌ها به فضای ابری جدا نیاز ندارد. پردازش PHP داخل Vercel اجرا نمی‌شود. خاموش شدن یا خطای سرور Laravel روی سایت Vercel هم اثر می‌گذارد.

## ۱. نصب نسخهٔ Blade روی هاست Laravel

از دیتابیس و storage نسخهٔ پشتیبان بگیرید. سورس جدید را در ریشهٔ هاست Laravel، کنار artisan، قرار دهید. `.env`، `vendor` و فایل‌های storage موجود را حذف نکنید. وب‌سرور باید پوشهٔ public را سرو کند. خروجی `public/build` باید شامل manifest و تمام دارایی‌های متناظر آن باشد.

برای ساخت دارایی‌ها روی دستگاه محلی:

```bash
npm ci
npm run build
```

فایل‌های public/build ساخته‌شده را همراه سورس منتقل کنید. اگر Node روی هاست دارید، همین دو فرمان را می‌توانید آنجا اجرا کنید.

روی چابوکان، فایل `chabok-pre-start.sh` در ریشهٔ برنامه قرار می‌گیرد. پلتفرم پیش از شروع سرویس، migrationهای اجرا‌نشده و بازسازی کش‌های تنظیمات، مسیرها و قالب‌ها را از این فایل اجرا می‌کند. این Hook داده‌ای را seed یا حذف نمی‌کند. پیش از استقرار از فایل‌ها و دیتابیس بکاپ بگیرید؛ در صورت قطع بودن کنسول نیز می‌توان از مدیریت فایل‌ها و راه‌اندازی مجدد سرویس برای همین مسیر رسمی استفاده کرد.

روی هاست و در پوشه‌ای که artisan داخلش است:

```bash
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan media:audit --verify
```

برنامه با PHP 8.3 یا جدیدتر و افزونه‌های موردنیاز composer.json اجرا می‌شود. پوشه‌های storage و bootstrap/cache باید برای کاربر PHP قابل نوشتن باشند. دادهٔ واقعی را با migrate:fresh یا DemoSeeder جایگزین نکنید.

## ۲. تنظیمات روی هاست Laravel

نمونهٔ کامل: `.env.production.example`. APP_KEY و DB_* همان مقادیر واقعی هاست فعلی باشند. این بخش‌ها را متناسب با دامنه‌ها تنظیم کنید:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://laravel-q2yx48.chbkn.dev
PUBLIC_SITE_URL=https://kkrm-laravel.vercel.app
FRONTEND_URL=https://kkrm-laravel.vercel.app
CORS_ALLOWED_ORIGINS=https://kkrm-laravel.vercel.app
SANCTUM_STATEFUL_DOMAINS=kkrm-laravel.vercel.app,laravel-q2yx48.chbkn.dev

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
MEDIA_DISK=local
LOG_CHANNEL=stack
LOG_STACK=daily
```

آدرس‌ها را با آدرس واقعی پروژه عوض کنید. PUBLIC_SITE_URL باید روی هاست و Vercel یکسان باشد. در درخواست پراکسی که علامت origin شناخته‌شده را داشته باشد، Laravel لینک‌ها و redirectها را با همین مبدأ عمومی می‌سازد تا کاربر در دامنهٔ سایت بماند. علامت پراکسی هیچ نقشی در احراز هویت یا اجازهٔ دسترسی فایل ندارد و فقط مبدأ تولید URL را تعیین می‌کند.

در اجرای local، PUBLIC_SITE_URL را خالی بگذارید و APP_ENV=local و APP_URL=http://127.0.0.1:8000 و SESSION_SECURE_COOKIE=false باشند.

پس از تغییر env روی هاست:

```bash
php artisan config:clear
php artisan config:cache
```

نشانی‌های `/`، `/admin/login` و `/up` باید روی خود سرور Laravel پاسخ درست داشته باشند. موفق بودن `/up` به‌تنهایی به معنی نصب قالب Blade نیست. اگر `/admin/login` روی خود سرور 404 باشد، Vercel آن صفحه را ایجاد نمی‌کند.

## ۳. تنظیمات Vercel

ریپازیتوری `AmirRiahi2008/kkrm-laravel` و شاخهٔ master را متصل کنید. Root Directory برابر `./` و Framework برابر Other باشد. vercel.json این تنظیمات را دارد:

- installCommand: `node --version`
- buildCommand: `node deploy/vercel/build.mjs`
- هیچ Output Directory override لازم نیست؛ Build Output API استفاده می‌شود.

فقط دو متغیر Config برای Production لازم است:

```dotenv
LARAVEL_ORIGIN=https://laravel-q2yx48.chbkn.dev
PUBLIC_SITE_URL=https://kkrm-laravel.vercel.app
```

LARAVEL_ORIGIN باید origin HTTPS هاست Laravel و بدون `/api/v1`، مسیر، query یا اطلاعات ورود باشد. PUBLIC_SITE_URL باید دامنهٔ نهایی عمومی Vercel یا دامنهٔ اختصاصی باشد. این دو آدرس نباید یکی باشند، چون حلقهٔ پراکسی ایجاد می‌شود. رمز دیتابیس، APP_KEY و کلید فضای فایل در Vercel لازم نیستند.

فایل build.mjs تنظیم `.vercel/output/config.json` را تولید می‌کند. تمام مسیرها با حفظ path به سرور Laravel بازنویسی می‌شوند و هدر مبدأ عمومی تنظیم می‌شود. کش پراکسی برای شروع خاموش است تا پاسخ نشست و داشبورد مشترک cache نشود. فایل‌های محلی private، vendor و دیتابیس همراه این پراکسی به Vercel منتقل نمی‌شوند.

پس از تغییر متغیرها Redeploy کنید. اگر دامنهٔ عمومی تغییر کرد، PUBLIC_SITE_URL، FRONTEND_URL و دامنهٔ مجاز روی هاست نیز به‌روزرسانی شوند.

## ۴. بررسی نهایی

صفحهٔ اصلی، انگلیسی، کارشناسان، خبر واقعی، تصویر واقعی و `/admin/login` را باز کنید. وارد داشبورد شوید، فهرست‌ها را باز کنید، عکس کوچک آپلود و مطلبی را ذخیره کنید. عکس باید پس از Redeploy ورسل نیز باقی بماند، چون روی هاست Laravel ذخیره شده است.

APIهای مدیریت middleware نشست و CSRF مستقل دارند؛ دریافت فهرست و عکس خصوصی به ارسال Referer مرورگر وابسته نیست. احراز هویت، نقش‌ها، session_version و اجازهٔ دسترسی رسانه همچنان در Laravel بررسی می‌شوند.

اگر Laravel خطای 500 داد، فایل لاگ روز در storage/logs را بررسی کنید. برای 404، route و نصب قالب‌های Blade روی هاست را بررسی کنید. برای مشکل فایل، [راهنمای رسانه](../../docs/MEDIA.fa.md) را اجرا کنید. ایمیل واقعی و صف همچنان باید روی هاست Laravel تنظیم شوند.

## وضعیت سرور هنگام آماده‌سازی

در بررسی عمومی ۲۷ اکتبر ۲۰۲۶، سرور laravel-q2yx48.chbkn.dev در `/up` پاسخ 200 داشت اما `/` و `/admin/login` پاسخ 404 دادند. بنابراین برای کامل شدن سایت، نسخهٔ جدید Blade باید روی آن سرویس نصب شود. نشانی kkrm.irancompile.com در همان بررسی پاسخ 500 داشت. این گزارش به معنی رفع خطای آن هاست‌ها یا نصب خودکار فایل‌ها روی آن‌ها نیست.

## مستندات رسمی

- https://vercel.com/docs/routing/rewrites
- https://vercel.com/docs/build-output-api/configuration

# آماده‌سازی و استقرار Laravel Blade روی Vercel

این راهنما برای نسخهٔ یکپارچهٔ Laravel است. سایت و داشبورد با PHP اجرا می‌شوند؛ Node فقط CSS و JavaScript را هنگام ساخت آماده می‌کند. `Dockerfile.vercel` از Container Runtime ورسل، PHP 8.4 و Apache روی پورت 80 استفاده می‌کند.

دو دادهٔ مستقل لازم است: دیتابیس MySQL خارجی و فایل‌های رسانه در یک bucket خصوصی S3. وارد کردن SQL فقط رکوردها را برمی‌گرداند؛ عکس‌ها باید جدا منتقل شوند. فایل‌سیستم کانتینر محل نگهداری دائمی آپلودها نیست. این تغییرات هنوز به حساب Vercel دیپلوی نشده‌اند.

## ۱. فایل‌های اصلاح‌شده را وارد پروژه کنید

فایل‌های بسته را با همان مسیرها جایگزین کنید. پوشه‌های `vendor`، `node_modules` و `.env` خودتان را نگه دارید. فایل `resources/views/.gitignore` اصلاح‌شده را نیز جایگزین کنید؛ نسخهٔ قبلی شامل `*` بود و قالب‌ها را از Git کنار می‌گذاشت.

```bash
composer install
npm ci
npm run build
php artisan config:clear
php artisan view:clear
php artisan view:cache
php artisan route:list --path=api/v1/media
```

در خروجی فرمان آخر باید مسیر GET و HEAD عکس‌ها وجود داشته باشد. برای مشاهدهٔ middlewareها می‌توانید `-v` اضافه کنید. قالب‌ها و فایل‌های deployment را در Git ثبت کنید. `.env`، SQL، عکس‌های خصوصی، vendor و node_modules را commit نکنید. Docker هنگام build وابستگی‌ها و `public/build` را می‌سازد.

```bash
git status --short
git add .
git diff --cached --stat
```

پس از بازبینی تغییرات staged و اطمینان از نبود فایل خصوصی، خودتان commit و push کنید. اگر فایل محرمانه از قبل tracked بوده، ignore آن را حذف نمی‌کند و باید جدا از index خارج شود.

## ۲. دیتابیس مقصد را مشخص کنید

در پنل چابوکان، وارد سرویس MySQL مورد نظر شوید و اطلاعات اتصال خارجی و نام دیتابیس را بخوانید. Host داخلی، localhost و 127.0.0.1 برای اتصال Vercel به دیتابیس چابوکان مناسب نیستند. `DB_PORT` نیز پورت بیرونی همان سرویس است، نه لزوماً 3306.

اگر به همان دیتابیس قبلی متصل می‌شوید، دوباره SQL را وارد نکنید. اگر دیتابیس جدید است، بکاپ را در دیتابیس خالی import کنید و سپس این فرمان‌ها را روی دستگاه خودتان با `.env` متصل به همان دیتابیس اجرا کنید:

```bash
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
```

برای دیتابیس قبلی `APP_KEY` قبلی را نگه دارید. `key:generate`، `migrate:fresh` و DemoSeeder لازم نیستند. در این بسته build و startup هیچ migration یا seeder اجرا نمی‌کنند. برای دیتابیس کاملاً تازه و بدون بکاپ، SiteStructureSeeder و `app:create-admin` را فقط یک بار طبق README اصلی اجرا کنید.

## ۳. فضای فایل دائمی و عکس‌های قبلی

یک bucket خصوصی سازگار با S3 در سرویس ذخیره‌سازی خودتان آماده کنید. این مخزن باید از محل اجرای ورسل قابل دسترسی باشد. کلید محدود به همان bucket با امکان خواندن، نوشتن و بررسی فایل‌ها کافی است. bucket را عمومی نکنید؛ عکس‌ها با همان مسیر امن لاراول نمایش داده می‌شوند.

اطلاعات AWS جدول پایین را در `.env` محلی دستگاهی که عکس‌ها را دارد وارد کنید. دیتابیس همان دیتابیس مقصد باشد. بعد از گرفتن backup، [راهنمای رسانه](../../docs/MEDIA.fa.md) را اجرا کنید:

```bash
php artisan config:clear
php artisan media:audit --verify
php artisan media:transfer --from=local --to=s3
php artisan media:transfer --from=local --to=s3 --apply
php artisan media:audit --verify
```

فرمان بدون `--apply` فقط بررسی می‌کند. فرمان دارای `--apply` فایل را کپی و هش آن را تأیید می‌کند؛ سپس disk همان رکورد را به s3 تغییر می‌دهد و اصل فایل را نگه می‌دارد. اگر مسیر فایل‌های قدیمی متفاوت است از `--source-root` طبق راهنمای رسانه استفاده کنید. انتقال باید روی دستگاهی انجام شود که عکس‌ها واقعاً در آن وجود دارند؛ Vercel پوشهٔ storage دستگاه شما را نمی‌بیند.

اگر سایت قبلی هم‌زمان از همین دیتابیس استفاده می‌کند، تغییر disk روی آن سایت هم اثر می‌گذارد. ابتدا تنظیمات و درایور S3 را در برنامهٔ قبلی نیز آماده کنید، یا انتقال آزمایشی را روی یک کپی جدا از دیتابیس انجام دهید.

## ۴. متغیرهای Vercel

در Project → Settings → Environment Variables ثبت کنید. فایل `.env.vercel.example` فهرست آمادهٔ بدون رمز است. مقدار placeholder یا عبارت فارسی جدول را عیناً به‌جای مقدار واقعی وارد نکنید.

| نام | مقدار | Type |
| --- | --- | --- |
| APP_NAME | KKRM Portal | Config |
| APP_ENV | production | Config |
| APP_DEBUG | false | Config |
| APP_KEY | همان کلید واقعی برنامهٔ قبلی | Secret |
| APP_URL | آدرس HTTPS نهایی همین پروژه؛ مثلاً https://YOUR-PROJECT.vercel.app | Config |
| APP_LOCALE | fa | Config |
| APP_FALLBACK_LOCALE | en | Config |
| DB_CONNECTION | mysql | Config |
| DB_HOST | Host خارجی سرویس MySQL چابوکان | Config |
| DB_PORT | پورت خارجی همان سرویس | Config |
| DB_DATABASE | نام دیتابیس واقعی | Config |
| DB_USERNAME | نام کاربر همان دیتابیس | Config |
| DB_PASSWORD | رمز همان کاربر | Secret |
| DB_CHARSET | utf8mb4 | Config |
| DB_COLLATION | utf8mb4_unicode_ci | Config |
| SESSION_DRIVER | database | Config |
| SESSION_LIFETIME | 60 | Config |
| SESSION_ENCRYPT | true | Config |
| SESSION_DOMAIN | null | Config |
| SESSION_SECURE_COOKIE | true | Config |
| SESSION_HTTP_ONLY | true | Config |
| SESSION_SAME_SITE | lax | Config |
| CACHE_STORE | database | Config |
| QUEUE_CONNECTION | sync | Config |
| FILESYSTEM_DISK | s3 | Config |
| MEDIA_DISK | s3 | Config |
| MEDIA_MAX_KB | 3840 | Config |
| MEDIA_LEGACY_ORIGINS | https://kkrm.vercel.app,https://laravel-q2yx48.chbkn.dev | Config |
| AWS_ACCESS_KEY_ID | Access Key مخزن فایل | Secret |
| AWS_SECRET_ACCESS_KEY | Secret Key مخزن فایل | Secret |
| AWS_BUCKET | نام bucket خصوصی | Config |
| AWS_DEFAULT_REGION | region اعلام‌شده توسط سرویس فایل | Config |
| AWS_ENDPOINT | نشانی HTTPS سرویس S3؛ برای AWS اصلی تنظیم نشود | Config |
| AWS_USE_PATH_STYLE_ENDPOINT | برای سرویس‌های سازگار معمولاً true؛ مطابق مستندات همان سرویس | Config |
| LOG_CHANNEL | stderr | Config |
| LOG_LEVEL | warning | Config |
| BCRYPT_ROUNDS | 12 | Config |
| MAIL_MAILER | برای شروع log؛ برای ارسال واقعی SMTP یا mailer تنظیم شود | Config |
| MAIL_FROM_ADDRESS | نشانی فرستندهٔ تأییدشدهٔ خودتان | Config |
| MAIL_FROM_NAME | KKRM | Config |
| CLAMAV_ENABLED | false | Config |
| CONTACT_RETENTION_DAYS | 180 | Config |
| AUDIT_RETENTION_DAYS | 365 | Config |

`FRONTEND_URL`، `CORS_ALLOWED_ORIGINS` و hostname فعلی Sanctum هنگام شروع کانتینر از APP_URL ساخته می‌شوند. در Preview، آدرس همان deployment از VERCEL_URL گرفته می‌شود. `TRUSTED_PROXIES` را بدون اطلاعات معتبر از زیرساخت، `*` نگذارید. `ASSET_URL`، `API_URL`، `NEXT_PUBLIC_API_URL` و `SITE_URL` لازم نیستند و تنظیمات باقی‌ماندهٔ Next.js را برای این برنامه وارد نکنید. `APP_DOWNLOAD_URL` اختیاری است و می‌تواند فعلاً تعریف نشود.

برای Production متغیرها را در محیط Production ثبت کنید. برای Preview بهتر است یک کپی دیتابیس و فضای فایل آزمایشی استفاده کنید، چون تغییرات داشبورد Preview در هر دیتابیسی که تنظیم کنید واقعاً ذخیره می‌شوند. Secretهای دیتابیس و فایل را داخل سورس یا متغیرهای `VITE_*` نگذارید.

## ۵. Import و Deploy در Vercel

۱. Add New → Project و ریپازیتوری `AmirRiahi2008/kkrm-laravel` را انتخاب کنید.
۲. Root Directory برابر ریشه‌ای باشد که artisan و Dockerfile.vercel داخل آن هستند؛ در این ریپازیتوری `./` است، نه public.
۳. Framework Preset را Container انتخاب کنید. اگر پروژهٔ قبلی Next.js را استفاده می‌کنید، preset و overrideهای قدیمی Install/Build/Output Directory را پاک کنید.
۴. Install Command، Build Command و Output Directory را override نکنید؛ Dockerfile ساخت PHP و دارایی‌ها را انجام می‌دهد.
۵. متغیرهای مرحلهٔ قبل را ذخیره کنید و Deploy بزنید. `vercel.json` منطقه را fra1 قرار داده است؛ در صورت نیاز و پشتیبانی حساب، نزدیک‌ترین منطقهٔ قابل اتصال به دیتابیس را انتخاب کنید.
۶. پس از دریافت آدرس نهایی Production، APP_URL را همان origin بدون مسیر اضافه تنظیم و Redeploy کنید. با تغییر متغیرها حتماً deployment جدید بسازید.

Vercel باید Dockerfile.vercel را تشخیص دهد. این روش به پکیج community PHP runtime و فایل api/index.php نیاز ندارد. پردازش HTTP در کانتینر روی پورت 80 است. فایل‌های موقت، کش قالب و تنظیمات لاراول در `/tmp/kkrm` هستند؛ session و cache مشترک روی MySQL و رسانه‌ها روی S3 باقی می‌مانند. لاگ در Runtime Logs ورسل دیده می‌شود.

## ۶. بررسی پس از Deploy

این نشانی‌ها را باز کنید:

- `/up` برای اجرای برنامه؛ این مسیر به‌تنهایی سلامت دیتابیس یا bucket را تضمین نمی‌کند.
- `/`، `/en`، `/news`، `/experts` و جزئیات یک خبر واقعی.
- `/api/v1/media/شناسه` برای تصویر استفاده‌شده در محتوای منتشرشده.
- `/admin/login` و سپس پیش‌نمایش یک عکس تازه و خصوصی در داشبورد.

یک تصویر کوچک و یک PDF کوچک آپلود کنید، ذخیرهٔ مطلب و نمایش آن در سایت را بررسی کنید و یک Redeploy انجام دهید. رسانه باید بعد از Redeploy هم باقی بماند. محتوای cache‌شده و خطاهای ذخیره‌سازی را با Network مرورگر و Runtime Logs بررسی کنید. حالت Ready صرفاً تکمیل استقرار است.

حد فایل در این تنظیمات ۳۸۴۰ کیلوبایت است تا درخواست multipart زیر محدودیت payload ورسل باقی بماند. بارگذاری PDF یا تصویر بزرگ‌تر در این نسخه پشتیبانی نمی‌شود؛ برای فایل بزرگ باید آپلود مستقیم امن به فضای فایل جداگانه پیاده شود.

صف در این استقرار sync است و Supervisor اجرا نمی‌شود. وظیفهٔ روزانهٔ `app:prune-private-data` با صرف Deploy خودبه‌خود اجرا نمی‌شود؛ برای اجرای دوره‌ای آن scheduler خارجی یا endpoint محافظت‌شدهٔ مخصوص Cron لازم است. با `MAIL_MAILER=log` نیز ایمیل بازیابی رمز ارسال واقعی ندارد؛ برای بهره‌برداری، سرویس ایمیل واقعی را تنظیم کنید.

## منابع رسمی

- https://vercel.com/docs/functions/runtimes/container
- https://vercel.com/docs/environment-variables
- https://vercel.com/docs/functions/limitations
- https://laravel.com/docs/13.x/filesystem

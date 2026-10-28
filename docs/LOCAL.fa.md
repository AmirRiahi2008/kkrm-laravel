# اجرای پروژه روی لوکال با دیتابیس واقعی

این راهنما برای ریپازیتوری `AmirRiahi2008/kkrm-laravel`، دیتابیس محلی `kkrm` و بکاپ `mysql-z03z94_2026-10-28_737141.sql` نوشته شده است. سایت عمومی و داشبورد هر دو Laravel Blade هستند؛ Next.js، Vercel، AWS و سرور جداگانهٔ فرانت برای اجرای محلی لازم نیستند.

## ۱. پیش‌نیازها را بررسی کنید

```bash
php -v
composer --version
node -v
npm -v
mysql --version
```

- PHP 8.3 یا بالاتر سازگار با `composer.lock`، مثلاً PHP 8.4؛ Composer 2.
- MySQL 8؛ سرویس MySQL باید روشن باشد. نام دیتابیس در این راهنما `kkrm` است.
- Node.js 22.12 به بالا یا Node.js 24 برای Vite؛ در زمان اجرای عادی فقط PHP و MySQL روشن می‌مانند.
- افزونه‌های PHP: `pdo_mysql`، `intl`، `mbstring`، `gd`، `fileinfo`، `ctype`، `curl`، `dom`، `xml`، `tokenizer`، `zip` و `openssl`.

اگر روی مک از قبل Herd، Valet، Homebrew یا MySQL/phpMyAdmin دارید، از همان نصب استفاده کنید و سرویس MySQL دوم روی همان پورت اجرا نکنید. فقط در صورت نبود ابزارها و داشتن Homebrew، می‌توانید این نسخه‌ها را نصب کنید:

```bash
brew install php@8.4 composer mysql@8.4 node@22
brew services start mysql@8.4
```

برای استفاده از این نسخه‌های Homebrew در ترمینال فعلی، در صورت نیاز:

```bash
export PATH="$(brew --prefix php@8.4)/bin:$(brew --prefix node@22)/bin:$(brew --prefix mysql@8.4)/bin:$PATH"
```

اگر چند نسخهٔ PHP دارید، `php --ini` و `php -m` را بررسی کنید. برای رفع نبود افزونه، نصب PHP را اصلاح کنید؛ `--ignore-platform-reqs` استفاده نکنید.

## ۲. پروژه را کلون کنید

در پوشه‌ای که می‌خواهید پروژه قرار بگیرد:

```bash
git clone https://github.com/AmirRiahi2008/kkrm-laravel.git
cd kkrm-laravel
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
cp .env.example .env
composer install
php artisan key:generate
```

تمام دستورهای بعدی، جز دستورهای کپی فایل که مسیر مشخص دارند، از همین ریشهٔ پروژه اجرا می‌شوند؛ همان پوشه‌ای که `artisan` و `composer.json` در آن هستند. `composer update` لازم نیست. فایل `.env` فقط محلی است و در Git ثبت نمی‌شود.

`key:generate` را یک بار برای همین نصب مستقل لوکال اجرا کنید. رمز کاربران داخل SQL هش شده و با کلید تازه عوض نمی‌شود. برای جابه‌جایی خود سایت عملیاتی، APP_KEY عملیاتی باید حفظ شود؛ کلید لوکال را روی هاست قرار ندهید. نسخهٔ لوکال کوکی و پیشوند کش جدا دارد و صف‌های قدیمی بکاپ را اجرا نمی‌کند.

## ۳. فایل `.env` را تنظیم کنید

`.env.example` تنظیمات کامل لوکال را دارد. پس از کپی، فقط اتصال واقعی MySQL خود را تنظیم کنید. مقادیر اصلی باید این‌ها باشند:

```dotenv
APP_NAME="KKRM Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
ASSET_URL=
PUBLIC_SITE_URL=
APP_LOCALE=fa
APP_FALLBACK_LOCALE=en

FRONTEND_URL=http://127.0.0.1:8000
CORS_ALLOWED_ORIGINS=http://127.0.0.1:8000
SANCTUM_STATEFUL_DOMAINS=localhost:8000,127.0.0.1:8000
TRUSTED_PROXIES=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kkrm
DB_USERNAME=root
DB_PASSWORD=""
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=database
SESSION_COOKIE=kkrm_local_session
SESSION_LIFETIME=60
SESSION_ENCRYPT=true
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
CACHE_PREFIX=kkrm_local_cache_
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
MEDIA_DISK=local
MEDIA_LOCAL_ROOT=
MEDIA_PUBLIC_ROOT=
MEDIA_LEGACY_ORIGINS=https://kkrm.vercel.app,https://kkrm-laravel.vercel.app,https://laravel-q2yx48.chbkn.dev
MEDIA_MAX_KB=51200

CLAMAV_ENABLED=false
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=debug
MAIL_MAILER=log
APP_DOWNLOAD_URL=
```

مقدار `APP_KEY` تولیدشده را در `.env` نگه دارید؛ هنگام کپی این بخش حذفش نکنید. سایر خطوط موجود در `.env.example` هم باقی می‌مانند.

- `DB_USERNAME` و `DB_PASSWORD` را با نام کاربری و رمز MySQL **روی مک خودتان** جایگزین کنید. `root` و رمز خالی صرفاً نمونه‌اند. اگر سرویس شما پورت دیگری دارد، `DB_PORT` را هم عوض کنید.
- `DB_HOST=127.0.0.1` اتصال TCP برقرار می‌کند؛ درگیری با socket متفاوت MySQL با `localhost` را کم می‌کند.
- `PUBLIC_SITE_URL` و `ASSET_URL` خالی باشند تا لینک‌ها و فایل‌ها به دامنهٔ عملیاتی نروند.
- `SESSION_SECURE_COOKIE=false` برای HTTP لوکال لازم است؛ `SESSION_DOMAIN=null` را بدون کوتیشن بنویسید.
- `MAIL_MAILER=log` ایمیل واقعی ارسال نمی‌کند؛ پیام ایمیل در لاگ محلی نوشته می‌شود.
- `QUEUE_CONNECTION=sync` عملیات جدید را همان لحظه اجرا می‌کند. برای این نصب worker یا scheduler لازم نیست؛ صف‌های واردشده از بکاپ عملیاتی را روی لوکال اجرا نکنید.
- متغیرهای `API_URL`، `NEXT_PUBLIC_API_URL`، `SITE_URL` و `LARAVEL_ORIGIN` برای اجرای لوکال این نسخه لازم نیستند.

در تمام مدت از `http://127.0.0.1:8000` استفاده کنید. برای ورود مدیریت بین `localhost` و `127.0.0.1` جابه‌جا نشوید. اگر پورت سایت را عوض کردید، `APP_URL`، `FRONTEND_URL`، `CORS_ALLOWED_ORIGINS` و `SANCTUM_STATEFUL_DOMAINS` را هم هماهنگ کنید.

## ۴. دیتابیس خالی بسازید و بکاپ را وارد کنید

مهم: **ابتدا import و بعد migrate**. بکاپ خودش جدول‌ها را می‌سازد. اگر قبل از import، `migrate` اجرا کنید یا جدول‌های نصب قبلی در مقصد باشند، ممکن است خطای `Table already exists` دریافت کنید. برای حذف جدول‌های موجود عجله نکنید؛ ابتدا از آن دیتابیس هم بکاپ بگیرید.

اگر MySQL شما روی پورت 3306 است و کاربر آن `root` است:

```bash
mysql -h 127.0.0.1 -P 3306 -u root -p -e "CREATE DATABASE IF NOT EXISTS kkrm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h 127.0.0.1 -P 3306 -u root -p -e "SHOW TABLES FROM kkrm;"
```

بعد از هر دستور، رمز همان کاربر MySQL را وارد کنید؛ اگر رمز ندارد Enter بزنید. رمز را داخل دستور ننویسید. نام کاربر و پورت دستورها باید با `.env` هماهنگ باشند. خروجی `SHOW TABLES` باید خالی باشد؛ در غیر این صورت ابتدا تکلیف اطلاعات قبلی را مشخص کنید.

فایل SQL را در Downloads قرار دهید و کل آن را وارد کنید:

```bash
mysql -h 127.0.0.1 -P 3306 -u root -p --default-character-set=utf8mb4 kkrm < "$HOME/Downloads/mysql-z03z94_2026-10-28_737141.sql"
```

اگر مسیر یا نام فایل متفاوت است، همان بخش انتهای دستور را عوض کنید. SQL و اطلاعات واقعی کاربران را در پوشهٔ عمومی وب یا Git قرار ندهید.

در phpMyAdmin هم می‌توانید دیتابیس خالی `kkrm` با collation برابر `utf8mb4_unicode_ci` بسازید، آن را انتخاب کنید و از زبانهٔ **Import** همین فایل کامل را وارد کنید. جدول‌ها را جداگانه و با ترتیب دستی import نکنید؛ بکاپ وابستگی‌های کلید خارجی را مدیریت می‌کند.

بعد از اتمام موفق import:

```bash
php artisan optimize:clear
php artisan migrate
php artisan migrate:status
```

`migrate` فقط مایگریشن‌های جدیدتر از بکاپ را اجرا می‌کند. برای دادهٔ واقعی `db:seed`، `DemoSeeder`، `migrate:fresh` و `migrate:refresh` اجرا نکنید. حساب‌های مدیریت و منوهای فعلی داخل همین SQL هستند.

## ۵. عکس‌ها و پیوست‌های واقعی را کپی کنید

SQL فقط رکورد و مسیر فایل را دارد؛ خود عکس، PDF و اکسل را ندارد. فایل‌های گرافیکی قالب زیر `public/assets` در Git هستند، اما کل رسانه‌های بارگذاری‌شدهٔ هاست همراه clone نیستند.

از FileZilla یا مدیریت فایل‌های هاست، پوشه‌های رسانه را دریافت کنید و در مسیر متناظر قرار دهید:

| مسیر در هاست قبلی | مسیر داخل پروژهٔ محلی |
| --- | --- |
| `storage/app/private/media/` | `storage/app/private/media/` |
| `storage/app/public/media/` | `storage/app/public/media/` |

ساختار زیرپوشه‌ها و نام فایل‌ها را دقیق حفظ کنید. برای مثال رکوردی با `disk=local` و `path=media/2026/10/example.jpg` به فایل `storage/app/private/media/2026/10/example.jpg` نیاز دارد. اگر فایل‌ها قبلاً در ریشهٔ دیگری بوده‌اند، [راهنمای رسانه](MEDIA.fa.md) را ببینید. فایل درست را از هاست بگیرید؛ تغییر هش دیتابیس یا جایگزین کردن فایل نامرتبط راه رفع خطا نیست.

سپس:

```bash
php artisan storage:link
php artisan media:audit --verify
```

`storage:link` تنها `public/storage` را به `storage/app/public` متصل می‌کند؛ فایل‌های خصوصی از مسیر کنترل‌شدهٔ `/api/v1/media/{id}` ارائه می‌شوند و نباید به `public` منتقل شوند. `media:audit` فقط بررسی می‌کند؛ `Missing` یعنی فایل متناظر پیدا نشده و `Invalid` می‌تواند نشانهٔ خرابی فایل، عدم تطابق یا دسترسی نامناسب باشد. اگر بعضی تصاویر هنوز منتقل نشده‌اند، باقی نصب قابل انجام است ولی آن تصاویر دیده نخواهند شد.

اگر لینک `public/storage` از نصب قبلی به مسیر دیگری اشاره می‌کند، نخست بررسی کنید که واقعاً symlink است؛ سپس `php artisan storage:unlink` و `php artisan storage:link` اجرا کنید. پوشهٔ واقعی حاوی فایل را به‌جای لینک حذف نکنید.

پوشه‌های `storage` و `bootstrap/cache` باید برای کاربر خودتان قابل نوشتن باشند. از `chmod 777` و اجرای روزمرهٔ Composer یا Artisan با `sudo` استفاده نکنید.

## ۶. استایل و JavaScript را بسازید

از ریشهٔ پروژه:

```bash
npm ci
rm -f public/hot
npm run build
php artisan view:clear
```

اگر Vite قبلاً باز است، اول آن را با Ctrl+C متوقف کنید. `public/hot` یک فایل تولیدی برای نشانی سرور توسعه است؛ فایل آپلودی کاربران نیست. خروجی `public/build/manifest.json` و فایل‌های CSS/JS پس از build ساخته می‌شوند و در Git نیستند. نبود این خروجی می‌تواند باعث خطای manifest یا نمایش بدون استایل شود.

## ۷. سایت را اجرا کنید

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

این ترمینال باز بماند. نشانی‌ها:

| بخش | نشانی |
| --- | --- |
| سایت فارسی | `http://127.0.0.1:8000/` |
| سایت انگلیسی | `http://127.0.0.1:8000/en` |
| ورود روابط عمومی | `http://127.0.0.1:8000/admin/login` |
| اخبار | `http://127.0.0.1:8000/news` |
| کارشناسان | `http://127.0.0.1:8000/experts` |
| قوانین و اسناد | `http://127.0.0.1:8000/documents` |
| دانلود اپلیکیشن | `http://127.0.0.1:8000/download` |

برای ورود از ایمیل و رمز فعلی حساب داخل بکاپ استفاده کنید؛ import رمز را عوض نمی‌کند. سایت را با دابل‌کلیک روی فایل‌ها، نشانی پورت 5173 یا دستور `npm start` باز نکنید. سرور اصلی همین Artisan روی پورت 8000 است.

## ۸. توسعه با تغییر زنده، اختیاری

برای تغییر CSS/JS و دیدن نتیجهٔ زنده، Artisan را روشن نگه دارید و در ترمینال دوم از ریشهٔ پروژه اجرا کنید:

```bash
npm run dev
```

سایت همچنان در `http://127.0.0.1:8000` باز می‌شود. هنگام استفاده از Vite هر دو ترمینال باز بمانند. برای بازگشت به اجرای معمول، Vite را با Ctrl+C متوقف کنید، `rm -f public/hot` و سپس `npm run build` اجرا کنید. برای استفادهٔ روزمره بعد از build فقط MySQL و Artisan لازم‌اند.

## ۹. بررسی و رفع خطاهای معمول

| نشانه | بررسی لازم |
| --- | --- |
| `ext-intl` یا افزونهٔ PHP موجود نیست | `php --ini` و `php -m`؛ Composer باید با همان PHP درست اجرا شود. وابستگی‌ها را نادیده نگیرید. |
| `Connection refused` | سرویس MySQL، پورت و `DB_HOST` را بررسی کنید. |
| `Access denied` | نام کاربری و رمز MySQL را در `.env` اصلاح و `php artisan config:clear` اجرا کنید. |
| `Unknown database` | دیتابیس `kkrm` را بسازید و بکاپ را در همان دیتابیس import کنید. |
| `Table already exists` هنگام import | مقصد خالی نیست؛ روی import نیمه‌کاره یا نصب migrateشده دوباره import نکنید. ابتدا از اطلاعات قبلی نسخهٔ پشتیبان بگیرید. |
| خطای کلید خارجی هنگام import | کل فایل اصلی را در مقصد خالی import کنید؛ چند INSERT جدا از بکاپ کافی نیست. |
| صفحه بدون استایل یا `Vite manifest not found` | مرحلهٔ build و نبود `public/hot` قدیمی را بررسی کنید. |
| خطای 419 یا برگشت به صفحهٔ ورود | نشانی سایت ثابت بماند، `SESSION_SECURE_COOKIE=false` و `SESSION_DOMAIN=null` باشد؛ بعد `php artisan config:clear` اجرا و کوکی همین لوکال را پاک کنید. |
| بعضی عکس‌ها 404 می‌شوند | `php artisan media:audit --verify`؛ فایل‌های storage هاست را در مسیر درست کپی کنید. |
| بارگذاری فایل بزرگ ناموفق است | `upload_max_filesize` و `post_max_size` در `php --ini` را بررسی کنید؛ post باید از upload بزرگ‌تر باشد. بعد Artisan را دوباره اجرا کنید. |
| پورت 8000 اشغال است | پردازش متعلق به پروژه را بررسی کنید یا پورت دیگری انتخاب و همهٔ originهای `.env` را هماهنگ کنید. |

لاگ‌ها با تاریخ روز ساخته می‌شوند:

```bash
ls -lt storage/logs
```

آخرین فایل `laravel-YYYY-MM-DD.log` را باز کنید؛ مسیر یک تاریخ قدیمی را ثابت فرض نکنید. برای دیدن تنظیمات جدید:

```bash
php artisan config:clear
php artisan view:clear
```

این دستورها را بعد از ساخت/import دیتابیس می‌توانید با `php artisan optimize:clear` نیز انجام دهید. اگر یک خطای واقعی مشاهده کردید، متن exception و آخرین لاگ را بررسی کنید؛ فعال کردن `APP_DEBUG=true` فقط برای همین لوکال است.

## ۱۰. به‌روزرسانی‌های بعدی

برای دریافت تغییرات کد در همین نصب محلی:

```bash
git pull --ff-only
composer install
npm ci
rm -f public/hot
npm run build
php artisan optimize:clear
php artisan migrate
```

قبل از build، Vite را متوقف کنید. `.env` محلی را دوباره از مثال کپی نکنید، APP_KEY جدید نسازید و SQL را با هر pull دوباره import نکنید. فایل‌های آپلودی و دیتابیس محلی باید باقی بمانند. اگر Git به‌دلیل تغییرات خودتان pull را متوقف کرد، ابتدا آن تغییرات را بررسی و نگه‌داری کنید.

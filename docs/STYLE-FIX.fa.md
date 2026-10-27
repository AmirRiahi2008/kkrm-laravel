# رفع بارگذاری نشدن استایل‌ها

محتوای بستهٔ اصلاحیه را داخل ریشهٔ پروژه، کنار فایل `artisan`، با حفظ مسیر پوشه‌ها کپی و فایل‌های هم‌نام را جایگزین کنید. فایل `.env` و دیتابیس شما در این بسته وجود ندارند و تغییر نمی‌کنند.

فایل‌های اصلی اصلاح‌شده:

- `app/Providers/AppServiceProvider.php`: تولید نشانی فایل‌ها بر اساس دامنه و پورت درخواست، بدون اجبار به نشانی قدیمی APP_URL.
- `app/Http/Middleware/SecurityHeaders.php`: مجاز شدن CSS، JS و WebSocket مبدأ فعال Vite فقط در حالت local.
- `vite.config.js`: اجرای Vite روی 127.0.0.1.
- `public/build`: خروجی آمادهٔ CSS و JavaScript به همراه manifest هماهنگ.

اصلاح قبلی `config/view.php` و پوشه‌های لازم کش Blade نیز برای کامل بودن این اصلاحیه موجودند.

## اجرای محلی با خروجی آماده

در `.env` این مقادیر را تنظیم کنید و سایر تنظیمات دیتابیس و APP_KEY خود را نگه دارید:

```dotenv
APP_ENV=local
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://127.0.0.1:8000
ASSET_URL=
SESSION_SECURE_COOKIE=false
```

اگر `npm run dev` در حال اجراست آن را با Ctrl+C متوقف کنید. داخل ریشهٔ پروژه اجرا کنید:

```bash
php -r 'if (is_file("public/hot")) { unlink("public/hot"); }'
php artisan config:clear
php artisan view:clear
php artisan serve --host=127.0.0.1 --port=8000
```

سایت را از `http://127.0.0.1:8000` باز کنید و یک بار با Cmd+Shift+R در مک یا Ctrl+Shift+R در ویندوز بارگذاری مجدد کنید. برای استفاده از خروجی آمادهٔ این بسته، اجرای npm الزامی نیست.

## ویرایش CSS و JavaScript

برای ویرایش زنده ابتدا `npm ci` و سپس `npm run dev` را در ترمینال دوم اجرا کنید. ترمینال `php artisan serve` هم باید باز بماند. در این روش APP_ENV باید local باشد.

برای تولید خروجی نهایی، ابتدا Vite در حال توسعه را متوقف و سپس اجرا کنید:

```bash
npm run build
```

تمام پوشهٔ `public/build` را یک‌جا منتقل کنید. فایل `public/hot` را به سرور منتقل نکنید. روی سرور وب‌سرور باید پوشهٔ `public` را سرو کند.

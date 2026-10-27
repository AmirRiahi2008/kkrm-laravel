# استقرار Laravel

بک‌اند آماده است؛ دامنه، سرور، SMTP، ClamAV، داده واقعی و پشتیبان‌گیری باید در محیط مقصد تنظیم شوند. در این تحویل هیچ دامنه یا سرویس واقعی منتشر نشده است.

## سرور

PHP 8.3+ با FPM، Composer 2، MySQL 8+، وب‌سرور Nginx و HTTPS نیاز است. دیتابیس محلی نیز MySQL با نام `kkr` است. اطلاعات حساب دیتابیس در env تنظیم شود.

نمونه افزونه‌های Ubuntu: php-fpm، php-mysql، php-mbstring، php-xml، php-curl، php-intl، php-gd، php-zip. برای فایل PDF/MP4، clamav و پایگاه امضا به‌روز لازم است. مسیر binary را در CLAMAV_BINARY قرار دهید و دسترسی خواندن فایل موقت برای FPM را بررسی کنید.

نمونه env در `.env.production.example` است. رمزها و APP_KEY باید مختص سرور باشند.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.production.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=SiteStructureSeeder --force
php artisan app:create-admin admin@your-domain.ir --name="مدیر روابط عمومی"
php artisan optimize
```

بعد از کپی env و قبل از key:generate، اطلاعات دیتابیس و آدرس‌ها را تنظیم کنید. app:create-admin رمز را مخفی و دوباره برای تأیید می‌پرسد. رمز را به آرگومان خط فرمان یا Git اضافه نکنید.

DatabaseSeeder/DemoSeeder مخصوص محیط محلی هستند؛ اجرای آن‌ها در production مسدود است. در production فقط SiteStructureSeeder منوها، مسیرهای قدیمی، لینک‌های بیرونی و اطلاعات پایه سایت را بدون حساب آزمایشی ایجاد می‌کند؛ صفحات و اسناد واقعی را باید مدیر وارد کند.

تمام فایل‌های برنامه خارج از document root باشند؛ فقط public/ سرو شود. فایل env، vendor، database و storage نباید از وب قابل دانلود باشند. نمونه Nginx در `deploy/nginx.conf` است و مسیر ریشه، PHP socket و TLS certificate آن باید تنظیم شود.

storage و bootstrap/cache باید برای کاربر FPM قابل نوشتن باشند؛ مجوز 777 لازم نیست. تصاویر/فایل‌ها در storage/app/private می‌مانند. storage:link برای این رسانه‌ها لازم نیست.

## صف و زمان‌بندی

Worker باید با Supervisor/systemd مدیریت شود:

```bash
php artisan queue:work --tries=3 --timeout=60 --max-time=3600
```

APP_KEY برای رمزگشایی payloadهای صف و نشست لازم است و در هر استقرار دوباره ساخته نمی‌شود. پس از تغییر کد یا تنظیمات:

```bash
php artisan optimize
php artisan queue:restart
```

نمونه Supervisor در deploy/queue-worker.conf قرار دارد. scheduler را هر دقیقه فراخوانی کنید:

```cron
* * * * * cd /var/www/kkrm-backend && php artisan schedule:run >> /dev/null 2>&1
```

فرمان `app:prune-private-data --dry-run` تعداد رکوردهای قابل پاک‌سازی را بدون حذف نشان می‌دهد. اجرای عادی پیام‌های بسته‌شده قدیمی و گزارش عملیات قدیمی را طبق retention حذف می‌کند؛ پیام‌های باز حفظ می‌شوند.

## تنظیم‌های مهم

APP_ENV=production، APP_DEBUG=false، HTTPS و SESSION_SECURE_COOKIE=true تنظیم شوند. مبداهای CORS و SANCTUM_STATEFUL_DOMAINS فقط دامنه‌های منتخب باشند. TRUSTED_PROXIES فقط IP/CIDR پراکسی واقعی باشد؛ در اتصال مستقیم خالی می‌ماند.

CLAMAV_ENABLED=true و امضاهای به‌روز برای آپلود PDF/MP4 لازم است. کد سالم بودن ابزار را بررسی می‌کند؛ فایل مشکوک 422 و اسکن ناموفق 503 می‌دهد. تصویر دوباره رمزگذاری می‌شود. سامانه در production بدون اسکنر PDF/MP4 قبول نمی‌کند.

SMTP واقعی و MAIL_FROM_ADDRESS معتبر تنظیم کنید و worker فعال باشد. در محیط محلی mail log صرفاً برای تست است.

تعداد درخواست عمومی ۱۲۰ در دقیقه برای IP، جستجو/محاسبه ۳۰، تماس ۳، آپلود ۱۵ و ورود ۵ در دقیقه برای ایمیل+IP با سقف ۲۰ برای IP است. محدودیت‌های زیرساخت برای دامنه و اندازه آپلود با این مقادیر هماهنگ شوند. سقف Nginx/PHP باید بیش از سقف مجاز درخواست آپلود باشد؛ upload_max_filesize=50M و post_max_size=55M پیشنهاد عملی این پیکربندی است.

cache تجمیعی صفحه اول/منو/تنظیمات ۶۰ ثانیه است و تغییرات محتوا آن را خالی می‌کنند. درخواست‌های مدیریت و خطاها private/no-store هستند.

## داده و نگهداری

صفحات حقوقی و فرم‌ها باید از فایل/متن تأییدشده رسمی بارگذاری شوند. اطلاعات کارشناس با رضایت انتشار تماس وارد شود. شماره حساب‌های واقعی قبل از فعال‌سازی بررسی شوند. تعرفه واقعی به مرجع و شماره تأیید نیاز دارد و تمام شرایط حقوقی پرونده خارج از این موتور باید بررسی شود.

از دیتابیس، storage/app/private و APP_KEY پشتیبان رمزگذاری‌شده نگه دارید. APP_KEY را خارج از نسخه سورس نگهداری کنید. بازیابی backup، ارسال ایمیل، آپلود فایل اسکن‌شده و ورود/خروج را روی staging همان زیرساخت بررسی کنید.

Health endpoint `/up` صرفاً سلامت بوت برنامه را نشان می‌دهد؛ مانیتور صف، SMTP، دیتابیس، فضای ذخیره و اسکنر را در زیرساخت مقصد اضافه کنید. نتیجه تست محلی جایگزین تست استقرار و ارزیابی نفوذ مستقل نیست.

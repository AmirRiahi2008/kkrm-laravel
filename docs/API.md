# قرارداد API

پیشوند `/api/v1` است. API فقط JSON است و `Accept: application/json` ارسال شود. فایل‌ها از مسیر رسانه به‌صورت باینری دریافت می‌شوند. `/sanctum/csrf-cookie` خارج از پیشوند نسخه و با پاسخ 204 است.

## پاسخ‌ها

```json
{
  "success": true,
  "data": [],
  "pagination": {
    "page": 1,
    "per_page": 15,
    "total": 48,
    "last_page": 4
  }
}
```

`pagination` فقط در فهرست صفحه‌بندی‌شده وجود دارد. جزئیات، صفحه اول و داده تجمیعی در `data` قرار می‌گیرند.

```json
{
  "success": false,
  "message": "اطلاعات ارسال‌شده معتبر نیست.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

errors فقط در خطای اعتبارسنجی وجود دارد. متن اصلی خطا فارسی است؛ برخی پیام‌های قواعد پیش‌فرض Laravel انگلیسی‌اند.

| وضعیت | معنی |
|---|---|
| 200 / 201 | موفق / ایجاد |
| 401 | مهمان، کاربر غیرفعال یا نشست لغوشده |
| 403 | مجوز کافی وجود ندارد |
| 404 | وجود ندارد یا برای بازدیدکننده قابل مشاهده نیست |
| 409 | تداخل نسخه خبر یا اطلاعات یکتا |
| 419 | CSRF نامعتبر یا نشست منقضی |
| 422 | ورودی نامعتبر |
| 429 | محدودیت درخواست |
| 500 / 503 | خطای داخلی / فایل‌سیستم یا اسکنر در دسترس نیست |

## احراز هویت داشبورد

ثبت‌نام عمومی و ورود کارشناسان در API وجود ندارد. کاربران داشبورد را فقط مدیر ارشد ایجاد می‌کند. ورود روی سایت عمومی صرفاً لینک سامانه خارجی است.

`GET /sanctum/csrf-cookie`، سپس `POST /api/v1/auth/login` با email/password. کوکی نشست HttpOnly است. مقدار کوکی `XSRF-TOKEN` را URL-decode کنید و در `X-XSRF-TOKEN` بفرستید. `credentials: include` لازم است.

| روش | مسیر | داده |
|---|---|---|
| POST | auth/login | email, password |
| GET | auth/me | مشخصات کاربر جاری |
| POST | auth/logout | بدون body ضروری |
| PUT | auth/password | current_password, password, password_confirmation |
| POST | auth/forgot-password | email |
| POST | auth/reset-password | email, token, password, password_confirmation |

حداقل طول رمز جدید ۱۲ است و حروف بزرگ/کوچک، عدد و نماد لازم دارد. بازیابی رمز در صف رمزگذاری‌شده انجام می‌شود و برای ایمیل شناخته‌شده و ناشناخته یک پاسخ عمومی دارد. توکن ۶۰ دقیقه اعتبار دارد و یک‌بار مصرف است.

## منابع عمومی

`GET /{resource}` فهرست و `GET /{resource}/{id-or-slug}` جزئیات است. منابع عمومی:

`posts, categories, tags, pages, documents, expert-groups, disciplines, qualifications, experts, board-terms, board-members, agreements, albums, events, faqs, slides, links, bank-accounts, tariffs`.

نوع‌های خبر: `news, article, announcement, resolution, training, welfare, condolence, group_event`.

نوع‌های سند: `law, regulation, statute, directive, circular, form, qualification_table, tariff`.

منابع دارای وضعیت انتشار فقط با `status=published` و تاریخ انتشار غیرآینده عمومی‌اند. محتوای منقضی‌شده خبر، پیش‌نویس و حذف‌شده نمایش داده نمی‌شوند. کارشناس فقط با `status=active`، `is_published=true` و رشته/گروه فعال نمایش دارد. اطلاعات تماس کارشناس فقط با `show_contact=true` عمومی می‌شود.

فیلترهای مشترک `page`، `per_page` حداکثر ۵۰، `q`، `type`، `group`، `role`، `sort` هستند و فقط در منبع مربوط اعمال می‌شوند. `sort` یکی از latest/oldest/title است. داده‌های دارای sort_order با ترتیب مدیریتی نمایش داده می‌شوند.

فیلتر خبر: `category` و `tag` با slug و `from`/`to` با تاریخ. فیلتر کارشناس: `first_name, last_name, license_number, city, geographical_scope, discipline_id, expert_group_id`. فیلتر اعضای هیئت: `board_term_id` و `role`. نقش اعضا: board/inspector/prosecutor/former_president.

```text
GET /api/v1/posts?type=news&per_page=12&page=2
GET /api/v1/posts?type=article&category=article
GET /api/v1/documents?type=form
GET /api/v1/experts?city=ساری&expert_group_id=1
GET /api/v1/board-members?role=former_president
GET /api/v1/links?group=systems
```

## صفحه اول و مسیرهای ویژه

`GET /home` شامل slides، featured_posts، sections، events، albums، systems و board_members است. هر بخش خبری تا ۶ مطلب و `archive_path` دارد تا فرانت دکمه «مشاهده همه» را بسازد. فهرست کامل از posts دریافت می‌شود.

`GET /navigation` درخت منو با header/mobile/footer/quick است. `GET /settings` فقط تنظیمات مجاز عمومی را می‌دهد. لوگو و favicon در صورت تنظیم، شناسه رسانه‌اند و فایل از `GET /media/{id}` دریافت می‌شود.

`GET /search?q=...` برای آیکون جستجوی سایت فقط **اخبار، اطلاعیه‌ها و مصوبات** را جستجو می‌کند. عنوان، خلاصه و متن بررسی می‌شوند؛ «ي/ی» و «ك/ک» یکسان‌اند. حداقل دو حرف بعد از یکسان‌سازی و حداکثر ۱۵۰ کاراکتر مجاز است. مقاله، صفحه، سند حقوقی و پروفایل کارشناس در این خروجی نیستند. پیش‌نویس، تاریخ آینده، محتوای منقضی و حذف‌شده نیز نمایش داده نمی‌شوند.

پارامترهای `page` و `per_page` برای هر گروه اعمال می‌شوند؛ پیش‌فرض ۱ و ۵ و سقف اندازه صفحه ۵۰ است. `type` اختیاری و فقط یکی از `news/announcement/resolution` است؛ در این حالت فقط همان گروه برمی‌گردد. `total` مجموع تعداد گروه‌های برگشتی است. `archive_path` برای «مشاهده همه نتایج» داده می‌شود؛ برای ادامه هر گروه می‌توان `type` و `page` را به همین endpoint فرستاد.

```json
{
  "success": true,
  "data": {
    "query": "آزمایشی",
    "total": 30,
    "results": {
      "news": {
        "total": 10,
        "items": [],
        "pagination": { "page": 1, "per_page": 5, "total": 10, "last_page": 2 },
        "archive_path": "/news?type=news&q=%D8%A2%D8%B2%D9%85%D8%A7%DB%8C%D8%B4%DB%8C"
      },
      "announcement": { "total": 10, "items": [], "pagination": { "page": 1, "per_page": 5, "total": 10, "last_page": 2 }, "archive_path": "/news?type=announcement&q=..." },
      "resolution": { "total": 10, "items": [], "pagination": { "page": 1, "per_page": 5, "total": 10, "last_page": 2 }, "archive_path": "/news?type=resolution&q=..." }
    }
  }
}
```

آرایه‌های `items` در مثال کوتاه شده‌اند؛ در پاسخ واقعی خلاصه مطالب با schema `Post` قرار دارند.

`GET /seo/sitemap` JSON شامل path و updated_at است؛ Next.js از آن XML/metadata route می‌سازد.

`GET /redirects/resolve?path=%2F%3Fpage_id%3D977` مسیر جدید و status_code را می‌دهد؛ خود بک‌اند redirect HTTP روی این endpoint انجام نمی‌دهد. فرانت باید در middleware یا route handler نتیجه را اعمال کند.

`POST /contact` نام، ایمیل، موضوع و متن ۱۰ تا ۵۰۰۰ کاراکتر می‌گیرد. website فیلد تله اسپم است و باید خالی بماند. پیام‌ها عمومی نیستند.

## مدیریت

`GET/POST /admin/{resource}` و `GET/PUT/PATCH/DELETE /admin/{resource}/{id}` برای همه منابع مدیریتی مجاز است. لیست کامل schema هر منبع در openapi.json است. در ویرایش جزئی فقط فیلدهای تغییرکرده ارسال شوند.

`POST /admin/{resource}/{id}/restore` برای مدیر است. `trash=true` موارد نرم‌حذف‌شده را فهرست می‌کند. slug/code یکتا پس از حذف نرم قابل استفاده مجدد نیست تا لینک و بازیابی قبلی محفوظ بماند.

منابع مدیریتی علاوه بر منابع عمومی، `menu-items` و `redirects` را دارند. نویسنده به posts/media و داشبورد خودش محدود است.

```json
{
  "title": "خبر آزمایشی",
  "slug": "sample-news",
  "type": "news",
  "body": "<p>متن خبر</p>",
  "status": "draft",
  "tag_ids": [],
  "attachment_ids": []
}
```

در ویرایش خبر، lock_version آخرین پاسخ مدیریتی الزامی است:

```json
{
  "title": "عنوان جدید",
  "lock_version": 1
}
```

پاسخ نسخه را افزایش می‌دهد. در 409 جزئیات خبر را دوباره بگیرید و تغییرات را با انتخاب کاربر ادغام کنید. `GET /admin/posts/{id}/revisions` نسخه‌های پیشین را می‌دهد.

مدیر انتشار را با status=published انجام می‌دهد. published_at خالی هنگام انتشار به زمان جاری تبدیل می‌شود؛ تاریخ آینده انتشار زمان‌بندی‌شده است. برای نمایش زمان‌بندی‌شده نیاز به job انتشار نیست چون query زمان را بررسی می‌کند؛ cache صفحه اول حداکثر ۶۰ ثانیه عمر دارد.

کاربران: `GET/POST /admin/users` و `GET/PUT/PATCH /admin/users/{id}`. حذف کاربر موجود نیست؛ is_active=false. تغییر email، role، is_active یا password نشست‌های آن کاربر را لغو می‌کند. مدیر ارشد دسترسی خودش یا آخرین مدیر فعال را کاهش نمی‌دهد.

دیگر مسیرها: `admin/dashboard`، `admin/audit-logs`، `admin/contact-messages` و `admin/settings`. پیام تماس حذف دائمی دارد و صرفاً برای مدیران در دسترس است.

## رسانه و آلبوم

`POST /admin/media` با multipart/form-data، file و alt. پاسخ id و URL می‌دهد. Content-Type را برای FormData دستی نگذارید. فایل صرفاً در صورت اتصال به محتوای عمومی منتشرشده برای مهمان دانلود می‌شود؛ مدیر یا مالک فعال می‌تواند فایل خصوصی خود را ببیند.

فرمت‌های مجاز JPEG/PNG/WebP/PDF/MP4، سقف فایل ۵۰MiB و سقف تصویر ۱۰MiB است. تصویر تا ۸۱۹۲ در هر ضلع و ۲۴ میلیون پیکسل پذیرفته می‌شود. PDF با Content-Disposition=attachment ارائه می‌شود. حذف فایل ارجاع‌شده خطای 409 دارد.

`PUT /admin/albums/{id}/items` کل آیتم‌ها را به‌صورت اتمیک جایگزین می‌کند؛ آرایه خالی آلبوم را خالی می‌کند. حداکثر ۱۰۰ آیتم، شناسه بدون تکرار، و caption/sort_order اختیاری است. آلبوم photo فقط تصویر و video فقط MP4 می‌پذیرد.

```json
{
  "items": [
    {"media_id": 1, "caption": "عنوان تصویر", "sort_order": 0}
  ]
}
```

## محاسبه تعرفه

`POST /tariffs/calculate` با value به ریال، expert_count بین ۱ تا ۵، tariff_id اختیاری، date اختیاری میلادی YYYY-MM-DD تا امروز. آخرین تعرفه فعال معتبر برای تاریخ انتخاب می‌شود. پاسخ شامل segments، total، is_demo و is_estimate است.

پله‌ها روی بخش ارزش هر بازه اعمال می‌شوند؛ کسر ریال رو به بالا گرد می‌شود، سپس ضریب هیئت و حداقل/حداکثر اعمال می‌شود. تنها پله آخر سقف null دارد و سقف‌ها صعودی‌اند. ضریب یک کارشناس ۱۰۰۰۰ است.

مقادیر نمونه برای تست‌اند. فقط مدیر ارشد با source_url و approval_reference می‌تواند is_demo=false کند. این اطلاعات به‌معنای تأیید خودکار صحت حقوقی نرخ نیست؛ مسئول محتوا باید مرجع واقعی را بررسی کند.

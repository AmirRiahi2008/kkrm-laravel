# نگاشت سایت اصلی

بررسی در ۱ اکتبر ۲۰۲۶ روی [kkrm.ir](https://kkrm.ir/) انجام شد. [karshenasan.ir](https://karshenasan.ir/) برای الگوی بخش‌های خبری و آرشیو بررسی شد. فایل آپلودشده فرانت خوانده شد و تغییری در آن انجام نشد.

عنوان‌ها و منوها از سایت اصلی گرفته شده‌اند؛ متن حقوقی و داده شخصی واقعی کپی نشده است. داده داخل Seed به‌وضوح آزمایشی است. فهرست لینک‌های استخراج‌شده در source-links.json نگهداری شده؛ بخش‌های انگلیسی قالب و لینک طراح سایت جزو قابلیت کانون محسوب نشده‌اند.

| گزینه اصلی | منبع / مسیر جدید |
|---|---|
| سامانه ثبت فیش و پنل کارشناسان | لینک https://my.kkrm.ir/ |
| سامانه ۲۰۲۰ | لینک https://2020kanoon.ir/ |
| آموزش مجازی | لینک https://event.alocom.co/class/kkrm/webinar |
| تاریخچه کانون، page_id=977 | pages/history |
| هیئت مدیره، بازرسان و دادستان، page_id=980 | board-members + board-terms |
| گروه‌های یازده‌گانه، page_id=982 | expert-groups + disciplines |
| گالری تصاویر، page_id=1010 | albums |
| فرم‌های موردنیاز، page_id=1841 | documents?type=form |
| عناوین صلاحیت، page_id=988 | qualifications + documents/qualification-titles |
| جستجوی کارشناس، page_id=1567 | experts با فیلتر نام، پروانه، رشته، گروه و شهر |
| سامانه کارآموزان | لینک https://karamooz.kkrm.ir/login |
| قراردادها، page_id=4109 | agreements |
| شرح تعرفه، page_id=1032 | documents/tariff-description |
| محاسبه آنلاین، page_id=1034 | tariffs + POST tariffs/calculate |
| شماره حساب، page_id=2610 | bank-accounts |
| تماس، page_id=18 | settings + contact |
| آرشیو خبرها، page_id=58 | posts و مسیر فرانت /news |
| خدمات قضایی | لینک https://adliran.ir/ |
| ایمیل و شماره تماس | settings |
| پیوندهای مفید | links?group=useful |
| افزایش صلاحیت در فوتر | documents/increase-qualification |

محاسبه آنلاین اصلی هنگام بررسی پاسخ 404 داشت. ساختار محاسبه جدید پیاده‌سازی شده ولی ضرایب رسمی همه حالات حقوقی از صفحه اصلی قابل بازیابی نبود؛ نرخ Seed نمونه است.

## اسناد موجود در منوی سایت

| عنوان و لینک اصلی | نوع | مسیر جدید |
|---|---|---|
| [قانون کارشناسان رسمی دادگستری](https://kkrm.ir/?page_id=984) | law | `/documents/experts-law` |
| [آیین‌نامه اجرایی قانون](https://kkrm.ir/?page_id=986) | regulation | `/documents/executive-regulation` |
| [آیین‌نامه رسیدگی به تخلفات کارشناسان](https://kkrm.ir/?page_id=1207) | regulation | `/documents/disciplinary-regulation` |
| [نظام‌نامه نقل و انتقالات کارشناسان](https://kkrm.ir/?page_id=1154) | statute | `/documents/transfer-statute` |
| [نظام‌نامه توسعه حوزه جغرافیایی و فعالیت خدمات کارشناسی](https://kkrm.ir/?page_id=1159) | statute | `/documents/geographical-scope` |
| [نظام‌نامه مالی و معاملاتی کانون‌ها](https://kkrm.ir/?page_id=1164) | statute | `/documents/financial-statute` |
| [نظام‌نامه اعطای صلاحیت‌های کارشناسی](https://kkrm.ir/?page_id=1176) | statute | `/documents/qualification-statute` |
| [نظام‌نامه هیئت رئیسه گروه‌های کارشناسی](https://kkrm.ir/?page_id=5570) | statute | `/documents/group-presidium` |
| [دستورالعمل تهیه گزارش و نظریه کارشناسی](https://kkrm.ir/?page_id=4658) | directive | `/documents/expert-report-guide` |
| [نظام‌نامه کارآموزی کارشناسان](https://kkrm.ir/?page_id=1194) | statute | `/documents/apprenticeship-statute` |
| [دستورالعمل نقل و انتقال کارشناسان](https://kkrm.ir/?page_id=1213) | directive | `/documents/transfer-guide` |
| [نظام‌نامه کمیسیون‌های تشخیص صلاحیت علمی و فنی](https://kkrm.ir/?page_id=1184) | statute | `/documents/qualification-commissions` |
| [بخشنامه هزینه خدمات کانون](https://kkrm.ir/?page_id=4441) | circular | `/documents/service-fees-circular` |
| [دستورالعمل زمان و فرآیند کارشناسی](https://kkrm.ir/?page_id=993) | directive | `/documents/expert-process-guide` |
| [جدول عناوین صلاحیت‌های کارشناسی](https://kkrm.ir/?page_id=988) | qualification_table | `/documents/qualification-titles` |
| [شرح تعرفه دستمزد کارشناسی](https://kkrm.ir/?page_id=1032) | tariff | `/documents/tariff-description` |

## خبرها و الگوهای صفحه اول

اطلاعیه‌ها، آموزش، رفاه، تسلیت، رویدادهای گروه‌ها، اخبار کانون و مصوبات با نوع‌های posts مدل شده‌اند. مقاله نیز نوع مستقلی دارد. صفحه اول برای هر نوع شش مطلب و archive_path می‌دهد. فرانت می‌تواند کارت‌های خبری، دکمه «مشاهده همه»، خبر شاخص و آرشیو صفحه‌بندی‌شده را از همین خروجی بسازد.

ورود کارشناسان خارجی است؛ حساب داخلی برای نویسنده و مدیر روابط عمومی است. لینک‌ها در دیتابیس قابل مدیریت‌اند تا تغییر سامانه بیرونی به تغییر کد نیاز نداشته باشد.

## مرجع‌های فنی

- [Laravel 13](https://laravel.com/docs/13.x/releases)
- [Sanctum و احراز هویت SPA](https://laravel.com/docs/13.x/sanctum)
- [CSRF](https://laravel.com/docs/13.x/csrf)
- [OpenAPI 3.0.3](https://spec.openapis.org/oas/v3.0.3.html)

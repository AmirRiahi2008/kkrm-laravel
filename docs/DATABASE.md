# ساختار دیتابیس

دیتابیس محلی MySQL 8+ با نام `kkr` است. اتصال پیش‌فرض `mysql`، میزبان `127.0.0.1`، پورت `3306` و charset/collation برابر `utf8mb4` / `utf8mb4_unicode_ci` است. اطلاعات حساب MySQL در `.env` تنظیم می‌شود. شناسه‌ها BIGINT هستند و کلیدهای خارجی در MySQL فعال‌اند.

users برای روابط عمومی است و experts صرفاً فهرست عمومی کارشناسان است؛ بین این دو ورود مشترک وجود ندارد.

| جدول | منبع API | فیلدهای کاربردی |
|---|---|---|
| categories | categories | name, slug, parent_id, is_active, sort_order |
| tags | tags | name, slug |
| pages | pages | title, slug, body, cover_id, status, published_at, meta_title, meta_description |
| documents | documents | title, slug, type, description, body, file_id, source_url, document_number, issued_at, is_demo, status, published_at, meta_title, meta_description |
| expert_groups | expert-groups | name, slug, code, description, is_active, sort_order |
| disciplines | disciplines | expert_group_id, name, code, is_active, sort_order |
| qualifications | qualifications | discipline_id, title, code, description, is_active, sort_order |
| experts | experts | discipline_id, photo_id, first_name, last_name, license_number, city, geographical_scope, phone, email, show_contact, is_published, status, biography |
| board_terms | board-terms | title, started_at, ended_at, is_active, sort_order |
| board_members | board-members | board_term_id, photo_id, name, position, role, biography, is_active, sort_order |
| agreements | agreements | title, slug, partner, body, file_id, cover_id, starts_at, ends_at, is_demo, status, published_at |
| albums | albums | title, slug, description, cover_id, type, event_date, status, published_at |
| events | events | title, slug, body, cover_id, mode, location, starts_at, ends_at, registration_url, status, published_at, meta_title, meta_description |
| faqs | faqs | question, answer, group, is_active, sort_order |
| slides | slides | title, image_id, target_url, starts_at, ends_at, is_active, sort_order |
| external_links | links | title, code, url, group, icon, is_active, sort_order |
| menu_items | menu-items | location, title, url, parent_id, open_new_tab, is_active, sort_order |
| bank_accounts | bank-accounts | bank_name, holder, account_number, iban, card_number, purpose, is_demo, is_active, sort_order |
| tariffs | tariffs | name, slug, currency, effective_from, effective_to, source_url, approval_reference, rules, is_demo, is_active, sort_order |
| legacy_redirects | redirects | old_path, new_path, status_code, is_active, sort_order |

| جدول مکمل | کاربرد |
|---|---|
| users | نقش، فعال‌بودن، رمز هش‌شده، session_version و ورود اخیر |
| media | دیسک خصوصی، مسیر تصادفی، MIME واقعی، اندازه، SHA256 و وضعیت اسکن |
| posts | نویسنده، دسته، کاور، وضعیت، زمان انتشار/انقضا و lock_version |
| post_tag | رابطه چندبه‌چند مطلب و برچسب با کلید مرکب |
| post_media | پیوست‌های مطلب با کلید مرکب |
| post_revisions | snapshot پیش از هر ویرایش همراه شناسه ویراستار |
| expert_qualification | رابطه کارشناس و صلاحیت با کلید مرکب |
| album_items | فایل، توضیح و ترتیب آلبوم؛ فایل تکراری در آلبوم ممنوع |
| settings | کلید مجاز و مقدار JSON تایپ‌شده |
| contact_messages | پیام خصوصی و یادداشت داخلی؛ بدون خروجی عمومی IP |
| audit_logs | عمل، عامل، نوع/شناسه رکورد و نام فیلدهای تغییرکرده |
| sessions | نشست‌های دیتابیسی |
| password_reset_tokens | توکن هش‌شده بازیابی |
| cache, cache_locks | cache و lock |
| jobs, job_batches, failed_jobs | صف و وضعیت شکست |

اسناد، صفحات، اخبار، قراردادها، آلبوم و رویداد دارای وضعیت انتشارند. بیشتر منابع مدیریتی و رسانه حذف نرم دارند. پیام تماس حذف دائمی دارد. users حذف API ندارند و غیرفعال می‌شوند.

روابط کلیدی: رشته به گروه، صلاحیت و کارشناس به رشته؛ کارشناس به صلاحیت‌ها؛ عضو هیئت به دوره؛ رسانه به بارگذار و منابع محتوا؛ آلبوم به آیتم‌ها؛ مطلب به نویسنده/دسته/برچسب/پیوست/نسخه‌های قبلی.

کلیدهای خارجی و indexهای جستجوی وضعیت/نوع/تاریخ/شهر وجود دارند. slug، code، license_number، مسیر فایل و مسیر قدیمی یکتا هستند. حذف نرم یکتایی را آزاد نمی‌کند.

قواعد چندرکوردی در service و transaction بررسی می‌شوند: صلاحیت باید متعلق به رشته کارشناس باشد؛ منو بدون حلقه و تا چهار سطح باشد؛ زیرمنوها location مشترک داشته باشند؛ پایان رویداد بعد از آغاز باشد؛ نوع آلبوم با فایل هماهنگ باشد؛ پله تعرفه صعودی و آخرین پله نامحدود باشد.

در ویرایش خبر، row lock و optimistic lock_version مانع ذخیره با نسخه قدیمی می‌شود. نسخه قبلی و روابط پیوست/برچسب در snapshot ثبت می‌شوند.

## متن جستجو

ستون داخلی `posts.search_text` از عنوان، خلاصه و متن بدون HTML ساخته می‌شود. حروف فارسی/عربی و Unicode یکسان‌سازی می‌شوند. هنگام ذخیره محتوا این ستون به‌روز می‌شود؛ migration جدید محتوای قبلی را نیز آماده می‌کند. این فیلد در API بازگردانده نمی‌شود و ورودی قابل ویرایش کاربر نیست. جستجوی آیکون سایت فقط نوع‌های `news`، `announcement` و `resolution` را برمی‌گرداند.

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models;
use App\Services\MediaService;
use App\Support\ContentTypes;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Demo data must not be installed in production.');
        }
        [$admin, $manager, $editor] = $this->createUsers();
        $media = $this->createMedia($admin);
        $this->createSettingsAndLinks($media);
        $this->createPosts($manager, $editor, $media);
        $this->createPages();
        $this->createDocuments($media);
        $this->createExpertDirectory();
        $this->createBoard();
        $this->createAgreementsAndGalleries($media);
        $this->createEvents($media);
        $this->createFaqs();
        $this->createAccountsAndMessages();
        $this->createTariffs();
    }

    private function createUsers(): array
    {
        $admin = Models\User::firstOrCreate(['email' => 'admin@example.test'], ['name' => 'مدیر ارشد نمونه', 'password' => 'DemoAdmin!2026', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
        $manager = Models\User::firstOrCreate(['email' => 'relations@example.test'], ['name' => 'مدیر روابط عمومی نمونه', 'password' => 'DemoManager!2026', 'role' => UserRole::RelationsManager, 'is_active' => true]);
        $editor = Models\User::firstOrCreate(['email' => 'editor@example.test'], ['name' => 'نویسنده روابط عمومی نمونه', 'password' => 'DemoEditor!2026', 'role' => UserRole::Editor, 'is_active' => true]);
        for ($i = 4; $i <= 10; $i++) {
            Models\User::firstOrCreate(['email' => 'staff'.$i.'@example.test'], [
                'name' => 'عضو نمونه روابط عمومی '.$i, 'password' => Str::random(40),
                'role' => UserRole::Editor, 'is_active' => false,
            ]);
        }

        return [$admin, $manager, $editor];
    }

    private function createMedia(Models\User $admin): array
    {
        $media = [];
        foreach (['news.jpg', 'event.jpg', 'gallery.jpg', 'logo.png', 'sample.pdf', 'demo-photo-1.jpg', 'demo-photo-2.jpg', 'demo-photo-3.jpg', 'demo-document.pdf'] as $filename) {
            $source = str_starts_with($filename, 'demo-photo-') ? 'gallery.jpg' : ($filename === 'demo-document.pdf' ? 'sample.pdf' : $filename);
            $media[$filename] = Models\Media::where('original_name', $filename)->first() ?? app(MediaService::class)->upload(
                new UploadedFile(database_path('seed-assets/'.$source), $filename, test: true), $admin, 'فایل نمونه آزمایشی',
            );
        }

        return $media;
    }

    private function createSettingsAndLinks(array $media): void
    {
        Models\Setting::firstOrCreate(['key' => 'logo_media_id'], ['value' => $media['logo.png']->id]);
        Models\Setting::firstOrCreate(['key' => 'announcement_ticker'], ['value' => 'این نسخه شامل داده‌های آزمایشی است.']);
        Models\Setting::firstOrCreate(['key' => 'favicon_media_id'], ['value' => $media['logo.png']->id]);
        Models\Setting::firstOrCreate(['key' => 'office_hours'], ['value' => 'ساعت کاری نمونه؛ نیازمند تأیید کانون']);
        foreach (['systems', 'useful'] as $group) {
            $count = Models\ExternalLink::where('group', $group)->count();
            for ($i = $count + 1; $i <= 10; $i++) {
                Models\ExternalLink::firstOrCreate(['code' => 'demo-link-'.$group.'-'.$i], [
                    'title' => 'پیوند آزمایشی '.$i, 'url' => 'https://example.test/demo/'.$group.'/'.$i,
                    'group' => $group, 'is_active' => true, 'sort_order' => $i + 10,
                ]);
            }
        }
    }

    private function createPosts(Models\User $manager, Models\User $editor, array $media): void
    {
        $labels = [
            'news' => 'خبر آزمایشی کانون', 'article' => 'مقاله نمونه درباره تهیه گزارش کارشناسی',
            'announcement' => 'اطلاعیه آزمایشی دوره آموزشی', 'resolution' => 'مصوبه نمونه هیئت مدیره',
            'training' => 'خبر نمونه آموزش کارشناسان', 'welfare' => 'خبر آزمایشی خدمات رفاهی',
            'condolence' => 'پیام همدردی نمونه', 'group_event' => 'نشست آزمایشی گروه کارشناسی',
        ];
        $tag = Models\Tag::firstOrCreate(['slug' => 'demo'], ['name' => 'نمونه آزمایشی']);
        $tags = [$tag->id];
        for ($i = 2; $i <= 10; $i++) {
            $tags[] = Models\Tag::firstOrCreate(['slug' => 'demo-tag-'.$i], ['name' => 'برچسب نمونه '.$i])->id;
        }
        foreach (['demo-general' => 'عمومی آزمایشی', 'demo-administrative' => 'اداری آزمایشی'] as $slug => $name) {
            Models\Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
        foreach ($labels as $type => $title) {
            $category = Models\Category::firstOrCreate(['slug' => $type], ['name' => $title, 'is_active' => true]);
            for ($i = 1; $i <= 10; $i++) {
                $post = Models\Post::firstOrCreate(['slug' => 'demo-'.$type.'-'.$i], [
                    'author_id' => $manager->id, 'category_id' => $category->id, 'cover_id' => $media['news.jpg']->id,
                    'type' => $type, 'title' => $title.' '.$i, 'excerpt' => 'این متن صرفاً برای تست رابط و API نوشته شده است.',
                    'body' => '<p>این یک محتوای کاملاً آزمایشی است و خبر، اطلاعیه یا سند رسمی کانون محسوب نمی‌شود.</p><p>در نسخه نهایی، روابط عمومی محتوای تأییدشده را منتشر می‌کند.</p>',
                    'status' => 'published', 'published_at' => now()->subDays($i),
                    'is_featured' => $i === 1, 'is_pinned' => $type === 'announcement' && $i === 1,
                    'meta_title' => $title, 'meta_description' => 'متادیتای نمونه برای تست اتصال Next.js',
                ]);
                $post->tags()->syncWithoutDetaching([$tag->id, $tags[$i - 1]]);
                $post->attachments()->syncWithoutDetaching([$media['sample.pdf']->id]);
                if ($type === 'news') {
                    Models\PostRevision::firstOrCreate(['post_id' => $post->id, 'editor_id' => $manager->id], ['snapshot' => $post->only($post->getFillable())]);
                }
            }
        }
        foreach (['draft', 'review'] as $status) {
            Models\Post::firstOrCreate(['slug' => 'demo-'.$status], [
                'author_id' => $editor->id, 'type' => 'article', 'title' => 'محتوای داخلی نمونه '.$status,
                'body' => '<p>این پیش‌نویس نباید در API عمومی نمایش داده شود.</p>', 'status' => $status,
            ]);
        }
    }

    private function createPages(): void
    {
        Models\Page::firstOrCreate(['slug' => 'history'], [
            'title' => 'تاریخچه کانون', 'body' => '<p>این صفحه نمونه برای تکمیل بخش تاریخچه است. متن تأییدشده کانون باید جایگزین شود.</p>',
            'status' => 'published', 'published_at' => now()->subDay(),
        ]);
        for ($i = 2; $i <= 10; $i++) {
            Models\Page::firstOrCreate(['slug' => 'demo-page-'.$i], [
                'title' => 'صفحه ثابت آزمایشی '.$i, 'body' => '<p>متن کاملاً آزمایشی برای اتصال سایت و مدیریت صفحات.</p>',
                'status' => 'published', 'published_at' => now()->subDays($i),
            ]);
        }
    }

    private function createDocuments(array $media): void
    {
        $structure = json_decode(file_get_contents(database_path('data/site-structure.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($structure['documents'] as $doc) {
            Models\Document::firstOrCreate(['slug' => $doc['slug']], [
                'title' => $doc['title'], 'type' => $doc['type'],
                'description' => 'محتوای آزمایشی؛ متن و فایل رسمی هنوز مهاجرت نشده است.',
                'body' => '<p>این متن نمونه است و اعتبار قانونی ندارد. سند رسمی باید پس از تأیید کانون بارگذاری شود.</p>',
                'source_url' => 'https://kkrm.ir/?page_id='.$doc['page_id'], 'file_id' => $media['sample.pdf']->id,
                'is_demo' => true, 'status' => 'published', 'published_at' => now()->subDay(),
            ]);
        }
        foreach ([
            ['increase-qualification', 'فرم افزایش صلاحیت کارشناسی'],
            ['welfare-membership', 'فرم عضویت صندوق رفاه'],
            ['national-bank-loan', 'فرم درخواست وام بانک ملی'],
            ['interest-free-loan', 'فرم درخواست وام قرض‌الحسنه'],
            ['loan-survey', 'فرم نظرسنجی دریافت تسهیلات'],
        ] as [$slug, $title]) {
            Models\Document::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'type' => 'form', 'description' => 'فرم آزمایشی و فاقد اعتبار',
                'file_id' => $media['sample.pdf']->id, 'is_demo' => true, 'status' => 'published', 'published_at' => now()->subDay(),
            ]);
        }
        foreach (ContentTypes::DOCUMENTS as $type) {
            $count = Models\Document::where('type', $type)->count();
            for ($i = $count + 1; $i <= 10; $i++) {
                Models\Document::firstOrCreate(['slug' => 'demo-document-'.$type.'-'.$i], [
                    'title' => 'سند آزمایشی '.$type.' '.$i, 'type' => $type,
                    'description' => 'سند نمونه و فاقد اعتبار قانونی',
                    'body' => '<p>این متن آزمایشی است و سند رسمی محسوب نمی‌شود.</p>',
                    'file_id' => $media['demo-document.pdf']->id, 'is_demo' => true,
                    'status' => 'published', 'published_at' => now()->subDays($i),
                ]);
            }
        }
    }

    private function createExpertDirectory(): void
    {
        $groups = [
            ['منابع آب و معادن', ['معادن', 'مواد', 'مهندسی آب']],
            ['ارزیابی اموال منقول', ['آثار هنری', 'اشیای عتیقه', 'ساعت و جواهرات', 'فرش', 'لوازم خانگی']],
            ['امور پزشکی و غذایی', ['پزشکی', 'داروسازی و سم‌شناسی', 'مواد غذایی']],
            ['حسابداری و امور مالی', ['آمار', 'امور بازرگانی', 'تعیین نفقه', 'حسابداری و حسابرسی', 'بیمه']],
            ['حمل و نقل', ['ترابری', 'وسائط دریایی', 'وسائط ریلی', 'وسائط زمینی', 'وسائط هوایی']],
            ['راه و ساختمان', ['آثار باستانی', 'امور ثبتی', 'برنامه‌ریزی شهری', 'راه و ساختمان', 'معماری داخلی', 'ترافیک', 'محیط زیست', 'نقشه‌برداری']],
            ['صنعت و فن', ['الکتروشیمی', 'انرژی هسته‌ای', 'برق و مخابرات', 'تأسیسات کارخانجات', 'تأسیسات ساختمانی', 'گاز', 'نفت', 'رایانه', 'مهندسی پزشکی', 'هوافضا', 'نساجی']],
            ['هنر و رسانه', ['ورزش', 'تألیفات', 'تئاتر و سینما', 'اصالت خط و امضا', 'تمبر', 'چاپ', 'شعر', 'صنایع دستی', 'گرافیک', 'عکاسی', 'موسیقی']],
            ['کشاورزی و منابع طبیعی', ['آبزیان و شیلات', 'دامپروری', 'صنایع چوب', 'کشاورزی', 'گیاه‌پزشکی', 'محصولات دامی', 'محیط زیست طبیعی']],
            ['خدمات اداری و عمومی', ['آموزش', 'امور اداری', 'خبرنگاری', 'گمرک', 'گردشگری', 'ثبت شرکت‌ها', 'زبان‌های خارجی', 'کتابداری']],
            ['ایمنی و حوادث', ['آتش‌نشانی', 'اسلحه‌شناسی', 'امنیت عمومی', 'حوادث کار', 'مواد منفجره']],
        ];
        foreach ($groups as $index => [$name, $names]) {
            $group = Models\ExpertGroup::firstOrCreate(['code' => 'GROUP-'.($index + 1)], ['name' => $name, 'slug' => 'group-'.($index + 1), 'is_active' => true, 'sort_order' => $index]);
            foreach ($names as $j => $name) {
                $discipline = Models\Discipline::firstOrCreate(['code' => 'DISC-'.($index + 1).'-'.($j + 1)], ['expert_group_id' => $group->id, 'name' => $name, 'is_active' => true, 'sort_order' => $j]);
                $qualification = Models\Qualification::firstOrCreate(['code' => 'QUAL-'.$discipline->code], ['discipline_id' => $discipline->id, 'title' => 'صلاحیت آزمایشی رشته '.$name, 'is_active' => true]);
                if ($j === 0 && $index < 10) {
                    $expert = Models\Expert::firstOrCreate(['license_number' => 'DEMO-'.($index + 1)], [
                        'discipline_id' => $discipline->id, 'first_name' => 'کارشناس', 'last_name' => 'نمونه '.($index + 1),
                        'city' => ['ساری', 'بابل', 'آمل'][$index % 3], 'geographical_scope' => 'استان مازندران',
                        'phone' => '00000000000', 'email' => 'expert'.($index + 1).'@example.test',
                        'show_contact' => false, 'is_published' => true, 'status' => 'active',
                        'biography' => 'هویت کاملاً آزمایشی؛ این شخص کارشناس واقعی نیست.',
                    ]);
                    $expert->qualifications()->syncWithoutDetaching([$qualification->id]);
                }
            }
        }
    }

    private function createBoard(): void
    {
        for ($i = 2; $i <= 10; $i++) {
            Models\BoardTerm::firstOrCreate(['title' => 'دوره پیشین آزمایشی '.$i], [
                'started_at' => today()->subYears($i * 2), 'ended_at' => today()->subYears(($i - 1) * 2),
                'is_active' => false, 'sort_order' => $i,
            ]);
        }
        $term = Models\BoardTerm::firstOrCreate(['title' => 'دوره آزمایشی هیئت مدیره'], ['started_at' => today()->subYear(), 'is_active' => true]);
        foreach ([['board', 'رئیس هیئت مدیره'], ['board', 'نایب رئیس'], ['inspector', 'بازرس'], ['prosecutor', 'دادستان انتظامی'], ['former_president', 'رئیس دوره پیشین'], ['board', 'دبیر هیئت مدیره'], ['board', 'خزانه‌دار'], ['board', 'عضو هیئت مدیره'], ['inspector', 'بازرس علی‌البدل'], ['former_president', 'رئیس دوره پیشین نمونه']] as $i => [$role, $position]) {
            Models\BoardMember::firstOrCreate(['name' => 'عضو نمونه '.($i + 1)], ['board_term_id' => $term->id, 'position' => $position, 'role' => $role, 'is_active' => true, 'sort_order' => $i]);
        }
    }

    private function createAgreementsAndGalleries(array $media): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Models\Agreement::firstOrCreate(['slug' => 'demo-agreement-'.$i], [
                'title' => 'تفاهم‌نامه آزمایشی '.$i, 'partner' => 'مجموعه نمونه '.$i,
                'body' => '<p>این تفاهم‌نامه نمونه است و هیچ تعهد حقوقی ایجاد نمی‌کند.</p>',
                'file_id' => $media['sample.pdf']->id, 'cover_id' => $media['gallery.jpg']->id,
                'is_demo' => true, 'status' => 'published', 'published_at' => now()->subDays($i),
            ]);
            $album = Models\Album::firstOrCreate(['slug' => 'demo-album-'.$i], [
                'title' => 'گالری نمونه '.$i, 'type' => 'photo', 'cover_id' => $media['gallery.jpg']->id,
                'description' => 'گالری آزمایشی', 'status' => 'published', 'published_at' => now()->subDays($i),
            ]);
            Models\AlbumItem::firstOrCreate(['album_id' => $album->id, 'media_id' => $media['gallery.jpg']->id], ['caption' => 'تصویر نمونه', 'sort_order' => 0]);
           
            Models\Slide::firstOrCreate(['title' => 'اسلاید آزمایشی '.$i], ['image_id' => $media['news.jpg']->id, 'target_url' => '/news/demo-news-'.$i, 'is_active' => true, 'sort_order' => $i]);
        }
    }

    private function createEvents(array $media): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Models\Event::firstOrCreate(['slug' => 'demo-event-'.$i], [
                'title' => 'رویداد آموزشی آزمایشی '.$i, 'body' => '<p>اطلاعات این رویداد کاملاً آزمایشی است.</p>',
                'cover_id' => $media['event.jpg']->id, 'mode' => ['in_person', 'online', 'hybrid'][$i % 3],
                'location' => 'محل برگزاری آزمایشی', 'starts_at' => now()->addDays($i), 'ends_at' => now()->addDays($i)->addHours(2),
                'registration_url' => 'https://event.alocom.co/class/kkrm/webinar', 'status' => 'published', 'published_at' => now()->subDay(),
            ]);
        }
    }

    private function createFaqs(): void
    {
        Models\Faq::firstOrCreate(['question' => 'کارشناسان از کجا وارد حساب خود می‌شوند؟'], ['answer' => 'ورود کارشناسان از طریق سامانه مستقل my.kkrm.ir انجام می‌شود.', 'group' => 'experts', 'is_active' => true]);
        Models\Faq::firstOrCreate(['question' => 'آیا اطلاعات این نسخه رسمی است؟'], ['answer' => 'خیر. محتوای نمونه فقط برای تست نرم‌افزار است.', 'group' => 'general', 'is_active' => true]);
        for ($i = 3; $i <= 10; $i++) {
            Models\Faq::firstOrCreate(['question' => 'پرسش متداول آزمایشی '.$i], [
                'answer' => 'پاسخ نمونه برای نمایش بخش پرسش‌های متداول.', 'group' => 'general', 'is_active' => true, 'sort_order' => $i,
            ]);
        }
    }

    private function createAccountsAndMessages(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Models\BankAccount::firstOrCreate(['purpose' => 'حساب نمونه '.$i.'؛ واریز نکنید'], [
                'account_number' => str_repeat('0', 12), 'bank_name' => 'بانک آزمایشی '.$i,
                'holder' => 'دارنده آزمایشی', 'is_demo' => true, 'is_active' => true, 'sort_order' => $i,
            ]);
            Models\ContactMessage::firstOrCreate(['email' => 'contact'.$i.'@example.test', 'subject' => 'پیام آزمایشی '.$i], [
                'name' => 'فرستنده نمونه '.$i, 'message' => 'این پیام آزمایشی برای بررسی مدیریت پیام‌ها است.',
                'status' => ['new', 'read', 'closed'][($i - 1) % 3],
            ]);
        }
    }

    private function createTariffs(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Models\Tariff::firstOrCreate(['slug' => $i === 1 ? 'demo-progressive' : 'demo-progressive-'.$i], [
                'name' => 'تعرفه پله‌ای آزمایشی '.$i.'؛ فاقد اعتبار قانونی', 'currency' => 'IRR',
                'effective_from' => today()->subYear()->subDays($i), 'is_demo' => true, 'is_active' => true,
                'rules' => [
                    'base_fee' => 100000, 'minimum_fee' => 200000, 'maximum_fee' => 10000000,
                    'tiers' => [['up_to' => 10000000, 'rate_bps' => 100], ['up_to' => 50000000, 'rate_bps' => 50], ['up_to' => null, 'rate_bps' => 25]],
                    'panel_multipliers' => [1 => 10000, 2 => 12000, 3 => 14000, 4 => 16000, 5 => 18000],
                ],
            ]);
        }
    }
}

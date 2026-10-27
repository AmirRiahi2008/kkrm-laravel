<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ContentTranslation;
use App\Models\ExternalLink;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Services\TranslationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

final class EnglishNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'صفحه اصلی' => 'Home',
            'اخبار' => 'News',
            'اخبار و مقاله‌ها' => 'News and articles',
            'مقاله‌ها' => 'Articles',
            'اطلاعیه‌ها' => 'Announcements',
            'مصوبات' => 'Resolutions',
            'آموزش' => 'Training',
            'رفاه' => 'Welfare',
            'تسلیت' => 'Condolences',
            'رویدادهای گروه‌ها' => 'Expert group events',
            'سامانه‌ها' => 'Online systems',
            'درباره کانون' => 'About the council',
            'تاریخچه کانون' => 'Council history',
            'هیئت مدیره، بازرسان و دادستان انتظامی' => 'Board members, inspectors and disciplinary prosecutor',
            'گروه‌های دوازده‌گانه کارشناسی' => 'The twelve expert groups',
            'گالری تصاویر' => 'Photo gallery',
            'ریاست ادوار' => 'Former presidents',
            'قوانین و مقررات' => 'Laws and regulations',
            'نظام‌نامه‌ها، دستورالعمل‌ها و بخشنامه‌ها' => 'Statutes, directives and circulars',
            'امور کارشناسان' => 'Expert services',
            'ورود کارشناسان' => 'Expert sign in',
            'ورود کارشناسان و ثبت فیش' => 'Expert sign in and payment receipts',
            'فرم‌های مورد نیاز' => 'Required forms',
            'جدول عناوین صلاحیت‌ها' => 'Qualification titles',
            'جستجوی کارشناس' => 'Find an expert',
            'سامانه کارآموزان' => 'Trainee portal',
            'قراردادها و تفاهم‌نامه‌ها' => 'Agreements and memoranda',
            'تعرفه دستمزد' => 'Expert fees',
            'شرح تعرفه' => 'Fee schedule',
            'شماره حساب کانون' => 'Council bank accounts',
            'آیین‌نامه اجرایی' => 'Executive regulation',
            'بخشنامه‌ها' => 'Circulars',
            'افزایش صلاحیت' => 'Additional qualifications',
            'تماس با ما' => 'Contact us',
            'قوانین و اسناد' => 'Laws and documents',
            'آموزش مجازی' => 'Online training',
            'سامانه ۲۰۲۰' => '2020 referral system',
            'خدمات الکترونیک قضایی' => 'Electronic judicial services',
            'پرسش‌های متداول' => 'Frequently asked questions',
            'قانون کارشناسان رسمی دادگستری' => 'Official Justice Experts Act',
            'آیین‌نامه اجرایی قانون' => 'Executive regulation of the Act',
            'آیین‌نامه رسیدگی به تخلفات کارشناسان' => 'Regulation on expert disciplinary proceedings',
            'نظام‌نامه نقل و انتقالات کارشناسان' => 'Expert transfer statute',
            'نظام‌نامه توسعه حوزه جغرافیایی و فعالیت خدمات کارشناسی' => 'Statute on geographical scope and expert services',
            'نظام‌نامه مالی و معاملاتی کانون‌ها' => 'Council financial and procurement statute',
            'نظام‌نامه اعطای صلاحیت‌های کارشناسی' => 'Statute on granting expert qualifications',
            'نظام‌نامه هیئت رئیسه گروه‌های کارشناسی' => 'Statute on expert group executive committees',
            'دستورالعمل تهیه گزارش و نظریه کارشناسی' => 'Directive on preparing expert reports and opinions',
            'نظام‌نامه کارآموزی کارشناسان' => 'Expert traineeship statute',
            'دستورالعمل نقل و انتقال کارشناسان' => 'Expert transfer directive',
            'نظام‌نامه کمیسیون‌های تشخیص صلاحیت علمی و فنی' => 'Statute on scientific and technical qualification committees',
            'بخشنامه هزینه خدمات کانون' => 'Circular on council service fees',
            'دستورالعمل زمان و فرآیند کارشناسی' => 'Directive on expert assessment timing and procedure',
            'جدول عناوین صلاحیت‌های کارشناسی' => 'Expert qualification titles',
            'شرح تعرفه دستمزد کارشناسی' => 'Expert fee schedule',
        ];
        foreach (['menu-items' => MenuItem::class, 'links' => ExternalLink::class] as $resource => $class) {
            $class::orderBy('id')->chunkById(100, function ($items) use ($resource, $titles): void {
                foreach ($items as $item) {
                    if (! isset($titles[$item->title])) {
                        continue;
                    }
                    $record = ContentTranslation::where('resource', $resource)->where('record_id', $item->id)->where('locale', 'en')->first();
                    if (! empty($record?->fields['title'])) {
                        continue;
                    }
                    app(TranslationService::class)->save($item, ['en' => ['title' => $titles[$item->title]]]);
                }
            });
        }
        foreach ([
            'site_name_en' => 'Mazandaran Official Justice Experts Council',
            'site_description_en' => 'News, information and services from the Mazandaran Official Justice Experts Council.',
            'announcement_ticker_en' => 'Investment for production',
            'copyright_en' => 'All rights reserved by the Mazandaran Official Justice Experts Council.',
        ] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        foreach (['site.home.fa', 'site.home.en', 'site.navigation.fa', 'site.navigation.en', 'site.settings.fa', 'site.settings.en'] as $key) {
            Cache::forget($key);
        }
    }
}

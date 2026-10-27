<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExternalLink;
use App\Models\LegacyRedirect;
use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SiteStructureSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/site-structure.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach (['systems', 'useful'] as $group) {
            foreach ($data[$group] as $index => [$code, $title, $url]) {
                ExternalLink::firstOrCreate(['code' => $code], ['title' => $title, 'url' => $url, 'group' => $group, 'is_active' => true, 'sort_order' => $index]);
            }
        }
        foreach ($data['legacy'] as $record) {
            LegacyRedirect::firstOrCreate(['old_path' => $record['old_path']], ['new_path' => $record['new_path'], 'status_code' => 301]);
        }
        foreach ([
            'site_name' => 'کانون کارشناسان رسمی دادگستری استان مازندران',
            'site_description' => 'پایگاه اطلاع‌رسانی کانون کارشناسان رسمی دادگستری مازندران',
            'site_email' => 'info@kkrm.ir',
            'site_phone' => '01144430531',
            'site_address' => 'ساری، میدان امام حسین، ابتدای خیابان خاقانی، ساختمان کانون، طبقه ۶',
            'copyright' => 'تمامی حقوق این سایت متعلق به کانون کارشناسان رسمی دادگستری مازندران است.',
        ] as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
        foreach (['header', 'mobile'] as $location) {
            $this->menu($location, 'صفحه اصلی', '/', null, 0);
            $news = $this->menu($location, 'اخبار و مقاله‌ها', '/news', null, 1);
            foreach ([['اخبار', '/news'], ['مقاله‌ها', '/articles'], ['اطلاعیه‌ها', '/news?type=announcement'], ['مصوبات', '/news?type=resolution'], ['آموزش', '/news?type=training'], ['رفاه', '/news?type=welfare'], ['تسلیت', '/news?type=condolence'], ['رویدادهای گروه‌ها', '/news?type=group_event']] as $index => [$title, $url]) {
                $this->menu($location, $title, $url, $news->id, $index);
            }
            $systems = $this->menu($location, 'سامانه‌ها', null, null, 2);
            foreach (array_slice($data['systems'], 0, 3) as $index => [$code, $title, $url]) {
                $this->menu($location, $title, $url, $systems->id, $index);
            }
            $about = $this->menu($location, 'درباره کانون', null, null, 3);
            foreach ([['تاریخچه کانون', '/pages/history'], ['هیئت مدیره، بازرسان و دادستان انتظامی', '/board-members'], ['گروه‌های دوازده‌گانه کارشناسی', '/expert-groups'], ['گالری تصاویر', '/albums'], ['ریاست ادوار', '/board-members?role=former_president']] as $index => [$title, $url]) {
                $this->menu($location, $title, $url, $about->id, $index);
            }
            $laws = $this->menu($location, 'قوانین و مقررات', null, null, 4);
            $statutes = $this->menu($location, 'نظام‌نامه‌ها، دستورالعمل‌ها و بخشنامه‌ها', '/documents?type=statute', $laws->id, 3);
            foreach ($data['documents'] as $index => $doc) {
                if (in_array($doc['type'], ['qualification_table', 'tariff'], true)) {
                    continue;
                }
                $this->menu($location, $doc['title'], '/documents/'.$doc['slug'], $index < 3 ? $laws->id : $statutes->id, $index);
            }
            $experts = $this->menu($location, 'امور کارشناسان', null, null, 5);
            foreach ([['ورود کارشناسان', 'https://my.kkrm.ir/'], ['فرم‌های مورد نیاز', '/documents?type=form'], ['جدول عناوین صلاحیت‌ها', '/documents/qualification-titles'], ['جستجوی کارشناس', '/experts']] as $index => [$title, $url]) {
                $this->menu($location, $title, $url, $experts->id, $index);
            }
            $this->menu($location, 'سامانه کارآموزان', 'https://karamooz.kkrm.ir/login', null, 6);
            $this->menu($location, 'قراردادها و تفاهم‌نامه‌ها', '/agreements', null, 7);
            $tariffs = $this->menu($location, 'تعرفه دستمزد', null, null, 8);
            foreach ([['شرح تعرفه', '/documents/tariff-description'], ['محاسبه آنلاین', '/tariffs/calculator'], ['شماره حساب کانون', '/bank-accounts']] as $index => [$title, $url]) {
                $this->menu($location, $title, $url, $tariffs->id, $index);
            }
            $this->menu($location, 'تماس با ما', '/contact', null, 9);
        }
        foreach (['footer', 'quick'] as $location) {
            foreach ([['اخبار', '/news'], ['گالری تصاویر', '/albums'], ['تاریخچه کانون', '/pages/history'], ['آیین‌نامه اجرایی', '/documents/executive-regulation'], ['بخشنامه‌ها', '/documents?type=circular'], ['محاسبه آنلاین تعرفه', '/tariffs/calculator'], ['افزایش صلاحیت', '/documents/increase-qualification'], ['تماس با ما', '/contact']] as $index => [$title, $url]) {
                $this->menu($location, $title, $url, null, $index);
            }
        }
    }

    private function menu(string $location, string $title, ?string $url, ?int $parent, int $sort): MenuItem
    {
        return MenuItem::firstOrCreate(
            ['location' => $location, 'title' => $title, 'parent_id' => $parent],
            ['url' => $url, 'sort_order' => $sort, 'is_active' => true, 'open_new_tab' => $url !== null && str_starts_with($url, 'https://')],
        );
    }
}

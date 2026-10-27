<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Portal as P;

final class PortalNavigation
{
    public function main(array $menus): array
    {
        $links = array_filter($menus, fn ($m) => in_array($m['display_mode'] ?? '', ['main', 'both'], true) && P::href($m['url'] ?? null) && ! in_array($m['url'], ['/', '/contact', '/systems', '/download', '/experts', '/tariffs/calculator'], true));
        if (! collect($menus)->contains(fn ($m) => ($m['url'] ?? '') === '/news?type=welfare' && ! empty($m['display_mode']))) {
            $links[] = ['title' => 'رفاهیات', 'url' => '/news?type=welfare'];
        }

        return array_values($links);
    }

    public function sections(array $menus): array
    {
        $groups = [
            'news' => ['label' => 'اخبار و مقاله‌ها', 'icon' => 'book-open', 'items' => []],
            'experts' => ['label' => 'امور کارشناسان', 'icon' => 'users', 'items' => []],
            'council' => ['label' => 'درباره کانون', 'icon' => 'building-2', 'items' => []],
            'laws' => ['label' => 'قوانین و مقررات', 'icon' => 'scale', 'items' => []],
            'contact' => ['label' => 'تماس با ما', 'icon' => 'phone', 'items' => []],
        ];
        foreach ($menus as $menu) {
            if (($menu['display_mode'] ?? '') === 'main') {
                continue;
            }
            $url = $menu['url'] ?? '';
            $title = $menu['original_title'] ?? $menu['title'];
            if (in_array($url, ['/', '/experts', '/tariffs/calculator', '/fees', '/faqs'], true)) {
                continue;
            }
            $key = match (true) {
                preg_match('/تماس|ارتباط با|contact/i', $title) === 1 || $url === '/contact' => 'contact',
                preg_match('/خبر|اخبار|مقاله|گالری|رویداد|رفاه|تسلیت|آموزش|news|article|gallery|event/i', $title) === 1 => 'news',
                preg_match('/قانون|قوانین|مقررات|آیین|نظام.?نامه|بخشنامه|تعرفه|دستمزد|law|regulation|tariff/i', $title) === 1 => 'laws',
                preg_match('/درباره|تاریخچه|هیئت|هیت|ریاست|ارکان|قرارداد|تفاهم|about|board/i', $title) === 1 => 'council',default => 'experts'
            };
            if ($title === $groups[$key]['label'] && ! empty($menu['children'])) {
                $groups[$key]['items'] = array_merge($groups[$key]['items'], $menu['children']);
            } else {
                $groups[$key]['items'][] = $menu;
            }
        }
        $defaults = ['news' => [['اخبار کانون', '/news'], ['مقاله‌ها', '/articles'], ['اطلاعیه‌ها', '/news?type=announcement'], ['گالری تصاویر', '/albums']], 'experts' => [['گروه‌های کارشناسی', '/expert-groups'], ['رشته‌های کارشناسی', '/disciplines'], ['سامانه‌ها', '/systems'], ['شماره حساب کانون', '/bank-accounts']], 'council' => [['تاریخچه کانون', '/pages/history'], ['هیئت مدیره و ارکان', '/board-members']], 'laws' => [['قوانین و اسناد', '/documents'], ['آیین‌نامه‌ها', '/documents?type=regulation'], ['نظام‌نامه‌ها', '/documents?type=statute']], 'contact' => [['تماس با ما', '/contact']]];
        foreach ($groups as $key => &$group) {
            if (! $group['items']) {
                foreach ($defaults[$key] as [$title,$url]) {
                    $group['items'][] = compact('title', 'url') + ['children' => []];
                }
            }
        }

        return array_values($groups);
    }
}

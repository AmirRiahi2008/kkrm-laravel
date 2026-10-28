@if($paginator->hasPages())
@php
    $pageHref = function (int $page) use ($paginator): string {
        $query = request()->query();
        $query[$paginator->getPageName()] = $page;

        return request()->getPathInfo().'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    };
@endphp
<nav class="pagination" aria-label="{{ P::t('صفحه‌بندی') }}">
    @if(!$paginator->onFirstPage())
        <a href="{{ $pageHref($paginator->currentPage()-1) }}">{{ P::t('قبلی') }}</a>
    @endif
    @for($page=max(1,$paginator->currentPage()-2);$page<=min($paginator->lastPage(),$paginator->currentPage()+2);$page++)
        <a href="{{ $pageHref($page) }}" @if($page===$paginator->currentPage()) aria-current="page" @endif>{{ P::number($page) }}</a>
    @endfor
    @if($paginator->hasMorePages())
        <a href="{{ $pageHref($paginator->currentPage()+1) }}">{{ P::t('بعدی') }}</a>
    @endif
</nav>
@endif

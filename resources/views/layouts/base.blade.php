<!doctype html>
<html lang="{{ P::locale() }}" dir="{{ P::locale()==='en'?'ltr':'rtl' }}" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', P::t('کانون کارشناسان رسمی دادگستری مازندران'))</title>
<meta name="description" content="@yield('description', $settings['site_description']??P::t('پایگاه اطلاع‌رسانی کانون کارشناسان رسمی دادگستری مازندران'))">
<link rel="icon" href="/assets/kkrm-emblem.png"><link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png"><link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#00477a">
@if(!request()->is('admin','admin/*'))<link rel="canonical" href="{{ url()->current() }}">@endif
<script>try { document.documentElement.dataset.theme=localStorage.getItem('kkrm-theme')==='dark'?'dark':'light'; } catch {}</script>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="antialiased" x-data="appShell">
@yield('body')
<div x-cloak x-show="$store.loading.count > 0" class="global-loading-overlay" role="status" aria-live="polite"><div class="global-loading-card"><span class="global-loading-spinner"></span><strong class="loading-council-name">{{ P::t('کانون کارشناسان رسمی دادگستری مازندران') }}</strong><span>{{ P::t('در حال انجام عملیات…') }}</span><span>{{ P::t('لطفاً کمی صبر کنید.') }}</span></div></div>
<div class="toast-stack" aria-live="polite"><template x-for="toast in $store.toasts.items" :key="toast.id"><div class="toast-item" :class="toast.kind" x-transition><span x-text="toast.message"></span><button type="button" aria-label="بستن" @click="$store.toasts.remove(toast.id)">×</button></div></template></div>
@include('components.lightboxes')
</body></html>

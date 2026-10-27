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
<div x-cloak x-show="$store.loading.count > 0" class="global-loading-overlay" role="status" aria-live="polite"><div class="global-loading-card"><span class="global-loading-spinner"></span><strong>{{ P::t('کانون کارشناسان رسمی دادگستری مازندران') }}</strong><p>{{ P::t('در حال پردازش…') }}</p></div></div>
<div class="toast-stack" aria-live="polite"><template x-for="toast in $store.toasts.items" :key="toast.id"><div class="toast-item" :class="toast.kind" x-transition><span x-text="toast.message"></span><button type="button" aria-label="بستن" @click="$store.toasts.remove(toast.id)">×</button></div></template></div>
<dialog x-ref="lightbox" class="inline-gallery-lightbox" @click="if($event.target===$el)closeGallery()" @cancel.prevent="closeGallery()" @keydown.arrow-left.prevent="moveGallery(1)" @keydown.arrow-right.prevent="moveGallery(-1)"><div class="inline-gallery-lightbox-inner"><div class="inline-gallery-dialog-heading"><span x-text="galleryTitle"></span><button type="button" @click="closeGallery()" aria-label="بستن">×</button></div><div class="inline-gallery-full-image"><img :src="galleryImages[galleryIndex]?.src || ''" :alt="galleryImages[galleryIndex]?.alt || ''"></div><div class="inline-gallery-controls"><button type="button" @click="moveGallery(-1)">❮</button><span x-text="(galleryIndex+1)+' / '+galleryImages.length"></span><button type="button" @click="moveGallery(1)">❯</button></div></div></dialog>
</body></html>

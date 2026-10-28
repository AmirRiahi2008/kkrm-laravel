@extends('layouts.base')
@section('title',P::t('این صفحه پیدا نشد'))
@section('body')<main class="error-page"><strong class="error-number">{{ P::number(404) }}</strong><h1>{{ P::t('این صفحه پیدا نشد') }}</h1><p>{{ P::t('ممکن است نشانی تغییر کرده باشد یا محتوا منتشر نشده باشد.') }}</p><a class="primary-button" href="{{ P::url('/') }}">{{ P::t('صفحه اصلی') }}</a></main>@endsection

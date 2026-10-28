@extends('layouts.admin')
@section('title','پیش‌نمایش صفحه')
@section('content')<div class="cms-preview-toolbar"><div><strong>پیش‌نمایش صفحه</strong><p>این پیش‌نمایش فقط در داشبورد قابل مشاهده است.</p></div><a class="secondary-button" href="/admin/pages/{{ $item['id'] }}">بازگشت به ویرایش</a>@if(($item['status']??'')==='published')<a class="primary-button" href="{{ P::path('pages',$item) }}" target="_blank" rel="noopener noreferrer">مشاهده صفحه عمومی</a>@endif</div>@include('site.cms-page',['preview'=>true])@endsection

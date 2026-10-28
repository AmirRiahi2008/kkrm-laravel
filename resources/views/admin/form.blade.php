@extends('layouts.admin')
@section('title',($initial?'ویرایش ':'افزودن ').$config['label'])
@section('content')
<div x-data="resourceForm({{ \Illuminate\Support\Js::from($resource) }},{{ \Illuminate\Support\Js::from($config) }},{{ \Illuminate\Support\Js::from($initial) }},{{ \Illuminate\Support\Js::from(auth()->user()->role->value) }})"><div class="admin-page-heading"><div><h1>{{ $initial?'ویرایش ':'افزودن ' }}{{ $config['label'] }}</h1>@if($initial)<p>{{ P::title($initial) }}</p>@endif</div><a class="secondary-button" href="/admin/{{ $resource }}">بازگشت به فهرست</a></div>
@if($resource==='contact-messages'&&$initial)<section class="admin-panel"><h2>{{ $initial['subject']??'' }}</h2><p>{{ $initial['name']??'' }} — {{ $initial['email']??'' }}</p><p>{{ $initial['message']??'' }}</p></section>@endif
<form class="admin-panel resource-form" @submit.prevent="save()"><p class="field-hint" role="status" x-show="referencesLoading" x-cloak>فهرست انتخاب‌ها و کتابخانه رسانه در حال آماده‌سازی است…</p><div class="form-grid">@if($resource==='menu-items')@include('admin.menu-placement')@endif @foreach($config['fields'] as $field)@if(!($resource==='menu-items'&&in_array($field['key'],['location','display_mode','parent_id']))&&(auth()->user()->role->value!=='editor'||!in_array($field['key'],['is_featured','is_pinned','show_in_slideshow'])))@include('admin.field')@endif @endforeach</div>
<p role="alert" class="form-error" x-show="error" x-text="error" x-cloak></p>@if($resource==='albums'&&!$initial)<p class="field-hint">پس از ثبت آلبوم، رسانه‌ها و ترتیب نمایش را اضافه کنید.</p>@endif
@include('admin.translations')
<div class="form-actions"><button class="primary-button" :disabled="busy||uploads>0"><x-icon name="save" size="17" /><span x-text="busy?'در حال ذخیره…':'ذخیره تغییرات'"></span></button><a href="/admin/{{ $resource }}" class="secondary-button">انصراف</a></div></form>
@if($resource==='albums')@include('admin.album-editor')@endif
@if($resource==='posts'&&$initial)<section class="admin-panel revision-history"><h2>نسخه‌های قبلی مطلب</h2>@foreach(\App\Models\Post::find($initial['id'])->revisions()->latest('id')->limit(15)->get() as $revision)<details><summary>{{ P::adminDate($revision->created_at) }}</summary><div class="rich-content">{!! app(\App\Services\HtmlService::class)->clean($revision->snapshot['body']??'') !!}</div></details>@endforeach</section>@endif
</div>
@endsection

@extends('layouts.site')
@section('title',$resource==='experts'?P::t('جستجوی پیشرفته کارشناسان'):$label)
@section('content')
<div class="page-container inner-page {{ $resource==='experts'?'expert-directory':'' }}">@include('components.page-title',['label'=>$resource==='experts'?P::t('جستجوی پیشرفته کارشناسان'):$label,'description'=>$resource==='experts'?'جستجو در فهرست کارشناسان کانون بر اساس نام، گروه، رشته و صلاحیت':($resource==='albums'?'آلبوم‌های تصویری نشست‌ها و رویدادهای کانون':null)])
@if($errors->any())<p class="form-error">{{ $errors->first() }}</p>@endif
@if($resource==='experts')@include('site.expert-filters')<div class="expert-directory-results" aria-live="polite"><div class="directory-results-heading"><h2>{{ P::t('نتایج جستجو') }}</h2><span>{{ P::number($items->total()) }} {{ P::t('کارشناس') }}</span></div>
@else @include('site.filters')<p class="result-count">{{ P::number($items->total()) }} {{ P::t('مورد') }}</p>@endif
@include('site.list')
{{ $items->links('components.pagination') }}
@if($resource==='experts')</div>@endif
</div>
@endsection

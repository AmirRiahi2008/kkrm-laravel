@extends('layouts.site')
@section('title',$label)
@section('content')<div class="page-container inner-page">@include('components.page-title')
@if($errors->any())<p class="form-error">{{ $errors->first() }}</p>@endif
@if($resource==='experts')@include('site.expert-filters')@else<form class="filter-bar" method="get"><label>{{ P::t('جستجو') }}<input name="q" maxlength="150" value="{{ request('q') }}"></label>@if($types=P::data('public-resources')[$resource]['types']??null)<label>{{ P::t('نوع') }}<select name="type"><option value="">{{ P::t('همه') }}</option>@foreach($types as $value=>$text)<option value="{{ $value }}" @selected(request('type')===$value)>{{ P::t($text) }}</option>@endforeach</select></label>@endif<label>{{ P::t('ترتیب نمایش') }}<select name="sort">@foreach(['latest'=>'جدیدترین','oldest'=>'قدیمی‌ترین','title'=>'عنوان'] as $key=>$value)<option value="{{ $key }}" @selected(request('sort')===$key)>{{ P::t($value) }}</option>@endforeach</select></label><button class="primary-button">{{ P::t('جستجو') }}</button></form>@endif
@if($items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<p class="result-count">{{ $items->total() }} {{ P::t('مورد') }}</p>@endif
<div class="{{ $resource==='experts'?'directory-expert-grid':(in_array($resource,['documents','qualifications','disciplines','expert-groups','categories','tags','board-terms'])?'document-list':($resource==='faqs'?'faq-list':(in_array($resource,['systems','links'])?'systems-grid':'cards-grid'))) }}">
@forelse($items as $model)@php($item=app(\App\Services\ContentPresenter::class)->one($model,false,false))
@if($resource==='faqs')<details class="faq-item"><summary>{{ P::title($item) }}</summary><div class="rich-content">{!! app(\App\Services\HtmlService::class)->clean($item['answer']??'') !!}</div></details>
@elseif(in_array($resource,['documents','qualifications','disciplines','expert-groups','categories','tags','board-terms']))<a class="document-row" href="{{ P::path($resource,$item) }}"><i data-lucide="file-text"></i><div><h2>{{ P::title($item) }}</h2><p>{{ $item['description']??$item['code']??'' }}</p></div><i data-lucide="chevron-left"></i></a>
@elseif($resource==='experts')@include('site.expert-card')
@elseif(in_array($resource,['systems','links'],true))@if($href=P::href($item['url']??null))<a x-data="pointerCard" @pointermove="move($event)" @pointerleave="reset()" class="system-link system-pointer" href="{{ $href }}" target="_blank" rel="noopener noreferrer">{{ P::title($item) }} ↗</a>@endif
@elseif($resource==='bank-accounts')<article class="bank-card"><h2>{{ P::title($item) }}</h2><p>{{ $item['holder']??'' }}</p><p>{{ $item['purpose']??'' }}</p>@foreach(['account_number'=>'شماره حساب','iban'=>'شبا','card_number'=>'شماره کارت'] as $key=>$text)@if(!empty($item[$key]))<p>{{ P::t($text) }}: <b dir="ltr">{{ $item[$key] }}</b></p>@endif @endforeach</article>
@else @include('components.card') @endif
@empty @include('components.empty') @endforelse</div>
@if($items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator){{ $items->links('components.pagination') }}@endif
</div>@endsection
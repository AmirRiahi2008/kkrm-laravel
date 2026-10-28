@extends('layouts.site')
@section('title',P::title($item))
@section('description',$item['meta_description']??$item['excerpt']??$item['summary']??'')
@section('content')
@if($resource==='pages')@include('site.cms-page')@else
<div class="page-container inner-page">@include('components.page-title',['label'=>P::title($item)])
@php $cover=in_array($resource,['experts','board-members'],true)?(P::media($item['photo']??null)?:P::media($item['image']??null)?:P::media($item['cover']??null)):null; @endphp
<article class="detail-panel {{ !$cover?'without-image':'' }} {{ $resource==='experts'?'expert-profile':'' }}"><div class="detail-meta">
@if($resource==='experts')<span class="directory-status {{ ($item['status']??'')==='suspended'?'suspended':'' }}">{{ P::t(($item['status']??'')==='suspended'?'پروانه تعلیق‌شده':'کارشناس فعال') }}</span>@endif
@if(!empty($item['published_at']))<time>{{ P::date($item['published_at'],true) }}</time>@endif @if(!empty($item['author']))<span>{{ P::t('نویسنده:') }} {{ P::title($item['author']) }}</span>@endif @if(!empty($item['is_demo']))<span class="demo-label">{{ P::t('داده آزمایشی') }}</span>@endif</div>
@if($cover)<img class="detail-cover portrait" src="{{ $cover }}" alt="{{ P::title($item) }}">@endif
@if($resource==='events')<div class="event-info"><p>{{ P::t('شروع:') }} {{ P::date($item['starts_at']??null,true) }}</p><p>{{ P::t('پایان:') }} {{ P::date($item['ends_at']??null,true) }}</p><p>{{ P::t(match($item['mode']??''){ 'online'=>'مجازی','hybrid'=>'حضوری و مجازی',default=>'حضوری'}) }} · {{ $item['location']??'' }}</p>@if($href=P::href($item['registration_url']??null))<a class="primary-button" href="{{ $href }}" target="_blank" rel="noopener noreferrer">{{ P::t('ورود به سامانه رویداد') }}</a>@endif</div>@endif
@if($resource==='experts')<dl class="profile-details">@foreach(['license_number'=>'شماره پروانه','city'=>'شهر','geographical_scope'=>'حوزه جغرافیایی','phone'=>'تلفن','email'=>'ایمیل'] as $key=>$label)@if(!empty($item[$key]))<div><dt>{{ P::t($label) }}</dt><dd>@if($key==='phone')<a dir="ltr" href="tel:{{ preg_replace('/[^+0-9]/','',strtr($item[$key],array_combine(preg_split('//u','۰۱۲۳۴۵۶۷۸۹',-1,PREG_SPLIT_NO_EMPTY),range(0,9)))) }}">{{ $item[$key] }}</a>@elseif($key==='email')<a dir="ltr" href="mailto:{{ $item[$key] }}">{{ $item[$key] }}</a>@else{{ $item[$key] }}@endif</dd></div>@endif @endforeach</dl><div class="expert-profile-specialty">@foreach(['expert_group'=>['گروه:','expert_group_id'],'discipline'=>['رشته:','discipline_id']] as $key=>[$label,$query])@if(!empty($item[$key]))<p>{{ P::t($label) }} <a href="{{ P::url('/experts?'.$query.'='.$item[$key]['id']) }}">{{ P::title($item[$key]) }}</a></p>@endif @endforeach</div>@if(!empty($item['qualifications']))<h2>{{ P::t('صلاحیت‌ها') }}</h2><ul class="qualification-list">@foreach($item['qualifications'] as $entry)<li>{{ P::title($entry) }}</li>@endforeach</ul>@endif @endif
@if(P::locale()==='en'&&($item['body_locale']??'')==='fa')<p class="translation-notice">{{ P::t('محتوای این صفحه هنوز ترجمه نشده است.') }}</p>@endif
<div class="content-original" lang="{{ $item['body_locale']??P::locale() }}" @if(($item['body_locale']??'')==='fa') dir="rtl" @endif><div class="rich-content" @click="richImage($event)">{!! app(\App\Services\HtmlService::class)->clean($item['body']??$item['biography']??$item['answer']??$item['description']??'') !!}</div></div>
@if(!empty($item['partner']))<p class="quiet">{{ P::t('طرف قرارداد:') }} {{ $item['partner'] }}</p>@endif
@if($resource==='albums')@include('site.gallery')@endif
@php $files=array_filter(array_merge([$item['file']??null],$item['attachments']??[]),fn($f)=>P::media($f)); @endphp
@if($files)<div class="attachments"><h2>{{ P::t('فایل‌های پیوست') }}</h2>@foreach($files as $file)<a href="{{ P::media($file) }}" target="_blank" rel="noopener noreferrer">{{ P::t('دریافت') }} {{ $file['alt']?:P::t('فایل پیوست').' '.P::number($loop->iteration) }} ↗</a>@endforeach</div>@endif
@if($resource==='board-members')@include('components.board-contact')@endif
@if($href=P::href($item['source_url']??null))<a class="source-link" href="{{ $href }}" target="_blank" rel="noopener noreferrer">{{ P::t('مشاهده منبع اصلی ↗') }}</a>@endif
@if(isset($item['tags'])&&is_array($item['tags']))<div class="tag-list">@foreach($item['tags'] as $entry)<a href="{{ P::url('/news?tag='.rawurlencode($entry['slug'])) }}">{{ P::title($entry) }}</a>@endforeach</div>@endif
<div class="detail-actions">@include('components.share')</div></article>
@if(!empty($relatedResource))<section class="home-section">@include('components.section-title',['label'=>$relatedLabel,'href'=>'/'.$relatedResource.(isset($relatedQuery)?'?'.$relatedQuery:'')])@include('site.list',['items'=>$related,'resource'=>$relatedResource])</section>@endif</div>
@endif
@endsection

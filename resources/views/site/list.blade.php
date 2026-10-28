@if(count($items)===0)@include('components.empty')@else
<div class="{{ $resource==='experts'?'directory-expert-grid':(in_array($resource,['documents','qualifications','disciplines','expert-groups','categories','tags','board-terms'])?'document-list':($resource==='faqs'?'faq-list':(in_array($resource,['systems','links'])?'systems-grid':'cards-grid'))) }}">
@foreach($items as $model)
@php $item=is_array($model)?$model:app(\App\Services\ContentPresenter::class)->one($model,false,false); @endphp
@if($resource==='faqs')<details><summary>{{ P::title($item) }}</summary><div lang="{{ $item['body_locale']??P::locale() }}" dir="{{ ($item['body_locale']??P::locale())==='en'?'ltr':'rtl' }}"><div class="rich-content" @click="richImage($event)">{!! app(\App\Services\HtmlService::class)->clean($item['answer']??'') !!}</div></div></details>
@elseif(in_array($resource,['documents','qualifications','disciplines','expert-groups','categories','tags','board-terms']))<a class="document-row" href="{{ P::path($resource,$item) }}"><x-icon name="file-text" size="27" /><div><h2>{{ P::title($item) }}</h2><p>{{ $item['description']??$item['code']??'' }}@if(!empty($item['issued_at'])) · {{ P::date($item['issued_at']) }}@endif</p></div><x-icon name="chevron-left" size="18" /></a>
@elseif($resource==='experts')@include('site.expert-card')
@elseif(in_array($resource,['systems','links'],true))@if($href=P::href($item['url']??null))<a x-data="pointerCard" @pointermove="move($event)" @pointerleave="reset()" @pointercancel="reset()" @blur="reset()" class="system-link system-pointer-card" href="{{ $href }}" target="_blank" rel="noopener noreferrer"><x-icon name="external-link" size="22" /><strong>{{ P::title($item) }}</strong><span>{{ P::t('مشاهده سامانه ↗') }}</span></a>@endif
@elseif($resource==='bank-accounts')<article class="bank-card">@if(!empty($item['is_demo']))<span class="demo-label">{{ P::t('حساب آزمایشی') }}</span>@endif<h2>{{ P::title($item) }}</h2><p>{{ $item['purpose']??'' }}</p><dl>@foreach(['holder'=>'صاحب حساب','account_number'=>'شماره حساب','iban'=>'شبا','card_number'=>'شماره کارت'] as $key=>$text)<div><dt>{{ P::t($text) }}</dt><dd dir="{{ $key==='holder'&&P::locale()==='fa'?'rtl':'ltr' }}">{{ $item[$key]??'—' }}</dd></div>@endforeach</dl></article>
@else @include('components.card') @endif
@endforeach</div>
@endif

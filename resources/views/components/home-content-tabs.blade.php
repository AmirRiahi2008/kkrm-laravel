@php $tabs=['announcement'=>['اطلاعیه‌های کانون','bell-ring','/news?type=announcement'],'news'=>['اخبار کانون','newspaper','/news?type=news'],'article'=>['مقاله‌ها','book-open','/articles'],'resolution'=>['مصوبات','clipboard-check','/news?type=resolution'],'welfare'=>['اخبار رفاهی','heart-handshake','/news?type=welfare']]; @endphp
<section class="home-section page-container content-hub-section" aria-labelledby="hub-heading" x-data="contentTabs">
<div class="content-hub" dir="{{ P::locale()==='en'?'ltr':'rtl' }}">
<header class="hub-heading"><div><h2 id="hub-heading">{{ P::t('تازه‌های کانون') }}</h2><p>{{ P::t('اخبار، اطلاعیه‌ها و مطالب کانون را یک‌جا دنبال کنید.') }}</p></div></header>
<div class="hub-tabs-viewport"><div class="hub-tab-list" role="tablist" aria-label="{{ P::t('نوع مطالب') }}" aria-orientation="horizontal" :style="{'--active-index':index,'--tab-direction':{{ P::locale()==='en'?1:-1 }}}"><span class="hub-tabs-indicator" aria-hidden="true"></span>
@foreach($tabs as $type=>[$label,$icon,$archive])<button type="button" class="hub-tab" role="tab" id="hub-{{ $type }}-tab" aria-controls="hub-{{ $type }}-panel" :aria-selected="index==={{ $loop->index }}" :tabindex="index==={{ $loop->index }}?0:-1" @click="select({{ $loop->index }})" @keydown="key($event,{{ $loop->index }})"><x-icon :name="$icon" size="18" stroke-width="1.8" /><span>{{ P::t($label) }}</span></button>@endforeach
</div></div>
@foreach($tabs as $type=>[$label,$icon,$archive])
@php $section=collect($data['sections'])->firstWhere('type',$type)??['items'=>[],'archive_path'=>$archive]; @endphp
<div class="hub-content-panel" role="tabpanel" id="hub-{{ $type }}-panel" aria-labelledby="hub-{{ $type }}-tab" x-show="index==={{ $loop->index }}" :tabindex="index==={{ $loop->index }}?0:-1" @if($type!=='news') x-cloak @endif>
<div class="hub-results"><div class="hub-results-heading"><div class="hub-category-heading"><x-icon :name="$icon" size="20" stroke-width="1.7" /><div><strong>{{ P::t($label) }}</strong><span>{{ P::t('جدیدترین مطالب این بخش') }}</span></div></div><a class="hub-view-all" href="{{ P::url($section['archive_path']) }}" aria-label="{{ P::t('مشاهده همه').' '.P::t($label) }}">{{ P::t('مشاهده همه') }}<x-icon name="arrow-up-right" size="17" /></a></div>
@if(count($section['items']))<div class="hub-results-grid">@foreach(array_slice($section['items'],0,3) as $item)<div class="hub-result-card" style="--card-delay:{{ $loop->index*45 }}ms">@include('components.card',['resource'=>$type==='article'?'articles':'news'])</div>@endforeach</div>@else @include('components.empty',['message'=>P::t('مطلبی در این بخش منتشر نشده است.')])@endif
</div></div>
@endforeach
</div></section>

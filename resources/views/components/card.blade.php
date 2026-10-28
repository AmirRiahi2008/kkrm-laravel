@php
$cover=P::media($item['cover']??null)?:P::media($item['photo']??null)?:P::media($item['image']??null);
$isBoard=$resource==='board-members';
$class='content-card'.($resource==='albums'?' album-card':'').(!$cover?' without-image':'').($isBoard?' board-member-card':'');
@endphp
@if($isBoard)<article class="{{ $class }}"><a class="board-member-main" href="{{ P::path($resource,$item) }}">@else<a class="{{ $class }}" href="{{ P::path($resource,$item) }}">@endif
@if($cover)
<div class="card-image"><img src="{{ $cover }}" alt="{{ P::title($item) }}" loading="lazy">@if($resource==='albums')<span class="image-label">{{ P::t('آلبوم تصاویر') }}</span>@endif</div>
@endif
<div class="card-copy">
@if($when=P::date($item['published_at']??$item['event_date']??$item['starts_at']??null))<time>{{ $when }}</time>@endif
<h3>{{ P::title($item) }}</h3>
@if($excerpt=$item['excerpt']??$item['description']??$item['partner']??$item['position']??'')<p>{{ $excerpt }}</p>@endif
<span class="read-more">{{ P::t('مشاهده جزئیات') }}<x-icon name="chevron-left" size="16" /></span>
</div></a>
@if($isBoard)@include('components.board-contact')</article>@endif

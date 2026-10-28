@php
$href=P::href($entry['url']??null);
$value=($entry['original_title']??$entry['title']).' '.($entry['url']??'');
$icon=match(true){preg_match('/تماس|contact/i',$value)===1=>'phone',preg_match('/رفاه|welfare/i',$value)===1=>'heart-handshake',preg_match('/سامانه|systems/i',$value)===1=>'network',preg_match('/خبر|news/i',$value)===1=>'newspaper',preg_match('/مقاله|article/i',$value)===1=>'book-open',preg_match('/هیئت|کارشناس|expert|board/i',$value)===1=>'users-round',default=>'link2'};
@endphp
@if(!empty($entry['children']))
<details class="nav-dropdown {{ P::branch($entry)?'is-active':'' }}"><summary><x-icon :name="$icon" size="16" class="nav-icon" stroke-width="1.8" /><span class="nav-label">{{ P::t($entry['title']) }}</span><x-icon name="chevron-down" size="13" class="nav-chevron" /></summary><div class="dropdown-panel">@foreach($entry['children'] as $child)@include('components.nav-link',['entry'=>$child])@endforeach</div></details>
@elseif($href)
<a class="nav-link {{ P::active($href)?'is-active':'' }}" href="{{ P::url($href) }}" @if(P::active($href)) aria-current="page" @endif @if($entry['open_new_tab']??false) target="_blank" rel="noopener noreferrer" @endif><x-icon :name="$icon" size="16" class="nav-icon" stroke-width="1.8" /><span class="nav-label">{{ P::t($entry['title']) }}</span></a>
@endif

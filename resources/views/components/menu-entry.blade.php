@php $href=P::href($entry['url']??null); @endphp
@if(!empty($entry['children']))
<details class="portal-mega-subgroup {{ P::branch($entry)?'is-active':'' }}" @if(P::branch($entry)) open @endif><summary><span>{{ P::t($entry['title']) }}</span><x-icon name="chevron-down" size="13" /></summary><div>
@if($href)<a class="portal-mega-link {{ P::active($href)?'is-active':'' }}" href="{{ P::url($href) }}"><x-icon name="link2" size="13" /><span>{{ P::t($entry['title']) }}</span><x-icon name="chevron-left" size="13" /></a>@endif
@foreach($entry['children'] as $child)@include('components.menu-entry',['entry'=>$child])@endforeach
</div></details>
@elseif($href)
<a href="{{ P::url($href) }}" class="portal-mega-link {{ P::active($href)?'is-active':'' }}" @if(P::active($href)) aria-current="location" @endif @if($entry['open_new_tab']??false) target="_blank" rel="noopener noreferrer" @endif><x-icon name="link2" size="13" /><span>{{ P::t($entry['title']) }}</span><x-icon name="chevron-left" size="13" /></a>
@endif

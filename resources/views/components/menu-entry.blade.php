@php($href=P::href($entry['url']??null))
@if(!empty($entry['children']))
<details class="portal-mega-subgroup {{ P::branch($entry)?'is-active':'' }}" @if(P::branch($entry)) open @endif><summary><span>{{ P::t($entry['title']) }}</span><i data-lucide="chevron-down"></i></summary><div>
@if($href)<a class="portal-mega-link" href="{{ P::url($href) }}">{{ P::t($entry['title']) }}</a>@endif
@foreach($entry['children'] as $child)@include('components.menu-entry',['entry'=>$child])@endforeach
</div></details>
@elseif($href)
<a href="{{ P::url($href) }}" class="portal-mega-link {{ P::active($href)?'is-active':'' }}" @if($entry['open_new_tab']??false) target="_blank" rel="noopener noreferrer" @endif><i data-lucide="link"></i><span>{{ P::t($entry['title']) }}</span><i data-lucide="chevron-left"></i></a>
@endif
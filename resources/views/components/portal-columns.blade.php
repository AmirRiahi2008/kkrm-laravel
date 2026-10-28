<div class="portal-mega-grid">
@foreach($sections as $section)
<section class="portal-mega-column {{ collect($section['items'])->contains(fn($item)=>P::branch($item))?'is-current':'' }}"><h3><span><x-icon :name="$section['icon']" size="18" /></span>{{ P::t($section['label']) }}</h3><div class="portal-mega-items">@foreach($section['items'] as $entry)@include('components.menu-entry')@endforeach</div></section>
@endforeach
</div>

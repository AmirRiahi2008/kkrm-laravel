@if(empty($onlyShortcuts))
<a class="nav-link portal-app-link" href="{{ P::url('/download') }}" @if(P::active('/download')) aria-current="page" @endif><x-icon name="smartphone" size="18" /><span>{{ P::t('اپلیکیشن همراه') }}</span></a>
@include('components.nav-link',['entry'=>['title'=>'تماس با ما','url'=>'/contact']])
@endif
@if(($primary??false)||($onlyShortcuts??false))
<a class="portal-shortcut portal-fees" aria-label="{{ P::t('محاسبه دستمزد') }}" href="{{ P::url('/tariffs/calculator') }}" @if(P::active('/tariffs/calculator')) aria-current="page" @endif><x-icon name="calculator" size="18" /><span>{{ P::t('محاسبه دستمزد') }}</span></a><a class="portal-shortcut portal-expert-search" aria-label="{{ P::t('جستجوی کارشناس') }}" href="{{ P::url('/experts') }}" @if(P::active('/experts')) aria-current="page" @endif><x-icon name="search-check" size="18" /><span>{{ P::t('جستجوی کارشناس') }}</span></a>
@endif

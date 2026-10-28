@php $adminTheme=$adminTheme??false; @endphp
@if($adminTheme)
<button type="button" class="utility-theme" :aria-label="$store.theme.dark?'حالت روشن':'حالت تاریک'" :aria-pressed="$store.theme.dark" @click="$store.theme.toggle()"><span x-show="$store.theme.dark"><x-icon name="sun" size="18" /></span><span x-show="!$store.theme.dark"><x-icon name="moon" size="18" /></span><span x-text="$store.theme.dark?'حالت روشن':'حالت تاریک'"></span></button>
@else
<button type="button" class="public-theme-switch" :aria-label="$store.theme.dark?'{{ P::t('حالت روشن') }}':'{{ P::t('حالت تاریک') }}'" :aria-pressed="$store.theme.dark" @click="$store.theme.toggle()"><span class="theme-switch-sky" aria-hidden="true"><span class="theme-switch-cloud"></span><span class="theme-switch-stars"></span></span><span class="theme-switch-thumb" aria-hidden="true"><x-icon name="sun" size="20" class="theme-switch-sun" /><x-icon name="moon" size="19" class="theme-switch-moon" /></span></button>
@endif

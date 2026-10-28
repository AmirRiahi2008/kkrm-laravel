@extends('layouts.site')
@section('title',P::t('جستجو در سایت'))
@section('content')
<div class="page-container inner-page">@include('components.page-title',['label'=>P::t('جستجو در سایت'),'description'=>P::t('جستجو در اخبار، اطلاعیه‌ها و مصوبات کانون')])<form class="search-page-form" action="{{ P::url('/search') }}"><label class="sr-only" for="page-search">{{ P::t('عبارت جستجو') }}</label><input id="page-search" name="q" value="{{ $query }}" minlength="2" maxlength="150" placeholder="{{ P::t('عبارت مورد نظر را وارد کنید…') }}" required><button class="primary-button">{{ P::t('جستجو') }}</button></form>
@if(mb_strlen($query)<2)@include('components.empty',['message'=>P::t('حداقل دو حرف برای جستجو وارد کنید.')])@else<p class="result-count">{{ P::number(collect($sections)->sum('total')) }} {{ P::t('نتیجه برای') }} «{{ $query }}»</p>@foreach($sections as $section)@if($section['total'])<section class="home-section">@include('components.section-title',['label'=>$section['label'],'href'=>'/news?type='.$section['type'].'&q='.rawurlencode($query)])@include('site.list',['resource'=>'news','items'=>$section['items']])</section>@endif @endforeach @if(!collect($sections)->sum('total'))@include('components.empty')@endif @endif
</div>
@endsection

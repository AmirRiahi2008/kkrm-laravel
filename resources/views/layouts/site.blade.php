@extends('layouts.base')
@section('body')
@include('components.header')
<main id="main" class="site-main">@yield('content')</main>
@include('components.footer')
@endsection

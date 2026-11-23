@extends('layouts.shell')
@section('content')
  @if (trim($__env->yieldContent('nonotice')) === '')
    @include('tasks.notice')
  @endif
  @if (session('msg'))<div class="card okm">{{ session('msg') }}</div>@endif
  @if (session('err'))<div class="card err">{{ session('err') }}</div>@endif
  @yield('body')
@endsection

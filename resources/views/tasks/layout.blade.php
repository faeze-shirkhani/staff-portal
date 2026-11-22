@extends('layouts.shell')
@section('content')
  @include('tasks.notice')
  @if (session('msg'))<div class="card okm">{{ session('msg') }}</div>@endif
  @if (session('err'))<div class="card err">{{ session('err') }}</div>@endif
  @yield('body')
@endsection

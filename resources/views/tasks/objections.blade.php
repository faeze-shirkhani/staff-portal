@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'اعتراض‌ها')
@section('body')
<div class="sm" style="margin-bottom:10px">اعتراض‌هایی که کارمندان به تسک‌های خود نوشته‌اند. بی‌پاسخ‌ها اول می‌آیند.</div>
@forelse ($rows as $o)
  <div class="card">
    <b>{{ $o->uname }}</b> <span class="sm">· تسک: {{ $o->title }}</span>
    @if (!$o->reply)<span class="b wa-t">بدون پاسخ</span>@endif
    @include('tasks._objs', ['ob' => collect([$o]), 'manage' => true])
  </div>
@empty
  <div class="sm">اعتراضی ثبت نشده است.</div>
@endforelse
@endsection

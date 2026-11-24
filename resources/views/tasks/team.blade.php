@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'افراد و تذکر')
@section('body')
<div class="sm" style="margin-bottom:10px">یک نفر را انتخاب کنید تا تسک‌هایش را ببینید و برای آن‌ها تذکر بدهید. نشان‌ها: «تذکر باز» یعنی تذکری که هنوز برداشته نشده؛ «اعتراض بی‌پاسخ» یعنی کارمند اعتراض کرده و هنوز جواب نگرفته.</div>
<form method="get" action="/tasks/team" class="card">
  <label>جست‌وجوی نام</label>
  <input name="q" value="{{ $q }}" placeholder="نام یا نام خانوادگی">
  <button class="btn">جست‌وجو</button>
</form>
@forelse ($people as $p)
  @php($f = $openFlags[$p->id] ?? 0)
  @php($o = $unreplied[$p->id] ?? 0)
  <a href="/tasks/team/{{ $p->id }}" class="card" style="display:block;color:inherit;text-decoration:none">
    <b>{{ $p->name }}</b> <span class="sm">{{ $sections[$p->section] ?? '' }}</span>
    @if ($f)<span class="b no-t">تذکر باز: {{ Fa::num($f) }}</span>@endif
    @if ($o)<span class="b wa-t">اعتراض بی‌پاسخ: {{ Fa::num($o) }}</span>@endif
  </a>
@empty
  <div class="sm">کسی پیدا نشد. برای دیدن افراد، اول برایشان تسک بنویسید.</div>
@endforelse
@endsection

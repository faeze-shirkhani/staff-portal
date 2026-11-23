@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'تسک‌های فرد')
@section('body')
<div class="card prow">
  <span class="tav">{{ mb_substr($person->name, 0, 1) }}</span>
  <div><b>{{ $person->name }}</b><div class="sm">{{ $sections[$person->section] ?? '' }}</div></div>
  <a class="sbtn" href="/tasks/team" style="margin-right:auto">‹ فهرست افراد</a>
</div>
<div class="tlist">
  <div class="tlist-h"><div><h3>تسک‌های این فرد ({{ Fa::num($tasks->count()) }})</h3><div class="sm">برای تذکر یا پاسخ به اعتراض، از منوی ⋯ هر ردیف استفاده کنید</div></div></div>
  <div class="thead"><span></span><span>عنوان تسک</span><span>وضعیت</span><span>تاریخ ثبت</span><span></span></div>
  @forelse ($tasks as $t)
    @include('tasks._row', ['mode' => 'manage'])
  @empty
    <div class="empty">تسکی برای این فرد ثبت نشده است.</div>
  @endforelse
</div>
@endsection

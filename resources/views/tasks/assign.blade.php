@extends('tasks.layout')
@use('App\Support\Fa')
@section('content')
<form method="post" action="/tasks/assign" class="card">
  @csrf
  <b>نوشتن تسک برای دیگران</b>
  <label>برای چه کسی؟</label>
  <select name="target" onchange="document.getElementById('bu').hidden=this.value!=='user';document.getElementById('bs').hidden=this.value!=='section'">
    <option value="user">یک نفر</option>
    <option value="section">یک بخش</option>
    <option value="all">همه</option>
  </select>
  <div id="bu">
    <label>نام</label>
    <select name="user_id">
      @foreach ($users as $x)<option value="{{ $x->id }}">{{ $x->name }}@if ($x->section) — {{ $sections[$x->section] ?? '' }}@endif</option>@endforeach
    </select>
  </div>
  <div id="bs" hidden>
    <label>بخش</label>
    <select name="section">@foreach ($sections as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
  </div>
  <label>تسک</label>
  <input name="title" maxlength="200" required>
  <label>توضیح (اختیاری)</label>
  <textarea name="note" rows="2" maxlength="1000"></textarea>
  <button class="btn">ثبت تسک</button>
  <div class="sm">برای «بخش» یا «همه»، برای هر نفر یک تسک جداگانه ساخته می‌شود تا تذکر هر کس جدا باشد.</div>
</form>

<h3>تسک‌هایی که نوشته‌ام</h3>
@forelse ($groups as $g)
  @php($t = $g->first())
  <div class="card">
    <b>{{ $t->title }}</b>
    <span class="sm">· {{ $g->count() > 1 ? 'برای ' . Fa::num($g->count()) . ' نفر' : $t->uname }}</span>
    @if ($t->note)<div class="sm">{!! nl2br(e($t->note)) !!}</div>@endif
    <div class="sm">ثبت: {{ Fa::dt($t->created_at) }}</div>
    <details style="margin-top:8px">
      <summary class="btn gh">ویرایش</summary>
      <form method="post" action="/tasks/assign/{{ $t->id }}/update">
        @csrf
        <label>عنوان</label>
        <input name="title" maxlength="200" value="{{ $t->title }}" required>
        <label>توضیح</label>
        <textarea name="note" rows="2" maxlength="1000">{{ $t->note }}</textarea>
        <button class="btn">ذخیره{{ $g->count() > 1 ? ' برای همه' : '' }}</button>
      </form>
    </details>
    <form method="post" action="/tasks/assign/{{ $t->id }}/delete" class="inl" onsubmit="return confirm('این تسک حذف شود؟')">
      @csrf
      <button class="btn no">حذف{{ $g->count() > 1 ? ' برای همه' : '' }}</button>
    </form>
  </div>
@empty
  <div class="sm">هنوز تسکی برای دیگران ننوشته‌اید.</div>
@endforelse
@endsection

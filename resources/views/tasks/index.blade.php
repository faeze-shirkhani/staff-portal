@extends('tasks.layout')
@use('App\Support\Fa')
@section('content')
<div class="card">
  <b>تسک‌های هفته</b>
  <div class="sm">{{ Fa::day($wk) }} {{ Fa::date($wk) }} تا {{ Fa::day($end) }} {{ Fa::date($end) }}</div>
  <div class="steps">
    <span>{{ Fa::num($done) }} انجام شده از {{ Fa::num($total) }}</span>
    @if ($back < 12)<a href="/tasks?back={{ $back + 1 }}">هفته‌ی قبل ›</a>@endif
    @if ($back > 0)<a href="/tasks?back={{ $back - 1 }}">‹ هفته‌ی بعد</a>@endif
  </div>
  @if (!$isCurrent)<div class="sm">این هفته گذشته است و فقط برای مشاهده است.</div>@endif
</div>

@if ($isCurrent)
<form method="post" action="/tasks" class="card">
  @csrf
  <label>تسک جدید</label>
  <input name="title" maxlength="200" required>
  <label>توضیح (اختیاری)</label>
  <textarea name="note" rows="2" maxlength="1000"></textarea>
  <button class="btn">افزودن تسک</button>
</form>
@endif

@forelse ($tasks as $t)
<div class="card">
  <b>{{ $t->title }}</b>
  <span class="b {{ $t->status === 'done' ? 'ok-t' : 'wa-t' }}">{{ $t->status === 'done' ? 'انجام شد' : 'انجام نشده' }}</span>
  @if ($t->carry_count > 0)<span class="b wa-t">منتقل‌شده × {{ Fa::num($t->carry_count) }}</span>@endif
  @if ($t->created_by != $t->user_id)<span class="b">از طرف مدیر</span>@endif
  @if ($t->note)<div class="sm">{{ $t->note }}</div>@endif
  <div class="sm">ثبت: {{ Fa::dt($t->created_at) }}@if ($t->done_at) · انجام: {{ Fa::dt($t->done_at) }}@endif</div>
  @if ($isCurrent)
    <form method="post" action="/tasks/{{ $t->id }}/toggle" style="display:inline">
      @csrf
      <button class="btn {{ $t->status === 'done' ? 'gh' : 'ok' }}">{{ $t->status === 'done' ? 'برگرداندن به انجام‌نشده' : 'انجام شد' }}</button>
    </form>
    @if ($t->created_by == $t->user_id)
      <details style="margin-top:8px">
        <summary class="btn gh" style="display:inline-block;list-style:none;cursor:pointer">ویرایش</summary>
        <form method="post" action="/tasks/{{ $t->id }}/update">
          @csrf
          <label>عنوان</label>
          <input name="title" maxlength="200" value="{{ $t->title }}" required>
          <label>توضیح</label>
          <textarea name="note" rows="2" maxlength="1000">{{ $t->note }}</textarea>
          <button class="btn">ذخیره</button>
        </form>
      </details>
      <form method="post" action="/tasks/{{ $t->id }}/delete" style="display:inline" onsubmit="return confirm('این تسک حذف شود؟')">
        @csrf
        <button class="btn no">حذف</button>
      </form>
    @endif
  @endif
</div>
@empty
<div class="sm">تسکی برای این هفته ثبت نشده است.</div>
@endforelse
@endsection

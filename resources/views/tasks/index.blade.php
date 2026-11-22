@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'تسک‌های من')
@section('body')
<form method="post" action="/tasks" class="card">
  @csrf
  <label>تسک جدید برای خودم</label>
  <input name="title" maxlength="200" required>
  <label>توضیح (اختیاری)</label>
  <textarea name="note" rows="2" maxlength="1000"></textarea>
  <button class="btn">افزودن</button>
</form>

<h3>تسک‌های من ({{ Fa::num($tasks->count()) }})</h3>
@forelse ($tasks as $t)
  @php($fl = $flags->get($t->id, collect()))
  @php($ob = $objs->get($t->id, collect()))
  @php($open = $fl->whereNull('resolved_at')->first())
  <div class="card {{ $open ? 'redcard' : '' }}">
    <b>{{ $t->title }}</b>
    @if ($open)<span class="b no-t">تذکر باز</span>@endif
    @if ($t->created_by != $t->user_id)<span class="sm">· نوشته‌ی {{ $t->author }}</span>@endif
    @if ($t->note)<div class="sm">{!! nl2br(e($t->note)) !!}</div>@endif
    <div class="sm">ثبت: {{ Fa::dt($t->created_at) }}</div>
    @include('tasks._flags', ['fl' => $fl])
    @include('tasks._objs', ['ob' => $ob])
    <details style="margin-top:8px">
      <summary class="btn gh">اعتراض یا توضیح</summary>
      <form method="post" action="/tasks/{{ $t->id }}/objection">
        @csrf
        <label>اگر نسبت به این تسک اعتراضی دارید، توضیح خود را بنویسید؛ بررسی خواهد شد.</label>
        <textarea name="body" rows="3" maxlength="1000" required></textarea>
        <button class="btn">ثبت اعتراض</button>
      </form>
    </details>
    @if ($t->created_by == $t->user_id)
      <details style="margin-top:8px">
        <summary class="btn gh">ویرایش</summary>
        <form method="post" action="/tasks/{{ $t->id }}/update">
          @csrf
          <label>عنوان</label>
          <input name="title" maxlength="200" value="{{ $t->title }}" required>
          <label>توضیح</label>
          <textarea name="note" rows="2" maxlength="1000">{{ $t->note }}</textarea>
          <button class="btn">ذخیره</button>
        </form>
      </details>
      @if ($fl->isEmpty())
        <form method="post" action="/tasks/{{ $t->id }}/delete" class="inl" onsubmit="return confirm('این تسک حذف شود؟')">
          @csrf
          <button class="btn no">حذف</button>
        </form>
      @endif
    @endif
  </div>
@empty
  <div class="sm">تسکی ثبت نشده است.</div>
@endforelse
@endsection

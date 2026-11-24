@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'اعتراض‌ها')
@section('body')
<div class="sm" style="margin-bottom:10px">برای دیدن پاسخ یا پاسخ دادن، روی ردیف (آیکن 💬) کلیک کنید. نقطه‌ی قرمز یعنی هنوز پاسخی داده نشده.</div>
@if ($rows->count())
<div class="osheet">
  <div class="ohead"><span>نام</span><span>تسک</span><span>اعتراض</span><span>تاریخ</span><span></span></div>
  @foreach ($rows as $o)
    <details class="orow {{ $o->reply ? 'answered' : 'pending' }}">
      <summary>
        <span class="c-name"><b>{{ $o->uname }}</b></span>
        <span class="c-task">{{ $o->title }}</span>
        <span class="c-body">{!! nl2br(e($o->body)) !!}</span>
        <span class="c-date sm">{{ Fa::dt($o->created_at) }}</span>
        <span class="c-ic" title="{{ $o->reply ? 'مشاهده‌ی پاسخ' : 'پاسخ دادن' }}">💬</span>
      </summary>
      <div class="obody">
        @if ($o->reply)
          <div><b>پاسخ {{ $o->pname }}:</b> {!! nl2br(e($o->reply)) !!}</div>
          <div class="sm">{{ Fa::dt($o->replied_at) }}</div>
        @else
          <div class="sm">هنوز پاسخی داده نشده.</div>
        @endif
        <form method="post" action="/tasks/objection/{{ $o->id }}/reply">
          @csrf
          <textarea name="reply" rows="2" maxlength="1000" required placeholder="پاسخ را بنویسید">{{ $o->reply }}</textarea>
          <button class="btn">{{ $o->reply ? 'ویرایش پاسخ' : 'ثبت پاسخ' }}</button>
        </form>
      </div>
    </details>
  @endforeach
</div>
@else
  <div class="sm">اعتراضی ثبت نشده است.</div>
@endif
@endsection
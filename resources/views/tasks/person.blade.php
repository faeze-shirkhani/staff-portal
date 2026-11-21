@extends('tasks.layout')
@use('App\Support\Fa')
@section('content')
<div class="card">
  <b>{{ $person->name }}</b> <span class="sm">{{ $sections[$person->section] ?? '' }}</span>
  <div><a href="/tasks/team">‹ فهرست افراد</a></div>
</div>
@forelse ($tasks as $t)
  @php($fl = $flags->get($t->id, collect()))
  @php($ob = $objs->get($t->id, collect()))
  @php($open = $fl->whereNull('resolved_at')->first())
  <div class="card {{ $open ? 'redcard' : '' }}">
    <b>{{ $t->title }}</b>
    @if ($open)<span class="b no-t">تذکر باز</span>@endif
    <span class="sm">· نوشته‌ی {{ $t->author }}</span>
    @if ($t->note)<div class="sm">{!! nl2br(e($t->note)) !!}</div>@endif
    <div class="sm">ثبت: {{ Fa::dt($t->created_at) }}</div>
    @include('tasks._flags', ['fl' => $fl])
    @include('tasks._objs', ['ob' => $ob, 'manage' => true])
    @if ($open)
      <form method="post" action="/tasks/flag/{{ $open->id }}/resolve" class="inl" onsubmit="return confirm('تذکر برداشته شود؟')">
        @csrf
        <button class="btn ok">رعایت شد؛ تذکر برداشته شود</button>
      </form>
    @else
      <details style="margin-top:8px">
        <summary class="btn no">انجام نشده؛ تذکر بده</summary>
        <form method="post" action="/tasks/{{ $t->id }}/flag">
          @csrf
          <label>توضیح تذکر (مثلاً: دیر می‌آیی)</label>
          <textarea name="note" rows="3" maxlength="1000" required></textarea>
          <button class="btn no">ثبت تذکر</button>
        </form>
      </details>
    @endif
  </div>
@empty
  <div class="sm">تسکی برای این فرد ثبت نشده است.</div>
@endforelse
@endsection

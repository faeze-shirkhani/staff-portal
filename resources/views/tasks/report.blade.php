@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'گزارش تسک')
@section('nonotice', '1')
@section('body')
@php
  $qs = http_build_query(['jy' => $jy, 'jm' => $jm, 'mode' => $mode, 'section' => $section, 'q' => $q]);
  $period = ($jm ? Fa::MONTHS[$jm - 1] . ' ' : 'کل سال ') . Fa::num($jy);
  $endLbl = $jm ? 'پایان ماه' : 'پایان سال';
@endphp
<form method="get" action="/tasks/report" class="card noprint" onchange="this.submit()">
<div class="fl3">
    <div><label>ماه</label>
      <select name="jm"><option value="0" @selected($jm == 0)>همه (کل سال)</option>@foreach (Fa::MONTHS as $i => $mn)<option value="{{ $i + 1 }}" @selected($jm == $i + 1)>{{ $mn }}</option>@endforeach</select></div>
    <div><label>سال</label><input name="jy" type="number" value="{{ $jy }}"></div>
    <div><label>بخش</label>
      <select name="section"><option value="">همه بخش‌ها</option>@foreach ($sections as $k => $v)<option value="{{ $k }}" @selected($section === $k)>{{ $v }}</option>@endforeach</select></div>
    <div><label>نوع گزارش</label>
      <select name="mode"><option value="flags" @selected($mode === 'flags')>فقط تسک‌های تذکردار</option><option value="all" @selected($mode === 'all')>همه‌ی تسک‌ها</option></select></div>
    <div><label>جست‌وجوی نام</label><input name="q" value="{{ $q }}"></div>
  </div>
  <div class="acts">
    <button class="btn">نمایش گزارش</button>
    <a class="btn gh" href="/tasks/report.xlsx?{{ $qs }}" style="text-decoration:none;display:inline-block">دانلود اکسل</a>
    <button type="button" class="btn gh" onclick="window.print()">چاپ / PDF</button>
  </div>
</form>

<div class="rep">
  <div class="rhead">
    <div class="rt">{{ $mode === 'all' ? 'گزارش همه‌ی تسک‌ها' : 'گزارش تسک‌ها و تذکرها' }}</div>
    <div class="rs">{{ $period }}@if ($section) · بخش {{ $sections[$section] }}@endif</div>
  </div>
  <div class="chips">
    <div class="chip2"><span class="ci">@include('tasks._icon', ['n' => 'users'])</span><div><b>{{ Fa::num(count($people)) }}</b><span class="l">{{ $mode === 'all' ? 'تعداد افراد' : 'نفر دارای تذکر' }}</span></div></div>
    @if ($mode === 'all')
      <div class="chip2"><span class="ci">@include('tasks._icon', ['n' => 'list'])</span><div><b>{{ Fa::num($tasks) }}</b><span class="l">تعداد تسک</span></div></div>
      <div class="chip2"><span class="ci r">@include('tasks._icon', ['n' => 'flag'])</span><div><b>{{ Fa::num($flagged) }}</b><span class="l">تسک تذکردار</span></div></div>
    @else
      <div class="chip2"><span class="ci r">@include('tasks._icon', ['n' => 'flag'])</span><div><b>{{ Fa::num($flags) }}</b><span class="l">تعداد تذکر</span></div></div>
    @endif
    <div class="chip2"><span class="ci w">@include('tasks._icon', ['n' => 'hourglass'])</span><div><b>{{ Fa::num($open) }}</b><span class="l">هنوز باز در {{ $endLbl }}</span></div></div>
  </div>

  @forelse ($people as $p)
    <div class="pcard">
      <div class="ph">
        <span class="tav sm">{{ mb_substr($p['name'], 0, 1) }}</span>
        <div><b>{{ $p['name'] }}</b> <span class="sm">{{ $sections[$p['section']] ?? '' }}</span></div>
        @if ($mode === 'all')
          <span class="pill {{ $p['open'] ? 'red' : ($p['flagged'] ? 'ok' : 'neutral') }}">{{ $p['open'] ? Fa::num($p['open']) . ' تذکر باز' : ($p['flagged'] ? 'همه رفع شد' : 'بدون تذکر') }}</span>
        @else
          <span class="pill {{ $p['open'] ? 'red' : 'ok' }}">{{ $p['open'] ? Fa::num($p['open']) . ' تذکر باز' : 'همه رفع شد' }}</span>
        @endif
      </div>

      @if ($mode === 'all')
       @foreach ($p['tasks'] as $t)
  <div class="lrow col {{ $t['open'] ? 'tflag' : '' }}">
    <div class="l1"><b>{{ $t['title'] }}</b></div>
    <div class="sm">{{ $t['author'] ? 'نوشته‌ی ' . $t['author'] : 'نوشته‌ی خودش' }} · {{ Fa::dt($t['at']) }}</div>
  </div>
@endforeach
      @else
        @foreach ($p['flags'] as $f)
          <div class="lrow col">
            <div class="l1"><b>{{ $f['title'] }}</b> <span class="pill {{ $f['open'] ? 'redo' : 'ok' }}">{{ $f['open'] ? 'هنوز باز' : 'رفع شد' }}</span></div>
            <div class="sm">{!! nl2br(e($f['note'])) !!}</div>
            <div class="sm">{{ $f['by'] }} · {{ Fa::dt($f['at']) }}@if ($f['resolved_at']) · رفع: {{ Fa::dt($f['resolved_at']) }}@endif</div>
          </div>
        @endforeach
      @endif
    </div>
  @empty
    <div class="sm" style="padding:0 14px 14px">{{ $mode === 'all' ? 'در این بازه تسکی ثبت نشده است.' : 'در این بازه تذکری ثبت نشده است.' }}</div>
  @endforelse
    <div class="rfoot">تاریخ تهیه گزارش: {{ Fa::date(\App\Support\LeaveRules::today()) }}</div>
</div>

<script>
  document.querySelectorAll('form[action="/tasks/report"] select, form[action="/tasks/report"] input')
    .forEach(function (el) {
      el.addEventListener('change', function () { el.form.submit(); });
    });
</script>
@endsectionس
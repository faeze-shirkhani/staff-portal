@extends('tasks.layout')
@use('App\Support\Fa')
@section('content')
@php($qs = http_build_query(['jy' => $jy, 'jm' => $jm, 'section' => $section, 'q' => $q]))
<form method="get" action="/tasks/report" class="card noprint">
  <div class="row">
    <div><label>ماه</label>
      <select name="jm">@foreach (Fa::MONTHS as $i => $mn)<option value="{{ $i + 1 }}" @selected($jm == $i + 1)>{{ $mn }}</option>@endforeach</select></div>
    <div><label>سال</label><input name="jy" type="number" value="{{ $jy }}"></div>
  </div>
  <div class="row">
    <div><label>بخش</label>
      <select name="section"><option value="">همه بخش‌ها</option>@foreach ($sections as $k => $v)<option value="{{ $k }}" @selected($section === $k)>{{ $v }}</option>@endforeach</select></div>
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
    <div class="rt">گزارش تسک‌ها و تذکرها</div>
    <div class="rs">{{ Fa::MONTHS[$jm - 1] }} {{ Fa::num($jy) }}@if ($section) · بخش {{ $sections[$section] }}@endif</div>
  </div>
  <div class="chips">
    <div class="chip"><b>{{ Fa::num(count($people)) }}</b><span>نفر دارای تذکر</span></div>
    <div class="chip"><b>{{ Fa::num($flags) }}</b><span>تعداد تذکر</span></div>
    <div class="chip"><b>{{ Fa::num($open) }}</b><span>هنوز باز در پایان ماه</span></div>
  </div>
  @forelse ($people as $p)
    <div class="pcard">
      <div class="ph">
        <div><b>{{ $p['name'] }}</b> <span class="sm">{{ $sections[$p['section']] ?? '' }}</span></div>
        <div class="pt">{{ $p['open'] ? Fa::num($p['open']) . ' باز' : 'همه رفع شد' }}</div>
      </div>
      @foreach ($p['flags'] as $f)
        <div class="lrow" style="flex-direction:column;gap:2px">
          <div><b>{{ $f['title'] }}</b> <span class="b {{ $f['open'] ? 'no-t' : 'ok-t' }}">{{ $f['open'] ? 'هنوز باز' : 'رفع شد' }}</span></div>
          <div class="sm">{!! nl2br(e($f['note'])) !!}</div>
          <div class="sm">{{ $f['by'] }} · {{ Fa::dt($f['at']) }}@if ($f['resolved_at']) · رفع: {{ Fa::dt($f['resolved_at']) }}@endif</div>
        </div>
      @endforeach
    </div>
  @empty
    <div class="sm" style="padding:0 14px 14px">در این ماه تذکری ثبت نشده است.</div>
  @endforelse
  <div class="rfoot">تاریخ تهیه گزارش: {{ Fa::date(\App\Support\LeaveRules::today()) }}</div>
</div>
@endsection

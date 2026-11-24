@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'نوشتن تسک')
@section('body')
<form method="post" action="/tasks/assign" class="card aform">
  @csrf
  <h3 class="ah">✏️ تسک جدید</h3>
  <div class="atarget" id="atarget">
    <label class="on"><input type="radio" name="target" value="user" checked> یک نفر</label>
    <label><input type="radio" name="target" value="section"> یک بخش</label>
    <label><input type="radio" name="target" value="all"> همه</label>
  </div>
  <div class="agrid">
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
    <div class="afull"><label>عنوان تسک</label><input name="title" maxlength="200" required></div>
    <div class="afull"><label>توضیح (اختیاری)</label><textarea name="note" rows="2" maxlength="1000"></textarea></div>
  </div>
  <div class="ahint" id="ahint"></div>
  <button class="btn">ثبت تسک</button>
</form>

<div class="osheet a4" style="margin-bottom:14px">
  <div class="tlist-h"><div><h3 style="margin:0;font-size:17px">تسک‌های گروهی ({{ Fa::num($templates->count()) }})</h3><div class="sm">برای همه یا یک بخش</div></div></div>
  @if ($templates->count())
    <div class="ohead"><span>تسک</span><span>برای</span><span>تاریخ ثبت</span><span></span></div>
  @endif
  @forelse ($templates as $g)
    <details class="orow">
      <summary>
        <span class="a-title"><b>{{ $g->title }}</b>@if ($g->note)<small>{{ \Illuminate\Support\Str::limit($g->note, 80) }}</small>@endif</span>
        <span><span class="pill neutral">{{ $g->scope === 'all' ? 'همه' : 'بخش ' . ($sections[$g->section] ?? '') }}</span> <span class="sm">{{ Fa::num($counts->get($g->id, 0)) }} نفر</span></span>
        <span class="sm">{{ Fa::dt($g->created_at) }}</span>
        <span class="c-ic" title="ویرایش">✏️</span>
      </summary>
      <div class="obody">
        <form method="post" action="/tasks/assign/group/{{ $g->id }}/update">
          @csrf
          <label>عنوان</label>
          <input name="title" maxlength="200" value="{{ $g->title }}" required>
          <label>توضیح</label>
          <textarea name="note" rows="2" maxlength="1000">{{ $g->note }}</textarea>
          <button class="btn">ذخیره برای همه</button>
        </form>
        <form method="post" action="/tasks/assign/group/{{ $g->id }}/delete" class="inl" onsubmit="return confirm('این تسک برای همه حذف شود؟')">
          @csrf
          <button class="btn no">حذف برای همه</button>
        </form>
      </div>
    </details>
  @empty
    <div class="empty">تسک گروهی ثبت نشده است.</div>
  @endforelse
</div>

<div class="osheet a4">
  <div class="tlist-h"><div><h3 style="margin:0;font-size:17px">تسک‌هایی که برای یک نفر نوشته‌ام ({{ Fa::num($singles->count()) }})</h3></div></div>
  @if ($singles->count())
    <div class="ohead"><span>تسک</span><span>برای</span><span>تاریخ ثبت</span><span></span></div>
  @endif
  @forelse ($singles as $t)
    <details class="orow">
      <summary>
        <span class="a-title"><b>{{ $t->title }}</b>@if ($t->note)<small>{{ \Illuminate\Support\Str::limit($t->note, 80) }}</small>@endif</span>
        <span><b>{{ $t->uname }}</b></span>
        <span class="sm">{{ Fa::dt($t->created_at) }}</span>
        <span class="c-ic" title="ویرایش">✏️</span>
      </summary>
      <div class="obody">
        <form method="post" action="/tasks/assign/{{ $t->id }}/update">
          @csrf
          <label>عنوان</label>
          <input name="title" maxlength="200" value="{{ $t->title }}" required>
          <label>توضیح</label>
          <textarea name="note" rows="2" maxlength="1000">{{ $t->note }}</textarea>
          <button class="btn">ذخیره</button>
        </form>
        <form method="post" action="/tasks/assign/{{ $t->id }}/delete" class="inl" onsubmit="return confirm('این تسک حذف شود؟')">
          @csrf
          <button class="btn no">حذف</button>
        </form>
      </div>
    </details>
  @empty
    <div class="empty">تسک تکی ثبت نشده است.</div>
  @endforelse
</div>

<script>
(function () {
  var box = document.getElementById('atarget');
  var hints = {
    user: '',
    section: 'برای هر نفرِ آن بخش یک تسک جداگانه ساخته می‌شود تا تذکر هر کس جدا باشد. کسانی که بعداً ثبت‌نام کنند هم این تسک را می‌گیرند.',
    all: 'برای هر نفر یک تسک جداگانه ساخته می‌شود تا تذکر هر کس جدا باشد. کسانی که بعداً ثبت‌نام کنند هم این تسک را می‌گیرند.'
  };
  box.addEventListener('change', function () {
    var v = box.querySelector('input:checked').value;
    box.querySelectorAll('label').forEach(function (l) { l.classList.toggle('on', l.querySelector('input').checked); });
    document.getElementById('bu').hidden = v !== 'user';
    document.getElementById('bs').hidden = v !== 'section';
    document.getElementById('ahint').textContent = hints[v];
  });
})();
</script>
@endsection
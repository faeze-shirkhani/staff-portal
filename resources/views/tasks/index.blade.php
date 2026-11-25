@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'تسک‌های من')
@section('body')

<dialog id="guideDlg" class="gdlg">
  <div class="gd">
    <div class="gd-h"><b>راهنمای تسک‌های من</b><button type="button" class="gd-x" data-close aria-label="بستن">✕</button></div>
    <ul>
      <li>اینجا می‌توانید تسک‌های خودتان را اضافه و مدیریت کنید.</li>
      <li>وظایفی که مدیریت برای شما تعریف می‌کند هم همین‌جا ثبت می‌شود.</li>
      <li>اگر وظیفه‌ای انجام نشود، مدیریت برای آن تذکر ثبت می‌کند و شما می‌توانید به آن اعتراض بنویسید.</li>
      <li>برای دیدن توضیحات، تذکرها و اعتراض‌های هر تسک، یک بار روی همان تسک کلیک کنید.</li>
    </ul>
    <div class="gd-f"><button type="button" class="btn" data-close>متوجه شدم</button></div>
  </div>
</dialog>
<script>
(function () {
  var d = document.getElementById('guideDlg'), KEY = 'guideSeen:tasks';
  if (!d || !d.showModal) return;
  function seen() { try { localStorage.setItem(KEY, '1'); } catch (e) {} }
  function open() { if (!d.open) d.showModal(); }
  function close() { seen(); d.close(); }
  document.addEventListener('click', function (e) { if (e.target.closest('[data-open-guide]')) open(); });
  d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('[data-close]')) close(); });
  d.addEventListener('cancel', seen);   // کلید Esc
  document.addEventListener('DOMContentLoaded', function () {
    try { if (!localStorage.getItem(KEY)) open(); } catch (e) {}   // فقط بار اول
  });
})();
</script>

<form method="post" action="/tasks" class="card addrow">
  @csrf
  <input name="title" placeholder="تسک جدید برای خودم…" maxlength="200" required>
  <button class="btn">افزودن</button>
  <details>
    <summary class="sbtn">افزودن توضیح</summary>
    <textarea name="note" rows="2" maxlength="1000" placeholder="توضیح (اختیاری)"></textarea>
  </details>
</form>

<div class="tlist">
  <div class="tlist-h">
    <div><h3>تسک‌های من ({{ Fa::num($tasks->count()) }}) <button type="button" class="ghelp" data-open-guide title="راهنما" aria-label="راهنما">؟</button></h3><div class="sm">تسک‌هایی که برای شما نوشته شده یا خودتان نوشته‌اید</div></div>
    <input id="tq" class="tsearch" placeholder="جست‌وجو در تسک‌ها">
  </div>
  <div class="thead"><span></span><span>عنوان تسک</span><span>وضعیت</span><span>تاریخ ثبت</span><span></span></div>
  @forelse ($tasks as $t)
    @include('tasks._row', ['mode' => 'mine'])
  @empty
    <div class="empty">تسکی ثبت نشده است.</div>
  @endforelse
</div>

@if ($inactive->count())
  <details class="card">
    <summary><b>تسک‌های غیرفعال ({{ Fa::num($inactive->count()) }})</b></summary>
    @foreach ($inactive as $t)
      <div class="irow">
        <span>{{ $t->title }}</span>
        <form method="post" action="/tasks/{{ $t->id }}/restore">@csrf<button class="sbtn ok">فعال‌سازی</button></form>
      </div>
    @endforeach
  </details>
@endif
<script>
document.getElementById('tq').addEventListener('input', function () {
  var q = this.value.trim();
  document.querySelectorAll('.trow').forEach(function (r) { r.style.display = !q || r.dataset.q.indexOf(q) > -1 ? '' : 'none'; });
});
</script>
@endsection


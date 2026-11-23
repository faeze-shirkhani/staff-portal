@extends('tasks.layout')
@use('App\Support\Fa')
@section('title', 'تسک‌های من')
@section('body')
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
    <h3>تسک‌های من ({{ Fa::num($tasks->count()) }})</h3>
    <input id="tq" class="tsearch" placeholder="جست‌وجو در تسک‌ها">
  </div>
  @forelse ($tasks as $t)
    @include('tasks._row', ['mode' => 'mine'])
  @empty
    <div class="empty">تسکی ثبت نشده است.</div>
  @endforelse
</div>
<script>
document.getElementById('tq').addEventListener('input', function () {
  var q = this.value.trim();
  document.querySelectorAll('.trow').forEach(function (r) { r.style.display = !q || r.dataset.q.indexOf(q) > -1 ? '' : 'none'; });
});
</script>
@endsection

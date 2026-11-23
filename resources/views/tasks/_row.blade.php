@use('App\Support\Fa')
@php($fl = $flags->get($t->id, collect()))
@php($ob = $objs->get($t->id, collect()))
@php($open = $fl->whereNull('resolved_at')->first())
@php($unrep = $ob->whereNull('reply')->count())
@php($red = $open || $unrep)
@php($own = $t->created_by == $t->user_id)
<div class="trow {{ $red ? 'red' : '' }}" data-q="{{ $t->title }} {{ $t->note }}">
  <input type="checkbox" class="tg c-a" id="a{{ $t->id }}" hidden>
  <input type="checkbox" class="tg c-e" id="e{{ $t->id }}" hidden>
  <input type="checkbox" class="tg c-o" id="o{{ $t->id }}" hidden>
  <div class="tav">{{ mb_substr($t->title, 0, 1) }}</div>
  <div class="tmain">
    <div class="ttitle">{{ $t->title }}
      @if ($open)<span class="pill red">تذکر</span>@endif
      @if ($unrep)<span class="pill redo">اعتراض</span>@elseif ($ob->count())<span class="pill ok">پاسخ داده شد</span>@endif
    </div>
    <div class="tmeta">@if (!$own)نوشته‌ی {{ $t->author }} · @endif{{ Fa::dt($t->created_at) }}</div>
    @if ($t->note)<div class="tnote">{!! nl2br(e($t->note)) !!}</div>@endif
  </div>
  <div class="tact">
    @if ($ob->count())<label class="sbtn {{ $unrep ? 'red' : '' }}" for="o{{ $t->id }}">اعتراض‌ها ({{ Fa::num($ob->count()) }})</label>@endif
    @if ($mode === 'mine')
      <label class="sbtn" for="a{{ $t->id }}">اعتراض</label>
      @if ($own)
        <label class="sbtn" for="e{{ $t->id }}">ویرایش</label>
        @if ($fl->isEmpty())
          <form method="post" action="/tasks/{{ $t->id }}/delete" onsubmit="return confirm('این تسک حذف شود؟')">@csrf<button class="sbtn red">حذف</button></form>
        @endif
      @endif
    @elseif ($open)
      <form method="post" action="/tasks/flag/{{ $open->id }}/resolve" onsubmit="return confirm('تذکر برداشته شود؟')">@csrf<button class="sbtn ok">رفع تذکر</button></form>
    @else
      <label class="sbtn red" for="a{{ $t->id }}">تذکر</label>
    @endif
  </div>
  @if ($fl->count())<div class="tflags">@include('tasks._flags', ['fl' => $fl])</div>@endif
  @if ($ob->count())<div class="tpanel p-o">@include('tasks._objs', ['ob' => $ob, 'manage' => $mode === 'manage'])</div>@endif
  <div class="tpanel p-a">
    @if ($mode === 'mine')
      <form method="post" action="/tasks/{{ $t->id }}/objection">
        @csrf
        <label>اگر نسبت به این تسک اعتراضی دارید، توضیح خود را بنویسید؛ بررسی خواهد شد.</label>
        <textarea name="body" rows="3" maxlength="1000" required></textarea>
        <button class="btn">ثبت اعتراض</button>
      </form>
    @else
      <form method="post" action="/tasks/{{ $t->id }}/flag">
        @csrf
        <label>توضیح تذکر (مثلاً: دیر می‌آیی)</label>
        <textarea name="note" rows="3" maxlength="1000" required></textarea>
        <button class="btn no">ثبت تذکر</button>
      </form>
    @endif
  </div>
  @if ($mode === 'mine' && $own)
    <div class="tpanel p-e">
      <form method="post" action="/tasks/{{ $t->id }}/update">
        @csrf
        <label>عنوان</label>
        <input name="title" maxlength="200" value="{{ $t->title }}" required>
        <label>توضیح</label>
        <textarea name="note" rows="2" maxlength="1000">{{ $t->note }}</textarea>
        <button class="btn">ذخیره</button>
      </form>
    </div>
  @endif
</div>

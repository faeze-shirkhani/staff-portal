@use('App\Support\Fa')

@php($fl = $flags->get($t->id, collect()))
@php($ob = $objs->get($t->id, collect()))
@php($more = $fl->count() + $ob->count())
@php($open = $fl->whereNull('resolved_at')->first())
@php($unrep = $ob->whereNull('reply')->count())
@php($red = $open || $unrep)
@php($own = $t->created_by == $t->user_id)

<div class="trow {{ $mode === 'manage' ? 'm4' : '' }} {{ $red ? 'red' : '' }} {{ $more ? 'has-flags' : '' }}" data-id="{{ $t->id }}" data-q="{{ $t->title }} {{ $t->note }}"
     @if ($more) data-tip="برای دیدن تذکرها و اعتراض‌ها کلیک کنید · {{ Fa::num($more) }} مورد" @endif>

  <input type="checkbox" class="tg c-a" id="a{{ $t->id }}" hidden>
  <input type="checkbox" class="tg c-e" id="e{{ $t->id }}" hidden>
  <input type="checkbox" class="tg c-o" id="o{{ $t->id }}" hidden>

  <div class="tmain">
    <div class="ttitle">{{ $t->title }}</div>
    <div class="tmeta">@if (!$own)نوشته‌ی {{ $t->author }}@else نوشته‌ی خودم@endif<span class="tdate-m"> · {{ Fa::dt($t->created_at) }}</span></div>
    @if ($t->note)<div class="tnote">{!! nl2br(e($t->note)) !!}</div>@endif
    @if ($mode !== 'manage')
      <div class="stat-m">
        @if ($open)<span class="pill red">تذکر باز</span>@endif
        @if ($unrep)<span class="pill redo">اعتراض</span>@elseif ($ob->count())<span class="pill ok">پاسخ داده شد</span>@endif
      </div>
    @endif
  </div>

  @if ($mode !== 'manage')
    <div class="tstatus">
      @if ($open)<span class="pill red">تذکر باز</span>@endif
      @if ($unrep)<span class="pill redo">اعتراض</span>@elseif ($ob->count())<span class="pill ok">پاسخ داده شد</span>@endif
      @if (!$open && !$ob->count())<span class="pill neutral">عادی</span>@endif
    </div>
  @endif

  <div class="tdate">{{ Fa::date(substr($t->created_at, 0, 10)) }}<small>ساعت {{ Fa::num(\Carbon\Carbon::parse($t->created_at, config('app.timezone'))->setTimezone('Asia/Tehran')->format('H:i')) }}</small></div>

  @if ($mode === 'manage')
    <div class="tact">
      @if ($open)
        <form method="post" action="/tasks/flag/{{ $open->id }}/resolve" class="inl" onsubmit="return confirm('تذکر برداشته شود؟')">@csrf<button class="sbtn ok">رفع تذکر</button></form>
      @else
        <label for="a{{ $t->id }}" class="sbtn flagbtn">🚩 تذکر دادن</label>
      @endif
    </div>
  @else
    <details class="dd">
      <summary class="dots" aria-label="عملیات">⋯</summary>
      <div class="ddmenu">
        @if ($own)
          <span class="dis" title="این تسک را خودتان نوشته‌اید؛ اعتراض ندارد">اعتراض یا توضیح</span>
        @else
          <label for="a{{ $t->id }}">اعتراض یا توضیح</label>
        @endif
        @if ($own)
          <label for="e{{ $t->id }}">ویرایش</label>
          @if ($fl->isEmpty())
            <form method="post" action="/tasks/{{ $t->id }}/delete" onsubmit="return confirm('این تسک غیرفعال شود؟')">@csrf<button class="danger">غیرفعال کردن</button></form>
          @endif
        @endif
      </div>
    </details>
  @endif

  @if ($fl->count())
    <div class="tflags">
      @include('tasks._flags', ['fl' => $fl, 'manage' => $mode === 'manage'])
    </div>
  @endif

  @if ($ob->count())
    <div class="tpanel p-o">
      @include('tasks._objs', ['ob' => $ob, 'manage' => $mode === 'manage'])
    </div>
  @endif

  @if (!($mode === 'mine' && $own))
    <div class="tpanel p-a">
      <label for="a{{ $t->id }}" class="xbtn" title="انصراف" aria-label="انصراف">✕</label>

      @if ($mode === 'mine')
        <form method="post" action="/tasks/{{ $t->id }}/objection" class="flagform">
          @csrf
          <label>
            اگر نسبت به این تسک اعتراضی دارید، توضیح خود را بنویسید؛
            این اعتراض برای {{ $own ? 'مدیر' : $t->author . ' و مدیر' }} ارسال می‌شود.
          </label>

          <textarea name="body" rows="3" maxlength="1000" required></textarea>

          <button class="btn">ثبت اعتراض</button>
        </form>
      @else
        <form method="post" action="/tasks/{{ $t->id }}/flag" class="flagform">
          @csrf
          <div class="ff-h">🚩 تذکر برای «{{ $t->title }}»</div>
          <div class="ff-chips">
            @foreach (['دیر انجام شد', 'ناقص انجام شد', 'گزارش نداده'] as $r)
              <button type="button" class="chipb" onclick="var t=this.closest('form').note;t.value=(t.value?t.value+' · ':'')+this.textContent;t.focus()">{{ $r }}</button>
            @endforeach
          </div>
          <textarea name="note" rows="3" maxlength="1000" required placeholder="توضیح تذکر…"></textarea>
          <div class="ff-act"><button class="btn no">ثبت تذکر</button><label for="a{{ $t->id }}" class="sbtn">انصراف</label></div>
        </form>
      @endif
    </div>
  @endif

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
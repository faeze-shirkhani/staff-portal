<div class="acts">
  @if ($open)
    <form method="post" action="/tasks/manage/{{ $t->id }}/unflag">
      @csrf
      <button class="btn ok">برداشتن تذکر</button>
    </form>
  @else
    <button type="button" class="btn no" data-panel="flag-{{ $t->id }}">
      تذکر
    </button>
  @endif

  @if ($canEdit)
    <button type="button" class="btn gh" data-panel="edit-{{ $t->id }}">
      ویرایش
    </button>

    <form
      method="post"
      action="/tasks/manage/{{ $t->id }}/delete"
      onsubmit="return confirm('{{ $t->group_id ? 'این تسک برای همه‌ی اعضای گروه حذف شود؟' : 'این تسک حذف شود؟' }}')"
    >
      @csrf
      <button class="btn no">حذف</button>
    </form>
  @endif
</div>

@if (!$open)
  <div class="panel" id="flag-{{ $t->id }}">
    <form method="post" action="/tasks/manage/{{ $t->id }}/flag">
      @csrf

      <label>توضیح تذکر</label>

      <textarea
        name="note"
        rows="2"
        maxlength="1000"
        required
      ></textarea>

      <button class="btn no">ثبت تذکر</button>
    </form>
  </div>
@endif

@if ($canEdit)
  <div class="panel" id="edit-{{ $t->id }}">
    <form method="post" action="/tasks/manage/{{ $t->id }}/update">

      @csrf

      @if ($t->group_id)
        <div class="sm">
          این تسک برای یک گروه ثبت شده و تغییر روی همه‌ی اعضا اعمال می‌شود.
        </div>
      @endif

      <label>عنوان</label>

      <input
        name="title"
        maxlength="200"
        value="{{ $t->title }}"
        required
      >

      <label>توضیح</label>

      <textarea
        name="note"
        rows="2"
        maxlength="1000"
      >{{ $t->note }}</textarea>

      <button class="btn">ذخیره</button>

    </form>
  </div>
@endif

</div>

@empty
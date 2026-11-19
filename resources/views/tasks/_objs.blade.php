@use('App\Support\Fa')
@foreach ($ob as $o)
  <div class="objbox">
    <b>اعتراض:</b> {!! nl2br(e($o->body)) !!}
    <div class="sm">{{ Fa::dt($o->created_at) }}</div>
    @if ($o->reply)
      <div><b>پاسخ {{ $o->pname }}:</b> {!! nl2br(e($o->reply)) !!}</div>
      <div class="sm">{{ Fa::dt($o->replied_at) }}</div>
    @elseif (empty($manage))
      <div class="sm">در انتظار پاسخ</div>
    @endif
    @if (!empty($manage))
      <details>
        <summary class="btn gh">{{ $o->reply ? 'ویرایش پاسخ' : 'پاسخ دادن' }}</summary>
        <form method="post" action="/tasks/objection/{{ $o->id }}/reply">
          @csrf
          <textarea name="reply" rows="2" maxlength="1000" required>{{ $o->reply }}</textarea>
          <button class="btn">ثبت پاسخ</button>
        </form>
      </details>
    @endif
  </div>
@endforeach

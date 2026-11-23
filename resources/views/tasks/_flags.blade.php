@use('App\Support\Fa')
@foreach ($fl as $f)
  <div class="flagbox {{ $f->resolved_at ? 'done' : '' }}">
    <div class="fhead">
      <b>{{ $f->resolved_at ? 'تذکر رفع شد' : 'تذکر' }}</b>
      @if (!empty($manage) && !$f->resolved_at)
        <form method="post" action="/tasks/flag/{{ $f->id }}/resolve" onsubmit="return confirm('این تذکر غیرفعال شود؟')">
          @csrf<button class="sbtn">غیرفعال کردن تذکر</button>
        </form>
      @endif
    </div>
    <div>{!! nl2br(e($f->note)) !!}</div>
    <div class="sm">{{ $f->bname }} · {{ Fa::dt($f->created_at) }}@if ($f->resolved_at) · رفع: {{ Fa::dt($f->resolved_at) }}@endif</div>
  </div>
@endforeach
@use('App\Support\Fa')
@foreach ($fl as $f)
  <div class="flagbox {{ $f->resolved_at ? 'done' : '' }}">
    <b>{{ $f->resolved_at ? 'تذکر رفع شد' : 'تذکر' }}</b>
    <div>{!! nl2br(e($f->note)) !!}</div>
    <div class="sm">{{ $f->bname }} · {{ Fa::dt($f->created_at) }}@if ($f->resolved_at) · رفع: {{ Fa::dt($f->resolved_at) }}@endif</div>
  </div>
@endforeach

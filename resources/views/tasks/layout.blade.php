@extends('layouts.shell')
@section('content')
  @if (trim($__env->yieldContent('nonotice')) === '')
    @include('tasks.notice')
  @endif
  @if (session('msg'))<div class="card okm">{{ session('msg') }}</div>@endif
  @if (session('err'))<div class="card err">{{ session('err') }}</div>@endif
  @yield('body')
@endsection
@push('scripts')
<script>
/* بستن منوی ⋯ بعد از انتخاب یک گزینه یا کلیک بیرون از آن */
document.addEventListener('click', function (e) {
  document.querySelectorAll('details.dd[open]').forEach(function (d) {
    if (!d.contains(e.target) || e.target.closest('.ddmenu label')) d.removeAttribute('open');
  });
});
</script>
@endpush

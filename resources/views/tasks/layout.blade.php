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


/* باز و بسته کردن تذکرهای هر تسک با کلیک روی ردیف */
(function () {
  var KEY = 'openFlags', store = {};
  try { store = JSON.parse(sessionStorage.getItem(KEY) || '{}'); } catch (e) {}
  function save() { try { sessionStorage.setItem(KEY, JSON.stringify(store)); } catch (e) {} }

  document.querySelectorAll('.trow.has-flags').forEach(function (r) {
    if (store[location.pathname + '#' + r.dataset.id]) r.classList.add('open-flags');
  });

   document.addEventListener('click', function (e) {
    var row = e.target.closest('.trow.has-flags');
    if (!row || e.target.closest('.dd, .tflags, .tpanel, form, button, a, label, input, textarea')) return;
    var co = row.querySelector('.c-o'), k = location.pathname + '#' + row.dataset.id;
    var isOpen = row.classList.contains('open-flags') || (co && co.checked);
    if (isOpen) {                                   // بستن: همه‌چیز (تذکرها و اعتراض‌ها)
      row.classList.remove('open-flags');
      if (co) co.checked = false;
      delete store[k];
    } else {                                        // باز کردن
      row.classList.add('open-flags');
      store[k] = 1;
    }
    save();
  });
})();

</script>
@endpush

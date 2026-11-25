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

/* پنجره‌ی تأیید به‌جای confirm مرورگر */
(function () {
  document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function (f) {
    var m = /confirm\((['"])([\s\S]*?)\1\)/.exec(f.getAttribute('onsubmit') || '');
    if (!m) return;
    f.dataset.confirm = m[2];
    f.removeAttribute('onsubmit');
  });

  function ask(msg, danger, ok) {
    var bg = document.createElement('div');
    bg.className = 'cfm-bg';
    bg.innerHTML = '<div class="cfm" role="dialog" aria-modal="true"><div class="cfm-t">آیا مطمئن هستید؟</div><div class="cfm-m"></div>' +
      '<div class="cfm-a"><button type="button" class="btn ' + (danger ? 'no' : '') + '" data-y>تأیید</button>' +
      '<button type="button" class="btn gh" data-n>انصراف</button></div></div>';
    bg.querySelector('.cfm-m').textContent = msg;
    function key(e) { if (e.key === 'Escape') close(); }
    function close() { document.removeEventListener('keydown', key); bg.remove(); }
    bg.addEventListener('click', function (e) { if (e.target === bg || e.target.closest('[data-n]')) close(); });
    bg.querySelector('[data-y]').addEventListener('click', function () { close(); ok(); });
    document.addEventListener('keydown', key);
    document.body.appendChild(bg);
    bg.querySelector('[data-y]').focus();
  }

  document.addEventListener('submit', function (e) {
    var f = e.target, msg = f.dataset && f.dataset.confirm;
    if (!msg) return;
    e.preventDefault();
    ask(msg, /غیرفعال|حذف/.test(msg), function () { f.submit(); });
  }, true);
})();

</script>
@endpush

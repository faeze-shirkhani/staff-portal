@extends('layouts.shell')
@section('title', 'مرخصی')
@section('content')
@auth
<div class="card notice">
  <div class="n-sec">
    <b>توجه</b>
    <p>لطفاً هنگام ثبت درخواست مرخصی، ابتدا اطمینان حاصل کنید که فرد دیگری در بخش شما مسئولیت و وظایف کاری شما را در مدت مرخصی بر عهده خواهد گرفت.</p>
    <p>توجه داشته باشید که بخش مالی صرفاً مانده مرخصی شما را بررسی و تأیید می‌کند و تأیید نهایی درخواست مرخصی بر عهده مدیریت است.</p>
    <p>در صورتی که درخواست مرخصی شما تأیید نشد، لطفاً موضوع را مستقیماً با مدیریت پیگیری نمایید.</p>
  </div>
</div>
@endauth
<div id="app"></div>
@endsection
@push('scripts')
<script>window.onerror=function(m,u,l){var a=document.getElementById("app");if(a&&!a.children.length){a.innerHTML='<div style="padding:16px;color:#b3261e;direction:ltr;font:13px/1.6 monospace">'+m+" ("+String(u||"").split("/").pop()+":"+l+")</div>"}}</script>
@foreach (['calendar', 'core', 'auth', 'request', 'queue', 'report', 'range', 'admin', 'main'] as $f)
<script src="/leave/{{ $f }}.js?v={{ filemtime(public_path("leave/$f.js")) }}"></script>
@endforeach
@endpush
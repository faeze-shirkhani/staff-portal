@extends('layouts.shell')
@section('title', 'مرخصی')
@section('content')
<div id="app"></div>
@endsection
@push('scripts')
<script>window.onerror=function(m,u,l){var a=document.getElementById("app");if(a&&!a.children.length){a.innerHTML='<div style="padding:16px;color:#b3261e;direction:ltr;font:13px/1.6 monospace">'+m+" ("+String(u||"").split("/").pop()+":"+l+")</div>"}}</script>
@foreach (['calendar', 'core', 'auth', 'request', 'queue', 'report', 'range', 'admin', 'main'] as $f)
<script src="/leave/{{ $f }}.js?v={{ filemtime(public_path("leave/$f.js")) }}"></script>
@endforeach
@endpush

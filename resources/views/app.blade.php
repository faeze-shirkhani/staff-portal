<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>سامانه مرخصی</title>
<link rel="stylesheet" href="/leave/style.css?v={{ filemtime(public_path('leave/style.css')) }}">
<link rel="stylesheet" href="/leave/report.css?v={{ filemtime(public_path('leave/report.css')) }}">
</head>
<body>
<main id="app"></main>
<script>window.onerror=function(m,u,l){var a=document.getElementById("app");if(a&&!a.children.length){a.innerHTML='<div style="padding:16px;color:#b3261e;direction:ltr;font:13px/1.6 monospace">'+m+" ("+String(u||"").split("/").pop()+":"+l+")</div>"}}</script>
@foreach (['calendar', 'core', 'auth', 'request', 'queue', 'report', 'range', 'admin', 'main'] as $f)
<script src="/leave/{{ $f }}.js?v={{ filemtime(public_path("leave/$f.js")) }}"></script>
@endforeach
</body>
</html>

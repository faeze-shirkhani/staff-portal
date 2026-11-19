@use('App\Support\TaskAccess')
@use('App\Support\Fa')
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'تسک‌ها')</title>
<link rel="stylesheet" href="/leave/style.css?v={{ filemtime(public_path('leave/style.css')) }}">
<link rel="stylesheet" href="/leave/report.css?v={{ filemtime(public_path('leave/report.css')) }}">
<link rel="stylesheet" href="/leave/tasks.css?v={{ filemtime(public_path('leave/tasks.css')) }}">
</head>
<body>
@php($u = auth()->user())
@php($pendingObj = TaskAccess::unreplied($u))
<main>
  <div class="top noprint">
    <h1>✅ {{ $u->name }}</h1>
    <a class="btn gh" href="/" style="display:inline-block;text-decoration:none">مرخصی</a>
  </div>
  <div class="tabs noprint">
    <a href="/tasks" class="{{ request()->is('tasks') ? 'on' : '' }}">تسک‌های من</a>
  </div>
  @include('tasks.notice')
  @if (session('msg'))<div class="card okm">{{ session('msg') }}</div>@endif
  @if (session('err'))<div class="card err">{{ session('err') }}</div>@endif
  @yield('content')
</main>
</body>
</html>

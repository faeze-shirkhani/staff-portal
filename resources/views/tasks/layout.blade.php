<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'تسک‌های هفته')</title>
<link rel="stylesheet" href="/leave/style.css?v={{ filemtime(public_path('leave/style.css')) }}">
</head>
<body>
<main>
  <div class="top">
    <h1>✅ {{ auth()->user()->name }}</h1>
    <a class="btn gh" href="/" style="display:inline-block;text-decoration:none">مرخصی</a>
  </div>
  @if (session('msg'))<div class="card okm">{{ session('msg') }}</div>@endif
  @if (session('err'))<div class="card err">{{ session('err') }}</div>@endif
  @yield('content')
</main>
</body>
</html>

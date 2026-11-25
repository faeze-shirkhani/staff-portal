@use('App\Support\Nav')
@use('App\Support\Fa')
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'سامانه') | اتوماسیون هیوا سام پارت</title>
@foreach (['style', 'report', 'tasks', 'shell'] as $css)
<link rel="stylesheet" href="/leave/{{ $css }}.css?v={{ filemtime(public_path("leave/$css.css")) }}">
@endforeach
</head>
<body class="{{ auth()->check() ? 'has-shell' : '' }}">
@auth
  @php($u = auth()->user())
  @php($today = \App\Support\LeaveRules::today())
  <div class="shell">
    <aside class="side" id="side">
      <div class="brand">
        <span class="logo">👥</span>
        <div><b>اتوماسیون هیوا سام پارت</b><div class="sm">مرخصی و تسک‌ها</div></div>
      </div>
      <div class="usercard">
        <span class="av">{{ mb_substr($u->name, 0, 1) }}</span>
        <div><b>{{ $u->name }}</b>
          <div class="sm">{{ Nav::ROLES[$u->role] ?? '' }}{{ $u->section ? ' · ' . (\App\Support\LeaveRules::SECTIONS[$u->section] ?? '') : '' }}</div></div>
      </div>
      <nav class="nav" onclick="if(event.target.closest('a'))document.body.classList.remove('nav-open')">
        @foreach (Nav::groups($u) as $g)
          @if (count($g['items']))
            <div class="ntitle">{{ $g['title'] }}</div>
            @foreach ($g['items'] as $it)
              <a href="{{ $it['href'] }}" data-nav="{{ $it['id'] }}" class="{{ Nav::isActive($it) ? 'on' : '' }}">
                <span class="ic">{{ $it['icon'] }}</span><span>{{ $it['label'] }}</span>
                @if (!empty($it['badge']))<span class="cnt">{{ Fa::num($it['badge']) }}</span>@endif
              </a>
            @endforeach
          @endif
        @endforeach
      </nav>
      <form method="post" action="/logout" class="logout">@csrf<button>⎋ خروج از حساب</button></form>
    </aside>
    <div class="scrim" onclick="document.body.classList.remove('nav-open')"></div>
    <div class="main">
      <header class="topbar noprint">
        <button type="button" class="burger" aria-label="منو" onclick="document.body.classList.toggle('nav-open')">☰</button>
        <div class="crumb">فضای کاری / <b id="crumb">@yield('title')</b></div>
        <div class="tb-end"><span class="sm tb-date">{{ Fa::day($today) }}، {{ Fa::date($today) }}</span><span class="av">{{ mb_substr($u->name, 0, 1) }}</span></div>
      </header>
      <section class="page">@yield('content')</section>
    </div>
  </div>
@else
  <main>@yield('content')</main>
@endauth
@stack('scripts')
</body>
</html>

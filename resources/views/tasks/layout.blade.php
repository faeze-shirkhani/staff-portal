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
    <h1>{{ $u->name }} <span class="sm">({{ ['employee' => 'کارمند', 'approver' => 'تأییدکننده', 'boss' => 'مدیر'][$u->role] ?? '' }}{{ $u->section ? ' · ' . (\App\Support\LeaveRules::SECTIONS[$u->section] ?? '') : '' }})</span></h1>
    <form method="post" action="/logout" class="inl">@csrf<button class="btn gh">خروج</button></form>
  </div>
  <div class="mainnav noprint"><a href="/">📅 مرخصی</a><a class="on" href="/tasks">✅ تسک‌ها</a></div>
  <div class="tabs noprint">
    <a href="/tasks" class="{{ request()->is('tasks') ? 'on' : '' }}">تسک‌های من</a>
    @if (TaskAccess::canAssign($u) && class_exists(\App\Http\Controllers\TaskManageController::class))
      <a href="/tasks/assign" class="{{ request()->is('tasks/assign') ? 'on' : '' }}">نوشتن تسک</a>
      <a href="/tasks/team" class="{{ request()->is('tasks/team*') ? 'on' : '' }}">افراد و تذکر</a>
      <a href="/tasks/objections" class="{{ request()->is('tasks/objections') ? 'on' : '' }}">اعتراض‌ها@if ($pendingObj) ({{ Fa::num($pendingObj) }})@endif</a>
    @endif
    @if (TaskAccess::canReport($u) && class_exists(\App\Http\Controllers\TaskReportController::class))
      <a href="/tasks/report" class="{{ request()->is('tasks/report') ? 'on' : '' }}">گزارش تسک</a>
    @endif
  </div>
  @include('tasks.notice')
  @if (session('msg'))<div class="card okm">{{ session('msg') }}</div>@endif
  @if (session('err'))<div class="card err">{{ session('err') }}</div>@endif
  @yield('content')
</main>
</body>
</html>

<?php
use App\Http\Controllers\AdminController as A;
use App\Http\Controllers\AuthController as U;
use App\Http\Controllers\LeaveController as L;
use App\Http\Controllers\LeaveReportController as RR;
use App\Http\Controllers\ReportController as P;
use App\Http\Controllers\TaskController as TK;
use App\Http\Controllers\TaskManageController as TM;
use App\Http\Controllers\TaskReportController as TR;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('app'));
Route::get('/login', fn () => redirect('/'))->name('login'); // کاربر وارد‌نشده به صفحه‌ی ورود می‌رود

Route::middleware('auth')->prefix('tasks')->group(function () {
    // تسک‌های خودِ کاربر
    Route::get('/', [TK::class, 'index']);
    Route::post('/', [TK::class, 'store']);
    Route::post('{id}/update', [TK::class, 'update'])->whereNumber('id');
    Route::post('{id}/delete', [TK::class, 'destroy'])->whereNumber('id');
    Route::post('{id}/objection', [TK::class, 'objection'])->whereNumber('id');
    // نوشتن تسک برای دیگران، تذکر و پاسخ به اعتراض (مدیر و کسانی که اجازه دارند)
    Route::get('assign', [TM::class, 'assignForm']);
    Route::post('assign', [TM::class, 'assignStore']);
    Route::post('assign/{id}/update', [TM::class, 'assignUpdate'])->whereNumber('id');
    Route::post('assign/{id}/delete', [TM::class, 'assignDestroy'])->whereNumber('id');
    Route::post('assign/group/{gid}/update', [TM::class, 'groupUpdate']);
    Route::post('assign/group/{gid}/delete', [TM::class, 'groupDestroy']);
    Route::get('team', [TM::class, 'team']);
    Route::get('team/{uid}', [TM::class, 'person'])->whereNumber('uid');
    Route::post('{id}/flag', [TM::class, 'flag'])->whereNumber('id');
    Route::post('flag/{fid}/resolve', [TM::class, 'resolve'])->whereNumber('fid');
    Route::get('objections', [TM::class, 'objections']);
    Route::post('objection/{oid}/reply', [TM::class, 'reply'])->whereNumber('oid');
    // گزارش ماهانه
    Route::get('report', [TR::class, 'index']);
    Route::get('report.xlsx', [TR::class, 'xlsx']);
});

Route::prefix('x')->group(function () {
    Route::get('me', [U::class, 'me']);
    Route::post('register', [U::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [U::class, 'login'])->middleware('throttle:10,1');
    Route::get('logout', [U::class, 'logout']);
    Route::middleware('auth')->group(function () {
        Route::get('balance', [L::class, 'balance']);
        Route::get('list', [L::class, 'list']);
        Route::post('create', [L::class, 'create']);
        Route::post('decide', [L::class, 'decide']);
        Route::post('delete', [L::class, 'remove']);
        Route::get('report', [P::class, 'index']);
        Route::get('report/leaves', [RR::class, 'index']);
        Route::get('report/leaves.xlsx', [RR::class, 'xlsx']);
        Route::get('admin/users', [A::class, 'users']);
        Route::post('admin/users/{id}', [A::class, 'update'])->whereNumber('id');
        Route::post('admin/users/{id}/delete', [A::class, 'delete'])->whereNumber('id');
    });
});

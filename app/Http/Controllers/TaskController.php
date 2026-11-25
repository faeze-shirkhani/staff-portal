<?php
namespace App\Http\Controllers;

use App\Support\TaskAccess as A;
use App\Support\TaskSync;
use App\Support\TaskWeek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** تسک‌های خودِ کاربر: دیدن، نوشتن برای خودش و اعتراض */
class TaskController extends Controller
{
    private function mine($id)
    {
        return DB::table('tasks')->where('id', (int)$id)->where('user_id', Auth::id())->whereNull('deleted_at')->first();
    }

    public function index()
    {
        $me = Auth::user();
        TaskSync::forUser($me->id); // تسک‌های «همه/بخش» که بعد از ثبت‌نام این کاربر نوشته شده یا قبلاً نرسیده
        $tasks = DB::table('tasks as t')->leftJoin('users as a', 'a.id', '=', 't.created_by')
            ->where('t.user_id', $me->id)->whereNull('t.deleted_at')->orderByDesc('t.id')->get(['t.*', 'a.name as author']);
        [$flags, $objs] = A::attach($tasks->pluck('id')->all());
        $inactive = DB::table('tasks')->where('user_id', $me->id)->where('created_by', $me->id)->whereNotNull('deleted_at')
            ->orderByDesc('id')->limit(50)->get();
        return view('tasks.index', compact('tasks', 'flags', 'objs', 'inactive'));
    }

    public function store(Request $r)
    {
        $title = A::clean($r->input('title'), 200);
        $note = A::clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return back()->with('err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        $id = DB::table('tasks')->insertGetId(['user_id' => Auth::id(), 'created_by' => Auth::id(), 'title' => $title,
            'note' => $note !== '' ? $note : null, 'week_start' => TaskWeek::start(), 'created_at' => now(), 'updated_at' => now()]);
        A::log($id, 'created');
        return back()->with('msg', 'تسک اضافه شد.');
    }

    public function update($id, Request $r)
    {
        $t = $this->mine($id);
        if (!$t || $t->created_by != Auth::id()) return back()->with('err', 'این تسک قابل ویرایش نیست.');
        $title = A::clean($r->input('title'), 200);
        $note = A::clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return back()->with('err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        DB::table('tasks')->where('id', $t->id)->update(['title' => $title, 'note' => $note !== '' ? $note : null, 'updated_at' => now()]);
        A::log($t->id, 'edited', 'قبلاً: ' . $t->title);
        return back()->with('msg', 'تسک ویرایش شد.');
    }

    public function destroy($id)
    {
        $t = $this->mine($id);
        if (!$t || $t->created_by != Auth::id()) return back()->with('err', 'این تسک قابل غیرفعال‌کردن نیست.');
        if (DB::table('task_flags')->where('task_id', $t->id)->exists()) return back()->with('err', 'این تسک تذکر دارد و غیرفعال نمی‌شود.');
        DB::table('tasks')->where('id', $t->id)->update(['deleted_at' => now(), 'updated_at' => now()]); // پنهان می‌شود، پاک نمی‌شود
        A::log($t->id, 'deleted', $t->title);
        return back()->with('msg', 'تسک غیرفعال شد. از پایین همین صفحه می‌توانید دوباره فعالش کنید.');
    }

    public function restore($id)
    {
        $t = DB::table('tasks')->where('id', (int)$id)->where('user_id', Auth::id())->where('created_by', Auth::id())->whereNotNull('deleted_at')->first();
        if (!$t) return back()->with('err', 'این تسک پیدا نشد.');
        DB::table('tasks')->where('id', $t->id)->update(['deleted_at' => null, 'updated_at' => now()]);
        A::log($t->id, 'restored', $t->title);
        return back()->with('msg', 'تسک دوباره فعال شد.');
    }

    public function objection($id, Request $r)
    {
        $t = $this->mine($id);
        if (!$t) return back()->with('err', 'تسک پیدا نشد.');
        if ($t->created_by == $t->user_id) return back()->with('err', 'این تسک را خودتان نوشته‌اید و اعتراض ندارد.');
        $body = A::clean($r->input('body'), 1000);
        if (mb_strlen($body) < 3) return back()->with('err', 'توضیح اعتراض خود را بنویسید.');
        DB::table('task_objections')->insert(['task_id' => $t->id, 'user_id' => Auth::id(), 'body' => $body, 'created_at' => now()]);
        A::log($t->id, 'objection');
        return back()->with('msg', 'اعتراض شما ثبت شد و بررسی خواهد شد.');
    }
}

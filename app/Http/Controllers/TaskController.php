<?php
namespace App\Http\Controllers;

use App\Support\TaskWeek;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    private function clean(?string $s, int $max): string
    {
        return mb_substr(trim(preg_replace('/[ \t]+/u', ' ', str_replace(['ي', 'ك'], ['ی', 'ک'], (string)$s))), 0, $max);
    }

    private function log(int $taskId, string $action, ?string $detail = null): void
    {
        DB::table('task_logs')->insert(['task_id' => $taskId, 'user_id' => Auth::id(), 'action' => $action,
            'detail' => $detail ? mb_substr($detail, 0, 500) : null, 'created_at' => now()]);
    }

    /** تسکِ خودِ کاربر که هنوز پنهان نشده */
    private function mine($id)
    {
        return DB::table('tasks')->where('id', (int)$id)->where('user_id', Auth::id())->whereNull('deleted_at')->first();
    }

    private function back(string $kind, string $msg) { return redirect('/tasks')->with($kind, $msg); }

    public function index(Request $r)
    {
        $me = Auth::user();
        TaskWeek::rollover($me->id);
        $back = max(0, min(12, (int)$r->query('back', 0)));
        $wk = TaskWeek::start(now('Asia/Tehran')->subWeeks($back)->toDateString());
        $tasks = DB::table('tasks')->where('user_id', $me->id)->where('week_start', $wk)->whereNull('deleted_at')
            ->orderByDesc('status')->orderBy('id')->get();
        return view('tasks.index', [
            'tasks' => $tasks, 'wk' => $wk, 'end' => Carbon::parse($wk)->addDays(5)->toDateString(), 'back' => $back,
            'isCurrent' => $back === 0, 'total' => $tasks->count(), 'done' => $tasks->where('status', 'done')->count(),
        ]);
    }

    public function store(Request $r)
    {
        $title = $this->clean($r->input('title'), 200);
        $note = $this->clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return $this->back('err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        $id = DB::table('tasks')->insertGetId(['user_id' => Auth::id(), 'created_by' => Auth::id(), 'title' => $title,
            'note' => $note !== '' ? $note : null, 'week_start' => TaskWeek::start(), 'created_at' => now(), 'updated_at' => now()]);
        $this->log($id, 'created');
        return $this->back('msg', 'تسک اضافه شد.');
    }

    public function update($id, Request $r)
    {
        $t = $this->mine($id);
        if (!$t || $t->created_by != Auth::id() || $t->week_start !== TaskWeek::start()) return $this->back('err', 'این تسک قابل ویرایش نیست.');
        $title = $this->clean($r->input('title'), 200);
        $note = $this->clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return $this->back('err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        DB::table('tasks')->where('id', $t->id)->update(['title' => $title, 'note' => $note !== '' ? $note : null, 'updated_at' => now()]);
        $this->log($t->id, 'edited', 'قبلاً: ' . $t->title);
        return $this->back('msg', 'تسک ویرایش شد.');
    }

    public function toggle($id)
    {
        $t = $this->mine($id);
        if (!$t || $t->week_start !== TaskWeek::start()) return $this->back('err', 'فقط تسک‌های هفته‌ی جاری قابل تغییر است.');
        $done = $t->status !== 'done';
        DB::table('tasks')->where('id', $t->id)->update(['status' => $done ? 'done' : 'open', 'done_at' => $done ? now() : null, 'updated_at' => now()]);
        $this->log($t->id, $done ? 'done' : 'reopened');
        return $this->back('msg', $done ? 'تسک انجام‌شده ثبت شد.' : 'تسک دوباره باز شد.');
    }

    public function destroy($id)
    {
        $t = $this->mine($id);
        if (!$t || $t->created_by != Auth::id() || $t->week_start !== TaskWeek::start()) return $this->back('err', 'این تسک قابل حذف نیست.');
        DB::table('tasks')->where('id', $t->id)->update(['deleted_at' => now(), 'updated_at' => now()]); // پنهان می‌شود، پاک نمی‌شود
        $this->log($t->id, 'deleted', $t->title);
        return $this->back('msg', 'تسک حذف شد.');
    }
}

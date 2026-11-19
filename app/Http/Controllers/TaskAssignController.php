<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use App\Support\TaskWeek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskAssignController extends Controller
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

    private function isBoss(): bool { return Auth::user()->role === 'boss'; }

    private function guard(): void
    {
        if (!$this->isBoss() && !Auth::user()->can_assign_tasks) abort(403, 'دسترسی ندارید');
    }

    private function back(Request $r, string $kind, string $msg)
    {
    return redirect('/tasks/manage')->with($kind, $msg);
    }

    /** تسک بدون حذف‌شدن */
    private function find($id)
    {
        return DB::table('tasks')->where('id', (int)$id)->whereNull('deleted_at')->first();
    }

    /** آیا کاربر می‌تواند روی این تسک تذکر بدهد یا بردارد؟ */
    private function canFlag($t): bool
    {
        if (!$t || $t->user_id == Auth::id()) return false;
        return $this->isBoss() || $t->created_by == Auth::id();
    }

    /** ردیف‌های هم‌گروه (یا خودِ تسک) که نویسنده‌شان کاربر فعلی است، برای ویرایش/حذف */
  private function editable($t)
{
    if (!$t || $t->created_by != Auth::id() || $t->user_id == Auth::id()) return null;
    $q = DB::table('tasks')->whereNull('deleted_at');
    if ($t->group_id) $q->where('group_id', $t->group_id)->where('created_by', Auth::id());
    else $q->where('id', $t->id);
    return $q;
}
public function index(Request $r)
{
    $this->guard();
    $me = Auth::user();

    $q = DB::table('tasks as t')->join('users as u', 'u.id', '=', 't.user_id')
        ->whereNull('t.deleted_at')->where('t.user_id', '!=', $me->id)
        ->select('t.*', 'u.name as uname', 'u.section as usection')->orderBy('u.name')->orderByDesc('t.id');
    if (!$this->isBoss()) $q->where('t.created_by', $me->id);
    $name = R::norm((string)$r->query('q', ''));
    if ($name !== '') $q->where('u.name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $name) . '%');
    if (isset(R::SECTIONS[(string)$r->query('section')])) $q->where('u.section', $r->query('section'));
    $tasks = $q->limit(1000)->get();
    $ids = $tasks->pluck('id');

    $flags = $tasks->isEmpty() ? collect() : DB::table('task_flags as f')
        ->leftJoin('users as u', 'u.id', '=', 'f.flagged_by')
        ->whereIn('f.task_id', $ids)->select('f.*', 'u.name as by_name')->orderBy('f.id')->get()->groupBy('task_id');

    $objs = $tasks->isEmpty() ? collect() : DB::table('task_objections as o')
        ->whereIn('o.task_id', $ids)->orderBy('o.id')->get()->groupBy('task_id');

    return view('tasks.manage', [
        'tasks' => $tasks, 'flags' => $flags, 'objs' => $objs,
        'sections' => R::SECTIONS, 'me' => $me, 'isBoss' => $this->isBoss(),
        'people' => DB::table('users')->where('id', '!=', $me->id)->orderBy('name')->get(['id', 'name']),
        'fq' => (string)$r->query('q', ''), 'fsec' => (string)$r->query('section', ''),
    ]);
}
    public function store(Request $r)
    {
        $this->guard();
        $me = Auth::user();
        $title = $this->clean($r->input('title'), 200);
        $note = $this->clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return $this->back($r, 'err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');

        $target = (string)$r->input('target');
        if ($target === 'user') {
            $users = DB::table('users')->where('name', R::norm((string)$r->input('user_name')))->where('id', '!=', $me->id)->get();
            if ($users->count() !== 1) return $this->back($r, 'err', 'کاربری با این اسم پیدا نشد (اسم را دقیق از فهرست انتخاب کنید).');
        } elseif ($target === 'section') {
            $sec = (string)$r->input('section');
            if (!isset(R::SECTIONS[$sec])) return $this->back($r, 'err', 'بخش نامعتبر است.');
            $users = DB::table('users')->where('section', $sec)->where('role', '!=', 'boss')->where('id', '!=', $me->id)->get();
        } elseif ($target === 'all') {
            $users = DB::table('users')->where('role', '!=', 'boss')->where('id', '!=', $me->id)->get();
        } else {
            return $this->back($r, 'err', 'مخاطب تسک را انتخاب کنید.');
        }
        if ($users->isEmpty()) return $this->back($r, 'err', 'کسی برای این انتخاب پیدا نشد.');

        $gid = $target === 'user' ? null : (string)Str::uuid();
        $week = TaskWeek::start();
        DB::transaction(function () use ($users, $me, $title, $note, $gid, $week) {
            foreach ($users as $u) {
                $id = DB::table('tasks')->insertGetId(['user_id' => $u->id, 'created_by' => $me->id, 'group_id' => $gid,
                    'title' => $title, 'note' => $note !== '' ? $note : null, 'week_start' => $week,
                    'created_at' => now(), 'updated_at' => now()]);
                $this->log($id, 'created', 'برای: ' . $u->name);
            }
        });
        return $this->back($r, 'msg', 'تسک برای ' . $users->count() . ' نفر ثبت شد.');
    }

    public function update($id, Request $r)
    {
        $this->guard();
        $t = $this->find($id);
        $q = $this->editable($t);
        if (!$q) return $this->back($r, 'err', 'این تسک قابل ویرایش نیست.');
        $title = $this->clean($r->input('title'), 200);
        $note = $this->clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return $this->back($r, 'err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        $ids = (clone $q)->pluck('id');
        $q->update(['title' => $title, 'note' => $note !== '' ? $note : null, 'updated_at' => now()]);
        foreach ($ids as $i) $this->log($i, 'edited', 'قبلاً: ' . $t->title);
        return $this->back($r, 'msg', 'تسک ویرایش شد.');
    }

    public function destroy($id, Request $r)
    {
        $this->guard();
        $t = $this->find($id);
        $q = $this->editable($t);
        if (!$q) return $this->back($r, 'err', 'این تسک قابل حذف نیست.');
        $ids = (clone $q)->pluck('id');
        $q->update(['deleted_at' => now(), 'updated_at' => now()]); // پنهان می‌شود، پاک نمی‌شود
        foreach ($ids as $i) $this->log($i, 'deleted', $t->title);
        return $this->back($r, 'msg', 'تسک حذف شد.');
    }

    public function flag($id, Request $r)
    {
        $this->guard();
        $t = $this->find($id);
        if (!$this->canFlag($t)) return $this->back($r, 'err', 'اجازه‌ی تذکر روی این تسک را ندارید.');
        $note = $this->clean($r->input('note'), 1000);
        if (mb_strlen($note) < 2) return $this->back($r, 'err', 'توضیح تذکر را بنویسید.');
        if (DB::table('task_flags')->where('task_id', $t->id)->whereNull('resolved_at')->exists())
            return $this->back($r, 'err', 'برای این تسک تذکر باز وجود دارد.');
        DB::table('task_flags')->insert(['task_id' => $t->id, 'flagged_by' => Auth::id(), 'note' => $note,
            'flagged_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->log($t->id, 'flagged', $note);
        return $this->back($r, 'msg', 'تذکر ثبت شد.');
    }

    public function unflag($id, Request $r)
    {
        $this->guard();
        $t = $this->find($id);
        if (!$this->canFlag($t)) return $this->back($r, 'err', 'اجازه‌ی برداشتن تذکر این تسک را ندارید.');
        $n = DB::table('task_flags')->where('task_id', $t->id)->whereNull('resolved_at')
            ->update(['resolved_at' => now(), 'resolved_by' => Auth::id(), 'updated_at' => now()]);
        if ($n) $this->log($t->id, 'unflagged');
        return $this->back($r, 'msg', $n ? 'تذکر برداشته شد.' : 'تذکر بازی وجود نداشت.');
    }
}
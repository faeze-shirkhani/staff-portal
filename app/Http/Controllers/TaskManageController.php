<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use App\Support\TaskAccess as A;
use App\Support\TaskWeek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** نوشتن تسک برای دیگران، تذکر (قرمز)، پاسخ به اعتراض — برای مدیر و کسانی که اجازه‌ی نوشتن تسک دارند */
class TaskManageController extends Controller
{
    private function gate(): void { abort_unless(A::canAssign(Auth::user()), 403); }

    /** مدیر روی همه‌ی تسک‌ها اختیار دارد؛ بقیه فقط روی تسک‌هایی که خودشان برای دیگران نوشته‌اند */
    private function canManage($t): bool
    {
        $me = Auth::user();
        return $me->role === 'boss' || (A::canAssign($me) && $t->created_by == $me->id && $t->user_id != $me->id);
    }

    public function assignForm()
    {
        $this->gate(); $me = Auth::user();
        $users = DB::table('users')->where('id', '!=', $me->id)
            ->when($me->role !== 'boss', fn ($q) => $q->where('role', '!=', 'boss'))->orderBy('name')->get(['id', 'name', 'section']);
        $rows = DB::table('tasks as t')->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.created_by', $me->id)->where('t.user_id', '!=', $me->id)->whereNull('t.deleted_at')
            ->orderByDesc('t.id')->get(['t.*', 'u.name as uname']);
        $groups = $rows->groupBy(fn ($r) => $r->group_id ?: 'one' . $r->id)->values();
        return view('tasks.assign', ['users' => $users, 'groups' => $groups, 'sections' => R::SECTIONS]);
    }

    public function assignStore(Request $r)
    {
        $this->gate(); $me = Auth::user();
        $title = A::clean($r->input('title'), 200);
        $note = A::clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return back()->with('err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        $q = DB::table('users')->where('id', '!=', $me->id);
        if ($me->role !== 'boss') $q->where('role', '!=', 'boss');
        $target = (string)$r->input('target');
        if ($target === 'user') $q->where('id', (int)$r->input('user_id'));
        elseif ($target === 'section') {
            $sec = (string)$r->input('section');
            if (!isset(R::SECTIONS[$sec])) return back()->with('err', 'بخش را انتخاب کنید.');
            $q->where('section', $sec);
        } elseif ($target !== 'all') return back()->with('err', 'مخاطب را انتخاب کنید.');
        $ids = $q->pluck('id');
        if ($ids->isEmpty()) return back()->with('err', 'کسی برای این انتخاب پیدا نشد.');
        $gid = $ids->count() > 1 ? (string)Str::uuid() : null;
        DB::transaction(function () use ($ids, $gid, $me, $title, $note) {
            foreach ($ids as $uid) {
                $id = DB::table('tasks')->insertGetId(['user_id' => $uid, 'created_by' => $me->id, 'title' => $title,
                    'note' => $note !== '' ? $note : null, 'week_start' => TaskWeek::start(), 'group_id' => $gid,
                    'created_at' => now(), 'updated_at' => now()]);
                A::log($id, 'created', 'نوشته‌شده برای دیگران');
            }
        });
        return back()->with('msg', 'تسک برای ' . \App\Support\Fa::num($ids->count()) . ' نفر ثبت شد.');
    }

    /** ردیف‌های هم‌گروهِ یک تسکِ نوشته‌شده (یک نفر یا کل گروه) */
    private function groupQuery($t)
    {
        $q = DB::table('tasks')->where('created_by', Auth::id())->whereNull('deleted_at');
        return $t->group_id ? $q->where('group_id', $t->group_id) : $q->where('id', $t->id);
    }

    private function myWritten($id)
    {
        $me = Auth::user();
        $t = DB::table('tasks')->where('id', (int)$id)->whereNull('deleted_at')->first();
        return ($t && $t->created_by == $me->id && $t->user_id != $me->id && A::canAssign($me)) ? $t : null;
    }

    public function assignUpdate($id, Request $r)
    {
        $t = $this->myWritten($id);
        if (!$t) return back()->with('err', 'این تسک قابل ویرایش نیست.');
        $title = A::clean($r->input('title'), 200);
        $note = A::clean($r->input('note'), 1000);
        if (mb_strlen($title) < 2) return back()->with('err', 'عنوان تسک را بنویسید (حداقل ۲ حرف).');
        $this->groupQuery($t)->update(['title' => $title, 'note' => $note !== '' ? $note : null, 'updated_at' => now()]);
        A::log($t->id, 'edited', 'قبلاً: ' . $t->title);
        return back()->with('msg', 'تسک ویرایش شد.');
    }

    public function assignDestroy($id)
    {
        $t = $this->myWritten($id);
        if (!$t) return back()->with('err', 'این تسک قابل حذف نیست.');
        $this->groupQuery($t)->update(['deleted_at' => now(), 'updated_at' => now()]);
        A::log($t->id, 'deleted', $t->title);
        return back()->with('msg', 'تسک حذف شد.');
    }

    private function counts($me, string $kind)
    {
        $table = $kind === 'flags' ? 'task_flags as x' : 'task_objections as x';
        $q = DB::table($table)->join('tasks as t', 't.id', '=', 'x.task_id')->whereNull('t.deleted_at')
            ->whereNull($kind === 'flags' ? 'x.resolved_at' : 'x.reply');
        if ($me->role !== 'boss') $q->where('t.created_by', $me->id);
        return $q->groupBy('t.user_id')->selectRaw('t.user_id as uid, count(*) as c')->pluck('c', 'uid');
    }

    public function team(Request $r)
    {
        $this->gate(); $me = Auth::user();
        $q = DB::table('users as u')->where('u.id', '!=', $me->id)->where('u.role', '!=', 'boss');
        if ($me->role !== 'boss') {
            $q->whereIn('u.id', function ($s) use ($me) {
                $s->select('user_id')->from('tasks')->where('created_by', $me->id)->whereNull('deleted_at');
            });
        }
        $name = R::norm((string)$r->query('q', ''));
        if ($name !== '') $q->where('u.name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $name) . '%');
        $people = $q->orderBy('u.name')->limit(300)->get(['u.id', 'u.name', 'u.section']);
        return view('tasks.team', ['people' => $people, 'q' => $name, 'sections' => R::SECTIONS,
            'openFlags' => $this->counts($me, 'flags'), 'unreplied' => $this->counts($me, 'obj')]);
    }

    public function person($uid)
    {
        $this->gate(); $me = Auth::user();
        $person = DB::table('users')->where('id', (int)$uid)->first();
        abort_if(!$person || $person->id == $me->id, 404);
        $q = DB::table('tasks as t')->leftJoin('users as a', 'a.id', '=', 't.created_by')
            ->where('t.user_id', $person->id)->whereNull('t.deleted_at')->orderByDesc('t.id');
        if ($me->role !== 'boss') $q->where('t.created_by', $me->id);
        $tasks = $q->get(['t.*', 'a.name as author']);
        [$flags, $objs] = A::attach($tasks->pluck('id')->all());
        return view('tasks.person', ['person' => $person, 'tasks' => $tasks, 'flags' => $flags, 'objs' => $objs, 'sections' => R::SECTIONS]);
    }

    public function flag($id, Request $r)
    {
        $this->gate();
        $t = DB::table('tasks')->where('id', (int)$id)->whereNull('deleted_at')->first();
        if (!$t || !$this->canManage($t)) return back()->with('err', 'روی این تسک نمی‌توانید تذکر بدهید.');
        $note = A::clean($r->input('note'), 1000);
        if (mb_strlen($note) < 2) return back()->with('err', 'توضیح تذکر را بنویسید.');
        if (DB::table('task_flags')->where('task_id', $t->id)->whereNull('resolved_at')->exists()) return back()->with('err', 'این تسک یک تذکر باز دارد.');
        DB::table('task_flags')->insert(['task_id' => $t->id, 'flagged_by' => Auth::id(), 'note' => $note, 'created_at' => now()]);
        A::log($t->id, 'flagged', $note);
        return back()->with('msg', 'تذکر ثبت شد.');
    }

    public function resolve($fid)
    {
        $this->gate();
        $f = DB::table('task_flags as f')->join('tasks as t', 't.id', '=', 'f.task_id')->where('f.id', (int)$fid)->first(['f.*', 't.created_by', 't.user_id']);
        if (!$f || $f->resolved_at || !$this->canManage($f)) return back()->with('err', 'این تذکر قابل برداشتن نیست.');
        DB::table('task_flags')->where('id', $f->id)->update(['resolved_at' => now(), 'resolved_by' => Auth::id()]);
        A::log($f->task_id, 'unflagged');
        return back()->with('msg', 'تذکر برداشته شد.');
    }

    public function objections()
    {
        $this->gate(); $me = Auth::user();
        $q = DB::table('task_objections as o')->join('tasks as t', 't.id', '=', 'o.task_id')->join('users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('users as p', 'p.id', '=', 'o.replied_by')->whereNull('t.deleted_at');
        if ($me->role !== 'boss') $q->where('t.created_by', $me->id)->where('t.user_id', '!=', $me->id);
        $rows = $q->orderByRaw('o.reply IS NOT NULL')->orderByDesc('o.id')->limit(80)
            ->get(['o.*', 't.title', 'u.name as uname', 'p.name as pname']);
        return view('tasks.objections', ['rows' => $rows]);
    }

    public function reply($oid, Request $r)
    {
        $this->gate();
        $o = DB::table('task_objections as o')->join('tasks as t', 't.id', '=', 'o.task_id')->where('o.id', (int)$oid)->first(['o.*', 't.created_by', 't.user_id']);
        if (!$o || !$this->canManage($o)) return back()->with('err', 'پاسخ به این اعتراض برای شما مجاز نیست.');
        $body = A::clean($r->input('reply'), 1000);
        if (mb_strlen($body) < 2) return back()->with('err', 'پاسخ را بنویسید.');
        DB::table('task_objections')->where('id', $o->id)->update(['reply' => $body, 'replied_by' => Auth::id(), 'replied_at' => now()]);
        A::log($o->task_id, 'replied');
        return back()->with('msg', 'پاسخ ثبت شد.');
    }
}

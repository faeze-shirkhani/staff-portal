<?php
namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskAccess
{
    public static function canAssign($u): bool { return $u && ($u->role === 'boss' || !empty($u->can_assign_tasks)); }
    public static function canReport($u): bool { return $u && ($u->role === 'boss' || !empty($u->can_report)); }

    public static function clean(?string $s, int $max): string
    {
        return mb_substr(trim(preg_replace('/[ \t]+/u', ' ', str_replace(['ي', 'ك'], ['ی', 'ک'], (string)$s))), 0, $max);
    }

    public static function log(int $taskId, string $action, ?string $detail = null): void
    {
        DB::table('task_logs')->insert(['task_id' => $taskId, 'user_id' => Auth::id(), 'action' => $action,
            'detail' => $detail ? mb_substr($detail, 0, 500) : null, 'created_at' => now()]);
    }

    /** اعتراض‌های بدون پاسخ که این کاربر باید جواب بدهد (مدیر: همه) */
    public static function unreplied($u): int
    {
        if (!self::canAssign($u)) return 0;
        $q = DB::table('task_objections as o')->join('tasks as t', 't.id', '=', 'o.task_id')
            ->whereNull('o.reply')->whereNull('t.deleted_at');
        if ($u->role !== 'boss') $q->where('t.created_by', $u->id)->where('t.user_id', '!=', $u->id);
        return $q->count();
    }

    /** تذکرها و اعتراض‌های چند تسک، گروه‌بندی‌شده بر اساس شناسه‌ی تسک */
    public static function attach($ids): array
    {
        $flags = DB::table('task_flags as f')->leftJoin('users as b', 'b.id', '=', 'f.flagged_by')
            ->whereIn('f.task_id', $ids)->orderBy('f.id')->get(['f.*', 'b.name as bname'])->groupBy('task_id');
        $objs = DB::table('task_objections as o')->leftJoin('users as p', 'p.id', '=', 'o.replied_by')
            ->whereIn('o.task_id', $ids)->orderBy('o.id')->get(['o.*', 'p.name as pname'])->groupBy('task_id');
        return [$flags, $objs];
    }
}

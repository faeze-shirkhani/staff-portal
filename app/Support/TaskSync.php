<?php
namespace App\Support;

use Illuminate\Support\Facades\DB;

class TaskSync
{
    /** تسک‌های «همه» و «بخش» که این کاربر هنوز ندارد (مثلاً چون بعداً ثبت‌نام کرده) برایش ساخته می‌شود */
    public static function forUser(int $userId): void
    {
        $u = DB::table('users')->where('id', $userId)->first();
        if (!$u) return;
        $groups = DB::table('task_groups as g')->join('users as c', 'c.id', '=', 'g.created_by')
            ->whereNull('g.deleted_at')->where('g.created_by', '!=', $u->id)
            ->where(function ($w) use ($u) {
                $w->where('g.scope', 'all')->orWhere(function ($x) use ($u) { $x->where('g.scope', 'section')->where('g.section', $u->section); });
            })
            ->whereNotExists(function ($s) use ($u) {
                $s->selectRaw('1')->from('tasks as t')->whereColumn('t.group_id', 'g.id')->where('t.user_id', $u->id);
            })
            ->when($u->role === 'boss', fn ($q) => $q->where('c.role', 'boss')) // برای مدیر فقط تسکِ خودِ مدیر
            ->get(['g.*']);
        foreach ($groups as $g) {
            $id = DB::table('tasks')->insertGetId(['user_id' => $u->id, 'created_by' => $g->created_by, 'title' => $g->title,
                'note' => $g->note, 'week_start' => TaskWeek::start(), 'group_id' => $g->id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('task_logs')->insert(['task_id' => $id, 'user_id' => $g->created_by, 'action' => 'created',
                'detail' => 'افزوده‌شده‌ی خودکار برای عضو جدید', 'created_at' => now()]);
        }
    }

    public static function all(): void
    {
        foreach (DB::table('users')->pluck('id') as $id) self::forUser((int)$id);
    }
}

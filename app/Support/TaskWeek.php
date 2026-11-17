<?php
namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskWeek
{
    /** شنبه‌ی هفته‌ی شمسی که این تاریخ در آن است (میلادی، Y-m-d) */
    public static function start(?string $date = null): string
    {
        $d = $date ? Carbon::parse($date, 'Asia/Tehran') : now('Asia/Tehran');
        return $d->startOfWeek(Carbon::SATURDAY)->toDateString();
    }

    /** تسک‌های انجام‌نشده‌ی هفته‌های قبل را یک بار به هفته‌ی جاری منتقل می‌کند (تسک قدیمی در گزارش «انجام‌نشده» می‌ماند) */
    public static function rollover(int $userId): void
    {
        $cur = self::start();
        $old = DB::table('tasks')->where('user_id', $userId)->where('week_start', '<', $cur)
            ->where('status', 'open')->whereNull('deleted_at')->whereNull('rolled_to')->get();
        foreach ($old as $t) {
            DB::transaction(function () use ($t, $cur) {
                $fresh = DB::table('tasks')->where('id', $t->id)->lockForUpdate()->first();
                if (!$fresh || $fresh->rolled_to || $fresh->deleted_at) return;
                $id = DB::table('tasks')->insertGetId([
                    'user_id' => $t->user_id, 'created_by' => $t->created_by, 'title' => $t->title, 'note' => $t->note,
                    'week_start' => $cur, 'status' => 'open', 'carry_count' => $t->carry_count + 1,
                    'origin_id' => $t->origin_id ?: $t->id, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('tasks')->where('id', $t->id)->update(['rolled_to' => $id]);
                DB::table('task_logs')->insert(['task_id' => $id, 'user_id' => $t->user_id, 'action' => 'rolled',
                    'detail' => 'منتقل‌شده از تسک ' . $t->id, 'created_at' => now()]);
            });
        }
    }
}

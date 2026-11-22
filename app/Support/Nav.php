<?php
namespace App\Support;

class Nav
{
    const ROLES = ['employee' => 'کارمند', 'approver' => 'تأییدکننده', 'boss' => 'مدیر'];

    /** گروه‌های منوی کناری؛ آیتم‌های «#...» داخل صفحه‌ی مرخصی (جاوااسکریپت) باز می‌شوند و بقیه صفحه‌ی جدا هستند */
    public static function groups($u): array
    {
        $boss = $u->role === 'boss';
        $report = $boss || !empty($u->can_report);
        $leave = array_values(array_filter([
            !$boss ? ['id' => 'req', 'label' => 'درخواست مرخصی', 'icon' => '📅', 'href' => '/#req'] : null,
            in_array($u->role, ['approver', 'boss'], true) ? ['id' => 'queue', 'label' => 'صف تأیید', 'icon' => '✅', 'href' => '/#queue'] : null,
            $report ? ['id' => 'report', 'label' => 'گزارش مانده مرخصی', 'icon' => '📊', 'href' => '/#report'] : null,
            $report ? ['id' => 'range', 'label' => 'گزارش بازه‌ای مرخصی', 'icon' => '🗓', 'href' => '/#range'] : null,
            $boss ? ['id' => 'admin', 'label' => 'مدیریت کاربران', 'icon' => '👥', 'href' => '/#admin'] : null,
        ]));
        $tasks = [['id' => 't-my', 'label' => 'تسک‌های من', 'icon' => '🎯', 'href' => '/tasks', 'path' => 'tasks']];
        if (TaskAccess::canAssign($u) && class_exists(\App\Http\Controllers\TaskManageController::class)) {
            $tasks[] = ['id' => 't-assign', 'label' => 'نوشتن تسک', 'icon' => '✏️', 'href' => '/tasks/assign', 'path' => 'tasks/assign'];
            $tasks[] = ['id' => 't-team', 'label' => 'افراد و تذکر', 'icon' => '🚩', 'href' => '/tasks/team', 'path' => 'tasks/team*'];
            $tasks[] = ['id' => 't-obj', 'label' => 'اعتراض‌ها', 'icon' => '💬', 'href' => '/tasks/objections', 'path' => 'tasks/objections',
                'badge' => TaskAccess::unreplied($u)];
        }
        if (TaskAccess::canReport($u) && class_exists(\App\Http\Controllers\TaskReportController::class)) {
            $tasks[] = ['id' => 't-report', 'label' => 'گزارش تسک', 'icon' => '📈', 'href' => '/tasks/report', 'path' => 'tasks/report'];
        }
        return [['title' => 'مرخصی', 'items' => $leave], ['title' => 'تسک‌ها', 'items' => $tasks]];
    }

    public static function isActive(array $it): bool
    {
        return isset($it['path']) && request()->is($it['path']);
    }
}

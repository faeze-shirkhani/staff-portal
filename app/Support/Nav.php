<?php

namespace App\Support;

class Nav
{
    public static function icon(string $n): string
    {
        $p = [
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
            'check'    => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/>',
            'chart'    => '<path d="M5 20V10M12 20V4M19 20v-7"/>',
            'range'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 15h8"/>',
            'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17.5" cy="9" r="2.5"/><path d="M17 14.2c2.6.2 4.5 2.1 4.5 5"/>',
            'target'   => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>',
            'edit'     => '<path d="M4 20l1-4L16.5 4.5a2.1 2.1 0 013 3L8 19l-4 1z"/>',
            'flag'     => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
            'chat'     => '<path d="M4 5h16v11H9l-5 4V5z"/>',
            'trend'    => '<path d="M3 17l5-5 4 4 8-9"/><path d="M15 7h5v5"/>',
            'logout'   => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        ];

        return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($p[$n] ?? '') . '</svg>';
    }

    const ROLES = [
        'employee' => 'کارمند',
        'approver' => 'تأییدکننده',
        'boss' => 'مدیر'
    ];

    /** گروه‌های منوی کناری؛ آیتم‌های «#...» داخل صفحه‌ی مرخصی (جاوااسکریپت) باز می‌شوند و بقیه صفحه‌ی جدا هستند */
    public static function groups($u): array
    {
        $boss = $u->role === 'boss';
        $report = $boss || !empty($u->can_report);

        $leave = array_values(array_filter([
            !$boss ? [
                'id' => 'req',
                'label' => 'درخواست مرخصی',
                'icon' => '📅',
                'href' => '/#req'
            ] : null,

            in_array($u->role, ['approver', 'boss'], true) ? [
                'id' => 'queue',
                'label' => 'صف تأیید',
                'icon' => '✅',
                'href' => '/#queue'
            ] : null,

            $report ? [
                'id' => 'report',
                'label' => 'گزارش مانده مرخصی',
                'icon' => '📊',
                'href' => '/#report'
            ] : null,

            $report ? [
                'id' => 'range',
                'label' => 'گزارش بازه‌ای مرخصی',
                'icon' => '🗓',
                'href' => '/#range'
            ] : null,

            $boss ? [
                'id' => 'admin',
                'label' => 'مدیریت کاربران',
                'icon' => '👥',
                'href' => '/#admin'
            ] : null,
        ]));

        $tasks = [
            [
                'id' => 't-my',
                'label' => 'تسک‌های من',
                'icon' => '🎯',
                'href' => '/tasks',
                'path' => 'tasks'
            ]
        ];

        if (
            TaskAccess::canAssign($u) &&
            class_exists(\App\Http\Controllers\TaskManageController::class)
        ) {
            $tasks[] = [
                'id' => 't-assign',
                'label' => 'نوشتن تسک',
                'icon' => '✏️',
                'href' => '/tasks/assign',
                'path' => 'tasks/assign'
            ];

            $tasks[] = [
                'id' => 't-team',
                'label' => 'تذکر دادن',
                'icon' => '🚩',
                'href' => '/tasks/team',
                'path' => 'tasks/team*'
            ];

            $tasks[] = [
                'id' => 't-obj',
                'label' => 'اعتراض‌ها',
                'icon' => '💬',
                'href' => '/tasks/objections',
                'path' => 'tasks/objections',
                'badge' => TaskAccess::unreplied($u)
            ];
        }

        if (
            TaskAccess::canReport($u) &&
            class_exists(\App\Http\Controllers\TaskReportController::class)
        ) {
            $tasks[] = [
                'id' => 't-report',
                'label' => 'گزارش تسک',
                'icon' => '📈',
                'href' => '/tasks/report',
                'path' => 'tasks/report'
            ];
        }

        return [
            [
                'title' => 'مرخصی',
                'items' => $leave
            ],
            [
                'title' => 'تسک‌ها',
                'items' => $tasks
            ]
        ];
    }

    public static function isActive(array $it): bool
    {
        return isset($it['path']) && request()->is($it['path']);
    }
}
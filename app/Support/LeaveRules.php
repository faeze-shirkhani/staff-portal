<?php
namespace App\Support;

use Illuminate\Support\Facades\DB;

class LeaveRules
{
    const DAY = 480;     // 8 ساعت
    const MONTH = 1200;  // 2 روز + 4 ساعت
    const SECTIONS = ['sales' => 'فروش', 'site' => 'سایت', 'finance' => 'مالی و بازرگانی', 'warehouse' => 'انبار'];
    const ROLES = ['employee', 'approver', 'boss'];

    public static function today(): string { return now('Asia/Tehran')->toDateString(); }

    public static function norm(string $s): string // یکسان‌سازی نام و جست‌وجو
    {
        $s = str_replace(['ي', 'ك'], ['ی', 'ک'], $s);
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    public static function digits(string $s): string // اعداد فارسی/عربی به انگلیسی
    {
        return strtr($s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
    }

    public static function g2j(string $date): array // [سال، ماه، روز] شمسی
    {
        [$gy, $gm, $gd] = array_map('intval', explode('-', substr($date, 0, 10)));
        $gdm = [0,31,59,90,120,151,181,212,243,273,304,334];
        if ($gy > 1600) { $jy = 979; $gy -= 1600; } else { $jy = 0; $gy -= 621; }
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 365*$gy + intdiv($gy2+3,4) - intdiv($gy2+99,100) + intdiv($gy2+399,400) - 80 + $gd + $gdm[$gm-1];
        $jy += 33*intdiv($days,12053); $days %= 12053;
        $jy += 4*intdiv($days,1461);   $days %= 1461;
        if ($days > 365) { $jy += intdiv($days-1,365); $days = ($days-1) % 365; }
        $jm = $days < 186 ? 1 + intdiv($days,31) : 7 + intdiv($days-186,30);
        $jd = 1 + ($days < 186 ? $days % 31 : ($days-186) % 30);
        return [$jy, $jm, $jd];
    }

    public static function jm(string $date): int // شماره‌ی ماه شمسی از مبدأ
    {
        [$jy, $jm] = self::g2j($date);
        return $jy*12 + $jm;
    }

    public static function jdate(string $date): string
    {
        [$y, $m, $d] = self::g2j($date);
        return sprintf('%04d/%02d/%02d', $y, $m, $d);
    }

    // بخش‌هایی که تأییدکننده‌ی این بخش برایشان تأیید می‌کند (مالی و بازرگانی برای انبار هم تأیید می‌کند)
    public static function covers(?string $section): array
    {
        return $section === 'finance' ? ['finance', 'warehouse'] : ($section ? [$section] : []);
    }

    // بخش‌هایی که تأییدکننده‌ی آن‌ها برای کاربرِ این بخش تأیید می‌کند
    public static function approversFor(?string $section): array
    {
        return $section === 'warehouse' ? ['warehouse', 'finance'] : ($section ? [$section] : []);
    }

    public static function accrued($startDate, int $extra = 0): int
    {
        $start = $startDate ? (string)$startDate : self::today();
        $months = self::jm(self::today()) - self::jm($start) + 1;
        return max($months, 0) * self::MONTH + $extra;
    }

    // ثبت مرخصی فقط از امروز تا پایان ماه بعد، و بازه نباید از مرز ماه رد شود
    public static function allowedRange(string $a, string $b): bool
    {
        $today = self::today();
        if ($a < $today) return false;
        $cur = self::jm($today);
        $m = self::jm($a);
        return $m === self::jm($b) && ($m === $cur || $m === $cur + 1);
    }

    public static function balance($u): array
    {
        $accrued = self::accrued($u->start_date, (int)($u->extra_minutes ?? 0));
        $rows = DB::table('leaves')->where('user_id', $u->id)
            ->where('fin_status', '!=', 'rejected')->where('boss_status', '!=', 'rejected')
            ->get(['start_date', 'minutes']);
        $used = (int) $rows->sum('minutes');

        // مصرف/رزرو همین ماه شمسی و مانده‌ی همین ماه (سهم ماه منهای مصرف ماه، حداقل صفر)
        $cur = self::jm(self::today());
        $usedMonth = (int) $rows->filter(fn ($r) => self::jm((string)$r->start_date) === $cur)->sum('minutes');

        return ['accrued' => $accrued, 'used' => $used, 'balance' => $accrued - $used,
            'month' => self::g2j(self::today())[1],
            'month_used' => $usedMonth,
            'month_balance' => max(self::MONTH - $usedMonth, 0)];
    }

    public static function record($u): array
    {
        return ['id' => $u->id, 'fullname' => $u->name, 'role' => $u->role, 'section' => $u->section,
            'can_report' => (bool)$u->can_report || $u->role === 'boss'];
    }
}
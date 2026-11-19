<?php
namespace App\Support;

use Carbon\Carbon;

class Fa
{
    const MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    const DAYS = [0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه'];

    public static function num($s): string
    {
        return strtr((string)$s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
    }

    public static function date(string $ymd): string // ۱۴ مهر ۱۴۰۵
    {
        [$y, $m, $d] = LeaveRules::g2j($ymd);
        return self::num($d) . ' ' . self::MONTHS[$m - 1] . ' ' . self::num($y);
    }

    public static function day(string $ymd): string // نام روز هفته
    {
        return self::DAYS[Carbon::parse($ymd)->dayOfWeek];
    }

    public static function dt($ts): string // تاریخ و ساعت به وقت تهران
    {
        if (!$ts) return '';
        $c = Carbon::parse($ts, config('app.timezone'))->setTimezone('Asia/Tehran');
        return self::date($c->toDateString()) . ' ساعت ' . self::num($c->format('H:i'));
    }

    /** تاریخ شمسی به میلادی (Y-m-d) */
    public static function j2g(int $jy, int $jm, int $jd): string
    {
        $d = Carbon::create($jy + 621, 3, 21, 12, 0, 0, 'UTC')->addDays((int)round(($jm - 1) * 30.4 + $jd - 1));
        $t = $jy * 400 + $jm * 32 + $jd;
        for ($i = 0; $i < 80; $i++) {
            [$y, $m, $dd] = LeaveRules::g2j($d->toDateString());
            $k = $y * 400 + $m * 32 + $dd;
            if ($k === $t) return $d->toDateString();
            $d->addDays($k < $t ? 1 : -1);
        }
        return $d->toDateString();
    }

    public static function monthDays(int $jy, int $jm): int
    {
        if ($jm <= 6) return 31;
        if ($jm <= 11) return 30;
        return LeaveRules::g2j(self::j2g($jy, 12, 30))[1] === 12 ? 30 : 29;
    }

    /** [اولین روز، آخرین روز] ماه شمسی به میلادی */
    public static function monthRange(int $jy, int $jm): array
    {
        return [self::j2g($jy, $jm, 1), self::j2g($jy, $jm, self::monthDays($jy, $jm))];
    }
}

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
}

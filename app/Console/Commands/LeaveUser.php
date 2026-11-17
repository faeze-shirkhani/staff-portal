<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LeaveUser extends Command
{
    // فقط برای ساخت اولین «مدیر». بقیه ثبت‌نام می‌کنند و مدیر از صفحه‌ی «مدیریت کاربران» نقششان را تعیین می‌کند.
    protected $signature = 'leave:user {name} {password} {role : employee|approver|boss} {section=none : sales|site|finance|none}';
    protected $description = 'ساخت یا به‌روزرسانی کاربر با نام کامل';

    public function handle(): int
    {
        $name = trim(preg_replace('/\s+/u', ' ', $this->argument('name')));
        $role = $this->argument('role'); $sec = $this->argument('section');
        if (substr_count($name, ' ') < 1) { $this->error('نام باید شامل نام و نام خانوادگی باشد، مثل "Hadi Hanabestani"'); return 1; }
        if (!in_array($role, ['employee', 'approver', 'boss'], true)) { $this->error('نقش نامعتبر'); return 1; }
        if (!in_array($sec, ['sales', 'site', 'finance', 'none'], true)) { $this->error('بخش نامعتبر'); return 1; }
        if (strlen($this->argument('password')) < 4) { $this->error('رمز حداقل ۴ کاراکتر'); return 1; }
        $data = ['password' => Hash::make($this->argument('password')), 'role' => $role,
            'section' => $sec === 'none' ? null : $sec, 'updated_at' => now()];
        $u = DB::table('users')->where('name', $name)->first();
        if ($u) DB::table('users')->where('id', $u->id)->update($data);
        else DB::table('users')->insert($data + ['name' => $name, 'email' => Str::uuid() . '@leave.local',
            'start_date' => now('Asia/Tehran')->toDateString(), 'created_at' => now()]);
        $this->info('انجام شد.');
        return 0;
    }
}

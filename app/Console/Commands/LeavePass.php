<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LeavePass extends Command
{
    protected $signature = 'leave:pass {name} {password}';
    protected $description = 'تغییر رمز یک کاربر (فقط رمز؛ بقیه‌ی مشخصات دست‌نخورده)';

    public function handle(): int
    {
        $name = trim(preg_replace('/\s+/u', ' ', $this->argument('name')));
        $n = DB::table('users')->where('name', $name)->update(['password' => Hash::make($this->argument('password')), 'updated_at' => now()]);
        $n ? $this->info('رمز عوض شد.') : $this->error('کاربری با این نام پیدا نشد.');
        return $n ? 0 : 1;
    }
}

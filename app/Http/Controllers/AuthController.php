<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function err(string $m, int $c = 400) { return response()->json(['message' => $m], $c); }
    private function reply($u) { return response()->json(['record' => R::record($u)]); }

    public function me() { return response()->json(['record' => Auth::check() ? R::record(Auth::user()) : null]); }

    public function login(Request $r)
    {
        $name = R::norm(R::norm((string)$r->input('first')) . ' ' . R::norm((string)$r->input('last')));
        if (!Auth::attempt(['name' => $name, 'password' => R::digits((string)$r->input('password'))])) {
            return $this->err('نام یا رمز عبور اشتباه است');
        }
        $r->session()->regenerate();
        return $this->reply(Auth::user());
    }

    public function register(Request $r)
    {
        $first = R::norm((string)$r->input('first')); $last = R::norm((string)$r->input('last'));
        $pw = R::digits((string)$r->input('password')); $section = (string)$r->input('section');
        if (mb_strlen($first) < 2 || mb_strlen($last) < 2 || mb_strlen($first) > 40 || mb_strlen($last) > 40) return $this->err('نام و نام خانوادگی را کامل بنویسید');
        if (!isset(R::SECTIONS[$section])) return $this->err('بخش را انتخاب کنید');
        if (mb_strlen($pw) < 4) return $this->err('رمز عبور حداقل ۴ کاراکتر باشد');
        $name = $first . ' ' . $last;
        if (DB::table('users')->where('name', $name)->exists()) return $this->err('این نام قبلاً ثبت شده است. اگر خودتان هستید وارد شوید.');
        $id = DB::table('users')->insertGetId([
            'name' => $name, 'email' => Str::uuid() . '@leave.local', 'password' => Hash::make($pw),
            'role' => 'employee', 'section' => $section, 'start_date' => R::today(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::loginUsingId($id);
        $r->session()->regenerate();
        return $this->reply(Auth::user());
    }

    public function logout(Request $r)
{
    Auth::logout();
    $r->session()->invalidate();
    $r->session()->regenerateToken();
    if ($r->expectsJson()) return response()->json(['ok' => 1]);
    return redirect('/');
}
}

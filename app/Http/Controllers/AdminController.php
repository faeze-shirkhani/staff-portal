<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    private function err(string $m, int $c = 400) { return response()->json(['message' => $m], $c); }
    private function notBoss() { return Auth::user()->role !== 'boss'; }

    public function users(Request $r)
    {
        if ($this->notBoss()) return $this->err('دسترسی ندارید', 403);
        $q = DB::table('users')->select('id', 'name', 'role', 'section', 'can_report', 'skip_first', 'extra_minutes')->orderBy('name');
        $name = R::norm((string)$r->query('q', ''));
        if ($name !== '') $q->where('name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $name) . '%');
        return response()->json(['items' => $q->limit(300)->get()->map(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'role' => $u->role, 'section' => $u->section,
            'can_report' => (bool)$u->can_report, 'skip_first' => (bool)$u->skip_first, 'extra_minutes' => (int)$u->extra_minutes])->values()]);
    }

    public function update($id, Request $r)
    {
        if ($this->notBoss()) return $this->err('دسترسی ندارید', 403);
        $u = DB::table('users')->where('id', $id)->first();
        if (!$u) return $this->err('کاربر پیدا نشد', 404);
        $set = [];
        if ($r->has('name')) {
            $n = R::norm((string)$r->input('name'));
            if (substr_count($n, ' ') < 1 || mb_strlen($n) > 80) return $this->err('نام باید شامل نام و نام خانوادگی باشد');
            if (DB::table('users')->where('name', $n)->where('id', '!=', $id)->exists()) return $this->err('این نام قبلاً برای کاربر دیگری ثبت شده');
            $set['name'] = $n;
        }
        $role = $r->has('role') ? (string)$r->input('role') : $u->role;
        if (!in_array($role, R::ROLES, true)) return $this->err('نقش نامعتبر');
        if ($u->id == Auth::id() && $role !== 'boss') return $this->err('نقش خودتان را نمی‌توانید عوض کنید');
        $section = $r->has('section') ? ((string)$r->input('section') ?: null) : $u->section;
        if ($section !== null && !isset(R::SECTIONS[$section])) return $this->err('بخش نامعتبر');
        if ($role === 'approver' && !$section) return $this->err('تأییدکننده باید بخش داشته باشد');
        $set['role'] = $role; $set['section'] = $section;
        if ($r->has('can_report')) $set['can_report'] = $r->boolean('can_report');
        if ($r->has('skip_first')) $set['skip_first'] = $r->boolean('skip_first');
        if ($r->has('extra_minutes')) {
            $e = (int)$r->input('extra_minutes');
            if (abs($e) > 480 * 3650) return $this->err('مقدار مانده‌ی اضافه نامعتبر است');
            $set['extra_minutes'] = $e;
        }
        $pw = R::digits((string)$r->input('password', ''));
        if ($pw !== '') {
            if (mb_strlen($pw) < 4) return $this->err('رمز حداقل ۴ کاراکتر باشد');
            $set['password'] = Hash::make($pw);
        }
        $set['updated_at'] = now();
        DB::table('users')->where('id', $id)->update($set);
        // اگر «نیاز به تأیید اولیه ندارد» روشن است، درخواست‌های منتظرِ مرحله‌ی اول همین کاربر هم مستقیم می‌روند برای مدیر
        if (!empty($set['skip_first'])) {
            DB::table('leaves')->where('user_id', $id)->where('fin_status', 'pending')->where('boss_status', 'pending')
                ->update(['fin_status' => 'approved', 'updated_at' => now()]);
        }
        return response()->json(['ok' => 1]);
    }

    public function delete($id)
    {
        if ($this->notBoss()) return $this->err('دسترسی ندارید', 403);
        if ($id == Auth::id()) return $this->err('خودتان را نمی‌توانید حذف کنید');
        DB::table('users')->where('id', $id)->delete();
        return response()->json(['ok' => 1]);
    }
}

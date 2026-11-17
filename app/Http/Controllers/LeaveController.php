<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    private function err(string $m, int $c = 400) { return response()->json(['message' => $m], $c); }

    private function item($x): array
    {
        return ['id' => (string)$x->id, 'kind' => $x->kind, 'start_date' => (string)$x->start_date, 'end_date' => (string)$x->end_date,
            'from_time' => $x->from_time ?: '', 'to_time' => $x->to_time ?: '', 'minutes' => (int)$x->minutes,
            'reason' => $x->reason, 'fin_status' => $x->fin_status, 'boss_status' => $x->boss_status,
            'created_at' => $x->created_at ? \Carbon\Carbon::parse($x->created_at, config('app.timezone'))->setTimezone('Asia/Tehran')->format('Y-m-d H:i') : '',
            'expand' => ['user' => ['fullname' => $x->fullname, 'section' => $x->sec]]];
    }

    private function validDate($s): bool
    {
        return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && checkdate((int)substr($s,5,2), (int)substr($s,8,2), (int)substr($s,0,4));
    }

    public function balance() { return response()->json(R::balance(Auth::user())); }

    public function list(Request $r)
    {
        $me = Auth::user(); $scope = $r->query('scope', 'mine');
        $q = DB::table('leaves as l')->join('users as u', 'u.id', '=', 'l.user_id')
            ->select('l.*', 'u.name as fullname', 'u.section as sec');
        if ($scope === 'mine') {
            $q->where('l.user_id', $me->id)->orderByDesc('l.id')->limit(100);
        } elseif ($me->role === 'approver' && $me->section && $scope === 'queue') {
            $q->where('l.fin_status', 'pending')->where('l.boss_status', 'pending')
              ->whereIn('u.section', R::covers($me->section))->where('l.user_id', '!=', $me->id)->orderBy('l.id')->limit(100);
        } elseif ($me->role === 'approver' && $scope === 'history') {
            $q->where('l.fin_by', $me->id)->orderByDesc('l.updated_at')->limit(30);
        } elseif ($me->role === 'boss' && $scope === 'queue') {
            // بعد از تأیید مرحله‌ی اول، یا وقتی بخش اصلاً تأییدکننده ندارد
            $q->where('l.boss_status', 'pending')->where(function ($w) {
                $w->where('l.fin_status', 'approved')->orWhere(function ($w2) {
                    $w2->where('l.fin_status', 'pending')->whereNotExists(function ($s) {
                        $s->selectRaw('1')->from('users as a')->where('a.role', 'approver')->where(function ($x) {
                            $x->whereColumn('a.section', 'u.section')->orWhere(function ($y) { $y->where('a.section', 'finance')->where('u.section', 'warehouse'); });
                        });
                    });
                });
            })->orderBy('l.id')->limit(100);
        } elseif ($me->role === 'boss' && $scope === 'history') {
            $q->where('l.boss_status', '!=', 'pending')->orderByDesc('l.updated_at')->limit(30);
        } else return $this->err('دسترسی ندارید', 403);
        return response()->json(['items' => $q->get()->map(fn ($x) => $this->item($x))->values()]);
    }

    public function create(Request $r)
    {
        $me = Auth::user();
        if (!in_array($me->role, ['employee', 'approver'], true)) return $this->err('مدیر نیازی به ثبت درخواست ندارد', 403);
        $kind = $r->input('kind'); $sd = substr((string)$r->input('start_date'), 0, 10);
        if (!in_array($kind, ['daily', 'hourly'], true) || !$this->validDate($sd)) return $this->err('اطلاعات نامعتبر است');
        $reason = mb_substr(trim((string)$r->input('reason', '')), 0, 500);
        $ft = $tt = null;
        if ($kind === 'daily') {
            $ed = substr((string)$r->input('end_date'), 0, 10);
            if (!$this->validDate($ed) || $ed < $sd) return $this->err('تاریخ پایان نامعتبر است');
            $n = (new \DateTime($sd))->diff(new \DateTime($ed))->days + 1;
            if ($n > 120) return $this->err('بازه‌ی انتخاب‌شده بیش از حد طولانی است');
            $mins = $n * R::DAY;
        } else {
            $ed = $sd; $ft = (string)$r->input('from_time'); $tt = (string)$r->input('to_time');
            if (!preg_match('/^\d{2}:\d{2}$/', $ft) || !preg_match('/^\d{2}:\d{2}$/', $tt)) return $this->err('ساعت نامعتبر است');
            $t = fn ($s) => intval(substr($s, 0, 2)) * 60 + intval(substr($s, 3, 2));
            $mins = $t($tt) - $t($ft);
            if ($mins <= 0 || $mins > R::DAY) return $this->err('ساعت نامعتبر است (حداکثر ۸ ساعت)');
        }
        // فقط از امروز تا پایان ماه بعد، و بازه نباید از مرز ماه رد شود
        if (!R::allowedRange($sd, $ed)) {
            return $this->err('فقط از امروز تا پایان ماه بعد می‌توانید مرخصی ثبت کنید و بازه نباید از مرز ماه رد شود');
        }
        $ok = DB::transaction(function () use ($me, $mins, $kind, $sd, $ed, $ft, $tt, $reason) {
            DB::table('users')->where('id', $me->id)->lockForUpdate()->first();
            $fresh = DB::table('users')->where('id', $me->id)->first();
            if ($mins > R::balance($fresh)['balance']) return false;
            DB::table('leaves')->insert(['user_id' => $me->id, 'kind' => $kind, 'start_date' => $sd, 'end_date' => $ed,
                'from_time' => $ft, 'to_time' => $tt, 'minutes' => $mins, 'reason' => $reason,
                // درخواست خودِ تأییدکننده مرحله‌ی اول ندارد و مستقیم می‌رود برای مدیر
                'fin_status' => ($me->role === 'approver' || !empty($fresh->skip_first)) ? 'approved' : 'pending',
                'created_at' => now(), 'updated_at' => now()]);
            return true;
        });
        return $ok ? response()->json(['ok' => 1]) : $this->err('مانده مرخصی کافی نیست');
    }

    public function remove(Request $r)
    {
        $me = Auth::user(); $id = (int)$r->input('id');
        $L = DB::table('leaves')->where('id', $id)->first();
        if (!$L) return $this->err('درخواست پیدا نشد', 404);
        if ($me->role === 'boss') { /* مدیر هر درخواستی را می‌تواند حذف کند */ }
        elseif ($L->user_id == $me->id) {
            if ($L->boss_status !== 'pending') return $this->err('بعد از تصمیم نهایی مدیر فقط مدیر می‌تواند حذف کند');
        } else return $this->err('دسترسی ندارید', 403);
        DB::table('leaves')->where('id', $id)->delete();
        return response()->json(['ok' => 1]);
    }

    public function decide(Request $r)
    {
        $me = Auth::user(); $id = (int)$r->input('id'); $act = $r->input('action');
        if (!in_array($act, ['approved', 'rejected'], true)) return $this->err('اطلاعات نامعتبر است');
        $L = DB::table('leaves as l')->join('users as u', 'u.id', '=', 'l.user_id')->where('l.id', $id)->select('l.*', 'u.section as sec')->first();
        if (!$L) return $this->err('درخواست پیدا نشد', 404);
        if ($me->role === 'approver') {
            if (!$me->section || !in_array($L->sec, R::covers($me->section), true) || $L->user_id == $me->id) return $this->err('این درخواست مربوط به بخش شما نیست', 403);
            $n = DB::table('leaves')->where('id', $id)->where('fin_status', 'pending')->where('boss_status', 'pending')
                ->update(['fin_status' => $act, 'fin_by' => $me->id, 'updated_at' => now()]);
        } elseif ($me->role === 'boss') {
            $noApprover = !DB::table('users')->where('role', 'approver')->whereIn('section', R::approversFor($L->sec))->exists();
            if (!($L->fin_status === 'approved' || ($L->fin_status === 'pending' && $noApprover))) return $this->err('این درخواست هنوز به مرحله‌ی مدیر نرسیده است');
            $upd = ['boss_status' => $act, 'boss_by' => $me->id, 'updated_at' => now()];
            if ($L->fin_status === 'pending') $upd['fin_status'] = 'approved';
            $n = DB::table('leaves')->where('id', $id)->where('boss_status', 'pending')->update($upd);
        } else return $this->err('دسترسی ندارید', 403);
        return $n === 1 ? response()->json(['ok' => 1]) : $this->err('این درخواست در این مرحله قابل تصمیم‌گیری نیست');
    }
}
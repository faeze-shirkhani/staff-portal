<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        $me = Auth::user();
        if ($me->role !== 'boss' && !$me->can_report) return response()->json(['message' => 'دسترسی ندارید'], 403);
        $sub = DB::table('leaves')->selectRaw("user_id,
            SUM(CASE WHEN fin_status='approved' AND boss_status='approved' THEN minutes ELSE 0 END) AS approved,
            SUM(CASE WHEN NOT (fin_status='approved' AND boss_status='approved') AND fin_status<>'rejected' AND boss_status<>'rejected' THEN minutes ELSE 0 END) AS pending")
            ->groupBy('user_id');
        $q = DB::table('users as u')->leftJoinSub($sub, 's', 's.user_id', '=', 'u.id')->where('u.role', '!=', 'boss')
            ->select('u.id', 'u.name', 'u.section', 'u.start_date', 'u.extra_minutes',
                DB::raw('COALESCE(s.approved,0) AS approved'), DB::raw('COALESCE(s.pending,0) AS pending'))->orderBy('u.name');
        $name = R::norm((string)$r->query('q', ''));
        if ($name !== '') $q->where('u.name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $name) . '%');
        if (isset(R::SECTIONS[(string)$r->query('section')])) $q->where('u.section', $r->query('section'));
        $items = $q->limit(500)->get()->map(function ($u) {
            $acc = R::accrued($u->start_date, (int)$u->extra_minutes);
            return ['name' => $u->name, 'section' => $u->section, 'accrued' => $acc, 'approved' => (int)$u->approved,
                'pending' => (int)$u->pending, 'balance' => $acc - (int)$u->approved - (int)$u->pending];
        })->values();
        return response()->json(['items' => $items]);
    }
}

<?php
namespace App\Http\Controllers;

use App\Support\Fa;
use App\Support\LeaveRules as R;
use App\Support\TaskAccess as A;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** گزارش ماهانه‌ی تذکرها: چه کسی چه تسکی را انجام نداده و تذکر گرفته */
class TaskReportController extends Controller
{
    private function build(Request $r): array
    {
        abort_unless(A::canReport(Auth::user()), 403);
        [$cy, $cm] = R::g2j(R::today());
        $jy = (int)$r->query('jy', $cy); $jm = (int)$r->query('jm', $cm);
        if ($jy < 1380 || $jy > 1500) $jy = $cy;
        if ($jm < 1 || $jm > 12) $jm = $cm;
        [$from, $to] = Fa::monthRange($jy, $jm);
        $sec = isset(R::SECTIONS[(string)$r->query('section')]) ? (string)$r->query('section') : '';
        $name = R::norm((string)$r->query('q', ''));

        // تذکرهایی که در این ماه فعال بوده‌اند: قبل از پایان ماه ثبت شده و تا اول ماه برداشته نشده‌اند
        $q = DB::table('task_flags as f')->join('tasks as t', 't.id', '=', 'f.task_id')->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('users as b', 'b.id', '=', 'f.flagged_by')
            ->where('f.created_at', '<=', $to . ' 23:59:59')
            ->where(fn ($w) => $w->whereNull('f.resolved_at')->orWhere('f.resolved_at', '>=', $from . ' 00:00:00'))
            ->orderBy('u.name')->orderBy('f.created_at')
            ->select('f.*', 't.title', 'u.id as uid', 'u.name as uname', 'u.section', 'b.name as bname');
        if ($sec !== '') $q->where('u.section', $sec);
        if ($name !== '') $q->where('u.name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $name) . '%');

        $people = []; $flags = 0; $open = 0;
        foreach ($q->limit(3000)->get() as $f) {
            $stillOpen = !$f->resolved_at || $f->resolved_at > $to . ' 23:59:59';
            $people[$f->uid] ??= ['name' => $f->uname, 'section' => $f->section, 'open' => 0, 'flags' => []];
            $people[$f->uid]['flags'][] = ['title' => $f->title, 'note' => $f->note, 'by' => $f->bname, 'at' => $f->created_at,
                'resolved_at' => $f->resolved_at, 'open' => $stillOpen];
            if ($stillOpen) { $people[$f->uid]['open']++; $open++; }
            $flags++;
        }
        return ['jy' => $jy, 'jm' => $jm, 'from' => $from, 'to' => $to, 'section' => $sec, 'q' => $name,
            'people' => array_values($people), 'flags' => $flags, 'open' => $open];
    }

    public function index(Request $r)
    {
        return view('tasks.report', $this->build($r) + ['sections' => R::SECTIONS]);
    }

    public function xlsx(Request $r)
    {
        $d = $this->build($r);
        $rows = [['نام', 'بخش', 'تسک', 'توضیح تذکر', 'تذکر دهنده', 'تاریخ تذکر', 'وضعیت در پایان ماه', 'تاریخ رفع']];
        foreach ($d['people'] as $p) foreach ($p['flags'] as $f) {
            $rows[] = [$p['name'], R::SECTIONS[$p['section']] ?? '', $f['title'], $f['note'], $f['by'] ?? '',
                Fa::date(substr($f['at'], 0, 10)), $f['open'] ? 'هنوز باز' : 'رفع شده',
                $f['resolved_at'] ? Fa::date(substr($f['resolved_at'], 0, 10)) : ''];
        }
        return Xlsx::response($rows, 'task-report-' . $d['jy'] . '-' . sprintf('%02d', $d['jm']), 'گزارش تسک');
    }
}

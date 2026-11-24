<?php
namespace App\Http\Controllers;

use App\Support\Fa;
use App\Support\LeaveRules as R;
use App\Support\TaskAccess as A;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** گزارش تسک‌ها و تذکرها: فقط تذکردارها یا همه‌ی تسک‌های هر نفر؛ برای یک ماه یا کل سال */
class TaskReportController extends Controller
{
    private function build(Request $r): array
    {
        abort_unless(A::canReport(Auth::user()), 403);
        [$cy, $cm] = R::g2j(R::today());
        $jy = (int)$r->query('jy', $cy); $jm = (int)$r->query('jm', $cm);
        if ($jy < 1380 || $jy > 1500) $jy = $cy;
        if ($jm < 0 || $jm > 12) $jm = $cm;                      // ۰ = همه‌ی ماه‌های آن سال
        $mode = $r->query('mode') === 'all' ? 'all' : 'flags';    // all = همه‌ی تسک‌ها
        $from = Fa::monthRange($jy, $jm ?: 1)[0];
        $to = Fa::monthRange($jy, $jm ?: 12)[1];
        $sec = isset(R::SECTIONS[(string)$r->query('section')]) ? (string)$r->query('section') : '';
        $name = R::norm((string)$r->query('q', ''));
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $name) . '%';

        // تذکرهایی که در این بازه فعال بوده‌اند: قبل از پایان بازه ثبت شده و تا اول بازه برداشته نشده‌اند
        $q = DB::table('task_flags as f')->join('tasks as t', 't.id', '=', 'f.task_id')->join('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('users as b', 'b.id', '=', 'f.flagged_by')
            ->where('f.created_at', '<=', $to . ' 23:59:59')
            ->where(fn ($w) => $w->whereNull('f.resolved_at')->orWhere('f.resolved_at', '>=', $from . ' 00:00:00'))
            ->orderBy('u.name')->orderBy('f.created_at')
            ->select('f.*', 't.title', 'u.id as uid', 'u.name as uname', 'u.section', 'b.name as bname');
        if ($sec !== '') $q->where('u.section', $sec);
        if ($name !== '') $q->where('u.name', 'like', $like);

        $people = []; $byTask = []; $flags = 0; $open = 0;
        foreach ($q->limit(3000)->get() as $f) {
            $stillOpen = !$f->resolved_at || $f->resolved_at > $to . ' 23:59:59';
            $row = ['title' => $f->title, 'note' => $f->note, 'by' => $f->bname, 'at' => $f->created_at,
                'resolved_at' => $f->resolved_at, 'open' => $stillOpen];
            if ($mode === 'flags') {
    if (!$stillOpen) continue;   // تذکرهای رفع‌شده را نیاور
    $people[$f->uid] ??= ['name' => $f->uname, 'section' => $f->section, 'open' => 0, 'flags' => []];
                $people[$f->uid] ??= ['name' => $f->uname, 'section' => $f->section, 'open' => 0, 'flags' => []];
                $people[$f->uid]['flags'][] = $row;
                if ($stillOpen) { $people[$f->uid]['open']++; $open++; }
                $flags++;
            } else {
                $byTask[$f->task_id][] = $row;
            }
        }

        // حالت «همه‌ی تسک‌ها»: تسک‌هایی که در این بازه فعال بوده‌اند، هر کدام با تذکرهایش
        $tasks = 0; $flagged = 0;
        if ($mode === 'all') {
            $tq = DB::table('tasks as t')->join('users as u', 'u.id', '=', 't.user_id')->leftJoin('users as a', 'a.id', '=', 't.created_by')
                ->where('t.created_at', '<=', $to . ' 23:59:59')
                ->where(fn ($w) => $w->whereNull('t.deleted_at')->orWhere('t.deleted_at', '>=', $from . ' 00:00:00'))
                ->orderBy('u.name')->orderBy('t.id')
                ->select('t.id', 't.title', 't.created_at', 't.created_by', 't.user_id', 'u.id as uid', 'u.name as uname', 'u.section', 'a.name as author');
            if ($sec !== '') $tq->where('u.section', $sec);
            if ($name !== '') $tq->where('u.name', 'like', $like);
            foreach ($tq->limit(5000)->get() as $t) {
                $fl = $byTask[$t->id] ?? [];
                $o = count(array_filter($fl, fn ($x) => $x['open']));
                $people[$t->uid] ??= ['name' => $t->uname, 'section' => $t->section, 'open' => 0, 'flagged' => 0, 'tasks' => []];
                $people[$t->uid]['tasks'][] = ['title' => $t->title, 'author' => $t->created_by == $t->user_id ? null : $t->author,
                    'at' => $t->created_at, 'flags' => $fl, 'open' => $o];
                $people[$t->uid]['open'] += $o;
                if ($fl) { $people[$t->uid]['flagged']++; $flagged++; }
                $flags += count($fl); $open += $o; $tasks++;
            }
        }
        return ['jy' => $jy, 'jm' => $jm, 'mode' => $mode, 'from' => $from, 'to' => $to, 'section' => $sec, 'q' => $name,
            'people' => array_values($people), 'flags' => $flags, 'open' => $open, 'tasks' => $tasks, 'flagged' => $flagged];
    }

    public function index(Request $r)
    {
        return view('tasks.report', $this->build($r) + ['sections' => R::SECTIONS]);
    }

    public function xlsx(Request $r)
    {
        $d = $this->build($r);
        if ($d['mode'] === 'all') {
            $rows = [['نام', 'بخش', 'تسک', 'نویسنده‌ی تسک', 'تاریخ ثبت تسک', 'تعداد تذکر', 'تذکر باز در پایان بازه', 'توضیح تذکرها']];
            foreach ($d['people'] as $p) foreach ($p['tasks'] as $t) {
                $rows[] = [$p['name'], R::SECTIONS[$p['section']] ?? '', $t['title'], $t['author'] ?? 'خودش',
                    Fa::date(substr($t['at'], 0, 10)), count($t['flags']), $t['open'],
                    implode(' | ', array_map(fn ($f) => $f['note'], $t['flags']))];
            }
        } else {
            $rows = [['نام', 'بخش', 'تسک', 'توضیح تذکر', 'تذکر دهنده', 'تاریخ تذکر', 'وضعیت در پایان بازه', 'تاریخ رفع']];
            foreach ($d['people'] as $p) foreach ($p['flags'] as $f) {
                $rows[] = [$p['name'], R::SECTIONS[$p['section']] ?? '', $f['title'], $f['note'], $f['by'] ?? '',
                    Fa::date(substr($f['at'], 0, 10)), $f['open'] ? 'هنوز باز' : 'رفع شده',
                    $f['resolved_at'] ? Fa::date(substr($f['resolved_at'], 0, 10)) : ''];
            }
        }
        $suffix = ($d['jm'] ? sprintf('%02d', $d['jm']) : 'all') . ($d['mode'] === 'all' ? '-all-tasks' : '');
        return Xlsx::response($rows, 'task-report-' . $d['jy'] . '-' . $suffix, 'گزارش تسک');
    }
}
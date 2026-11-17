<?php
namespace App\Http\Controllers;

use App\Support\LeaveRules as R;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveReportController extends Controller
{
    private function err(string $m, int $c = 400) { return response()->json(['message' => $m], $c); }
    private function ok($s): bool { return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && checkdate((int)substr($s,5,2), (int)substr($s,8,2), (int)substr($s,0,4)); }

    /** @return array [خطا|null، داده|null] */
    private function build(Request $r): array
    {
        $me = Auth::user();
        if ($me->role !== 'boss' && !$me->can_report) return [$this->err('دسترسی ندارید', 403), null];
        $from = (string)$r->query('from'); $to = (string)$r->query('to');
        if (!$this->ok($from) || !$this->ok($to) || $to < $from) return [$this->err('بازه‌ی تاریخ نامعتبر است'), null];
        if ((new \DateTime($from))->diff(new \DateTime($to))->days > 400) return [$this->err('بازه‌ی گزارش حداکثر ۴۰۰ روز است'), null];

        $q = DB::table('leaves as l')->join('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.start_date', '<=', $to)->where('l.end_date', '>=', $from)
            ->select('l.*', 'u.name', 'u.section')->orderBy('u.name')->orderBy('l.start_date');
        if ($r->query('status') === 'all') $q->where('l.fin_status', '!=', 'rejected')->where('l.boss_status', '!=', 'rejected');
        else $q->where('l.fin_status', 'approved')->where('l.boss_status', 'approved');
        if (isset(R::SECTIONS[(string)$r->query('section')])) $q->where('u.section', $r->query('section'));
        $name = R::norm((string)$r->query('q', ''));
        if ($name !== '') $q->where('u.name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $name) . '%');

        $people = []; $total = 0; $count = 0;
        foreach ($q->limit(3000)->get() as $l) {
            if ($l->kind === 'daily') {
                $cs = max($l->start_date, $from); $ce = min($l->end_date, $to);
                $mins = ((new \DateTime($cs))->diff(new \DateTime($ce))->days + 1) * R::DAY;
            } else { $cs = $ce = $l->start_date; $mins = (int)$l->minutes; }
            $people[$l->user_id] ??= ['name' => $l->name, 'section' => $l->section, 'total' => 0, 'leaves' => []];
            $people[$l->user_id]['total'] += $mins;
            $people[$l->user_id]['leaves'][] = ['kind' => $l->kind, 'start_date' => $cs, 'end_date' => $ce,
                'from_time' => $l->from_time ?: '', 'to_time' => $l->to_time ?: '', 'minutes' => $mins,
                'status' => ($l->fin_status === 'approved' && $l->boss_status === 'approved') ? 'approved' : 'pending'];
            $total += $mins; $count++;
        }
        return [null, ['from' => $from, 'to' => $to, 'total' => $total, 'count' => $count, 'items' => array_values($people)]];
    }

    public function index(Request $r)
    {
        [$e, $d] = $this->build($r);
        return $e ?: response()->json($d);
    }

    public function xlsx(Request $r)
    {
        [$e, $d] = $this->build($r);
        if ($e) return $e;
        $rows = [['نام', 'بخش', 'نوع', 'از تاریخ', 'تا تاریخ', 'ساعت', 'مدت (ساعت)', 'وضعیت']];
        foreach ($d['items'] as $p) foreach ($p['leaves'] as $l) {
            $rows[] = [$p['name'], R::SECTIONS[$p['section']] ?? '', $l['kind'] === 'daily' ? 'روزانه' : 'ساعتی',
                R::jdate($l['start_date']), R::jdate($l['end_date']),
                $l['kind'] === 'hourly' ? $l['from_time'] . ' تا ' . $l['to_time'] : '',
                round($l['minutes'] / 60, 2), $l['status'] === 'approved' ? 'تأیید شد' : 'در انتظار'];
        }
        $rows[] = ['مجموع', '', '', '', '', '', round($d['total'] / 60, 2), ''];
        $fname = 'leave-report-' . $d['from'] . '_' . $d['to'];

        if (!class_exists(\ZipArchive::class)) { // اگر افزونه‌ی zip نبود، CSV (قابل‌باز در اکسل)
            $out = "\xEF\xBB\xBF";
            foreach ($rows as $row) $out .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string)$v) . '"', $row)) . "\r\n";
            return response($out, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"$fname.csv\""]);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'lv');
        $z = new \ZipArchive();
        $z->open($tmp, \ZipArchive::OVERWRITE);
        $h = 'http://schemas.openxmlformats.org';
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . $h . '/package/2006/relationships"><Relationship Id="rId1" Type="' . $h . '/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="' . $h . '/spreadsheetml/2006/main" xmlns:r="' . $h . '/officeDocument/2006/relationships"><sheets><sheet name="گزارش مرخصی" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . $h . '/package/2006/relationships"><Relationship Id="rId1" Type="' . $h . '/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="' . $h . '/spreadsheetml/2006/main"><sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews><cols><col min="1" max="8" width="18" customWidth="1"/></cols><sheetData>';
        foreach ($rows as $ri => $row) {
            $sheet .= '<row r="' . ($ri + 1) . '">';
            foreach ($row as $ci => $v) {
                $ref = chr(65 + $ci) . ($ri + 1);
                $sheet .= is_int($v) || is_float($v) ? "<c r=\"$ref\"><v>$v</v></c>"
                    : "<c r=\"$ref\" t=\"inlineStr\"><is><t>" . htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $z->addFromString('xl/worksheets/sheet1.xml', $sheet . '</sheetData></worksheet>');
        $z->close();
        return response()->download($tmp, "$fname.xlsx")->deleteFileAfterSend(true);
    }
}

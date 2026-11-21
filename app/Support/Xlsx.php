<?php
namespace App\Support;

class Xlsx
{
    /** فایل اکسل (xlsx) از آرایه‌ی ردیف‌ها؛ اگر افزونه‌ی zip نبود CSV برمی‌گرداند */
    public static function response(array $rows, string $fname, string $sheetName = 'گزارش')
    {
        if (!class_exists(\ZipArchive::class)) {
            $out = "\xEF\xBB\xBF";
            foreach ($rows as $row) $out .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string)$v) . '"', $row)) . "\r\n";
            return response($out, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"$fname.csv\""]);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        $z = new \ZipArchive();
        $z->open($tmp, \ZipArchive::OVERWRITE);
        $h = 'http://schemas.openxmlformats.org';
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . $h . '/package/2006/relationships"><Relationship Id="rId1" Type="' . $h . '/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="' . $h . '/spreadsheetml/2006/main" xmlns:r="' . $h . '/officeDocument/2006/relationships"><sheets><sheet name="' . htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . $h . '/package/2006/relationships"><Relationship Id="rId1" Type="' . $h . '/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $cols = max(1, count($rows[0] ?? [1]));
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="' . $h . '/spreadsheetml/2006/main"><sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews><cols><col min="1" max="' . $cols . '" width="22" customWidth="1"/></cols><sheetData>';
        foreach ($rows as $ri => $row) {
            $sheet .= '<row r="' . ($ri + 1) . '">';
            foreach (array_values($row) as $ci => $v) {
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

<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * سازنده سبک فایل Excel (xlsx) بدون کتابخانه خارجی - با پشتیبانی راست‌به‌چپ
 * اگر افزونه zip در PHP فعال نباشد، خروجی CSV (سازگار با Excel) داده می‌شود.
 */
class Xlsx
{
    /** @param array<string, array{0:array,1:array}> $sheets  نام شیت => [سرستون‌ها, ردیف‌ها] */
    public static function download(string $filename, array $sheets): never
    {
        if (!class_exists('ZipArchive')) {
            self::csv($filename, reset($sheets));
        }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);

        $n = count($sheets);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $zip->addFromString('[Content_Types].xml', $ct . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $i = 0;
        foreach ($sheets as $name => [$headers, $rows]) {
            $i++;
            $wb .= '<sheet name="' . self::x(mb_substr($name, 0, 31)) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', self::sheet($headers, $rows));
        }
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('xl/workbook.xml', $wb . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Tahoma"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Tahoma"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="3"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/><xf numFmtId="164" applyNumberFormat="1"/></cellXfs></styleSheet>');
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"; filename*=UTF-8''" . rawurlencode($filename) . '.xlsx');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    private static function sheet(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews><cols>';
        foreach ($headers as $i => $h) {
            $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="22" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';
        $all = array_merge([$headers], $rows);
        foreach ($all as $r => $row) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($row) as $c => $val) {
                $ref = self::col($c) . ($r + 1);
                if ($r === 0) {
                    $xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . self::x($val) . '</t></is></c>';
                } elseif (is_int($val) || is_float($val)) {
                    $xml .= '<c r="' . $ref . '" s="2"><v>' . $val . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . self::x($val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        return $xml . '</sheetData></worksheet>';
    }

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private static function x($v): string
    {
        return htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function csv(string $filename, array $sheet): never
    {
        [$headers, $rows] = $sheet;
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename) . '.csv');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $r) {
            fputcsv($out, array_values($r));
        }
        fclose($out);
        exit;
    }
}

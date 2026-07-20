<?php
/**
 * Đọc file bảng tính thành mảng dòng (mỗi dòng = mảng ô, index 0..n).
 *
 * Hỗ trợ .xlsx (không cần thư viện: .xlsx là zip chứa XML — dùng ZipArchive +
 * SimpleXML) và .csv (fgetcsv). Đủ cho import phụ tùng theo TASK_78.
 *
 * Hạn chế đã biết: chỉ đọc SHEET ĐẦU TIÊN, lấy giá trị dạng text/số
 * (không xử lý công thức, ngày tháng định dạng đặc biệt). Với file mẫu
 * do hệ thống cấp thì đủ dùng.
 */

namespace App\core;

class SpreadsheetReader {

    public static function read($path, $ext){
        $ext = strtolower($ext);
        if ($ext === 'csv')  return self::readCsv($path);
        if ($ext === 'xlsx') return self::readXlsx($path);
        return [];
    }

    private static function readCsv($path){
        $rows = [];
        if (($h = fopen($path, 'r')) === false) return [];

        $first = true;
        while (($data = fgetcsv($h, 0, ',')) !== false){
            if ($first && isset($data[0])){
                // Bỏ BOM UTF-8 đầu file (Excel "CSV UTF-8" hay thêm)
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                $first = false;
            }
            $rows[] = $data;
        }
        fclose($h);
        return $rows;
    }

    private static function readXlsx($path){
        if (!class_exists('\ZipArchive')) return [];

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return [];

        // 1) Bảng chuỗi dùng chung
        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss !== false){
            $xml = @simplexml_load_string($ss);
            if ($xml){
                foreach ($xml->si as $si){
                    if (isset($si->t) && count($si->children()) === 1){
                        $shared[] = (string) $si->t;
                    } else {
                        // rich text: ghép các đoạn <r><t>
                        $text = '';
                        foreach ($si->r as $r){ $text .= (string) $r->t; }
                        $shared[] = $text;
                    }
                }
            }
        }

        // 2) Sheet đầu tiên
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) return [];

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData)) return [];

        $rows = [];
        foreach ($xml->sheetData->row as $row){
            $cells  = [];
            $maxCol = -1;

            foreach ($row->c as $c){
                $col  = self::colIndex((string) $c['r']);
                $type = (string) $c['t'];

                if ($type === 's'){
                    $idx = (int) $c->v;
                    $val = isset($shared[$idx]) ? $shared[$idx] : '';
                } elseif ($type === 'inlineStr'){
                    $val = isset($c->is->t) ? (string) $c->is->t : '';
                } else {
                    $val = (string) $c->v;
                }

                $cells[$col] = $val;
                if ($col > $maxCol) $maxCol = $col;
            }

            $rowArr = [];
            for ($i = 0; $i <= $maxCol; $i++){
                $rowArr[$i] = isset($cells[$i]) ? $cells[$i] : '';
            }
            $rows[] = $rowArr;
        }

        return $rows;
    }

    /** "B3" -> 1 (index cột 0-based) */
    private static function colIndex($ref){
        if (!preg_match('/^([A-Z]+)/', $ref, $m)) return 0;
        $letters = $m[1];
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++){
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }
}

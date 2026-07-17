<?php
/**
 * Helper dùng chung cho các file test.
 *
 * require_once __DIR__ . '/_helpers.php';
 */

if (!isset($GLOBALS['pass'])) $GLOBALS['pass'] = 0;
if (!isset($GLOBALS['fail'])) $GLOBALS['fail'] = 0;

if (!function_exists('ok')){
    function ok($condition, $name, $detail = ''){
        if ($condition){
            $GLOBALS['pass']++;
            echo "  [PASS] $name\n";
        } else {
            $GLOBALS['fail']++;
            echo "  [FAIL] $name\n";
            if ($detail !== '') echo "         $detail\n";
        }
    }
}

if (!function_exists('section')){
    function section($t){ echo "\n=== $t ===\n"; }
}

if (!function_exists('codeOnly')){
    /**
     * Trả về mã PHP của file sau khi BỎ HẾT comment.
     *
     * Cần thiết vì nhiều file có comment trích lại code cũ để giải thích
     * (vd: "bản cũ trỏ vào ./public/logs/session"). Grep thẳng vào text nguồn
     * sẽ trúng comment và báo fail oan — đã xảy ra 3 lần khi viết bộ test này.
     *
     * Dùng codeOnly() khi muốn khẳng định điều gì đó về CODE THẬT.
     * Dùng file_get_contents() khi chỉ cần tìm một chuỗi bất kỳ trong file.
     */
    function codeOnly($path){
        $tokens = token_get_all(file_get_contents($path));
        $out = '';
        foreach ($tokens as $t){
            if (is_array($t)){
                if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) continue;
                $out .= $t[1];
            } else {
                $out .= $t;
            }
        }
        return $out;
    }
}

if (!function_exists('summary')){
    /** In tổng kết và trả về exit code */
    function summary(){
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "PASS: {$GLOBALS['pass']}   FAIL: {$GLOBALS['fail']}\n";
        echo str_repeat('-', 50) . "\n";
        return $GLOBALS['fail'] > 0 ? 1 : 0;
    }
}

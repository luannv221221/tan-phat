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

if (!function_exists('web_goc')){
    /**
     * Địa chỉ HTTP của CHÍNH BẢN MÃ đang chạy test.
     *
     * VÌ SAO KHÔNG GÕ CỨNG ĐỊA CHỈ (11/11/2026):
     *
     * Bộ test này còn được chạy trong git worktree (.claude/worktrees/<tên>/),
     * mà worktree có bản mã KHÁC bản ở thư mục chính. Gõ cứng địa chỉ thì phần
     * gọi HTTP đo bản ở THƯ MỤC CHÍNH rồi báo xanh — trong khi thứ vừa sửa nằm
     * ở worktree. Đã xảy ra thật: cả 17 file test có gọi HTTP đều báo PASS cho
     * một bản vá mà chúng chưa từng chạm tới.
     *
     * Thư mục dự án nằm trong htdocs nên phần đuôi sau 'htdocs/' chính là đường
     * dẫn URL. Lưu ý: .env của worktree phải có APP_BASE_PATH trỏ đúng vào
     * worktree đó, nếu không mọi chuyển hướng sẽ bay sang thư mục chính.
     */
    function web_goc(){
        $thuMuc = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));

        if (preg_match('~/htdocs/(.+)$~i', $thuMuc, $m)){
            return 'http://localhost:88/' . rtrim($m[1], '/');
        }

        return 'http://localhost:88/tan-phat';
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

<?php
/**
 * Gỡ các lớp mã hoá HTML bị chồng lên dữ liệu.
 *
 * NGUYÊN NHÂN
 * core/Request.php trước đây chạy FILTER_SANITIZE_SPECIAL_CHARS lên MỌI ô của
 * mọi biểu mẫu admin, tức là mã hoá HTML ngay lúc LƯU. Nhưng lúc in ra, view
 * còn escape thêm lần nữa, nên `&` hiện lên trang thành `&#38;`. Và vì mỗi lần
 * bấm Lưu lại mã hoá tiếp, số lớp cứ chồng lên:
 *
 *     Phụ tùng & thiết bị          (người dùng gõ)
 *     Phụ tùng &#38; thiết bị       (lưu lần 1)
 *     Phụ tùng &#38;#38; thiết bị   (lưu lần 2)  <- trạng thái đang gặp
 *
 * Nặng nhất là ô nội dung có định dạng: thẻ `<p>` của một bài tin đã thành
 * `&#38;#60;p&#38;#62;`, bài viết hiện ra nguyên đống thẻ dạng chữ.
 *
 * Gốc rễ đã sửa trong core/Request.php (lưu nguyên văn, escape lúc in).
 * Migration này dọn phần dữ liệu đã lỡ hỏng.
 *
 * CÁCH GỠ
 * Giải mã lặp cho tới khi chuỗi không đổi nữa — vì không biết trước bị chồng
 * mấy lớp. Chặn ở 10 vòng phòng dữ liệu lạ làm lặp vô tận.
 *
 * CHỈ ĐỘNG VÀO DÒNG THỰC SỰ HỎNG: phải khớp `&#<số>;`. Chuỗi bình thường có
 * dấu & (vd "R&D") không khớp nên không bị đụng tới.
 */

use App\core\Migration;

return new class extends Migration {

    /** Các cột chữ có thể chứa nội dung người dùng nhập */
    private $cot = [
        'site_settings'    => ['svalue'],
        'news'             => ['title', 'description', 'content'],
        'galleries'        => ['name', 'description'],
        'projects'         => ['name', 'description', 'content'],
        'parts'            => ['name', 'description'],
        'part_categories'  => ['name', 'description'],
        'partners'         => ['name', 'address', 'note'],
        'menus'            => ['name'],
    ];

    /** Giải mã cho tới khi không đổi nữa */
    private function goHet($s){
        $vong = 0;
        while ($vong++ < 10){
            $moi = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($moi === $s) break;
            $s = $moi;
        }
        return $s;
    }

    /**
     * Danh sách cột của một bảng — bản cài cũ có thể thiếu cột.
     *
     * KHÔNG viết `SHOW COLUMNS FROM x LIKE ?`: MySQL không nhận tham số ở chỗ
     * đó, nó ném luôn lỗi cú pháp 1064. Bản đầu của migration này viết như vậy
     * rồi bọc try/catch, thành ra NUỐT lỗi và bỏ qua sạch mọi cột — chạy xong
     * báo "OK" mà sửa được 0 ô.
     */
    private function cotCua($bang){
        try {
            $rows = $this->db->getRaw("SHOW COLUMNS FROM `$bang`");
            $ten  = [];
            foreach ($rows ?: [] as $r){ $ten[] = $r['Field']; }
            return $ten;
        } catch (\Throwable $e){
            return [];
        }
    }

    public function up(){
        $sua = 0;

        foreach ($this->cot as $bang => $cots){
            if (!$this->hasTable($bang)) continue;
            $coSan = $this->cotCua($bang);

            foreach ($cots as $c){
                if (!in_array($c, $coSan, true)) continue;

                // REGEXP '&#[0-9]+;' -> chỉ những dòng thật sự có thực thể số
                $rows = $this->db->getRaw("SELECT `id`, `$c` AS v FROM `$bang` WHERE `$c` REGEXP '&#[0-9]+;'");

                foreach ($rows ?: [] as $r){
                    $moi = $this->goHet($r['v']);
                    if ($moi === $r['v']) continue;
                    $this->db->update($bang, [$c => $moi], '`id` = ?', [$r['id']]);
                    $sua++;
                }
            }
        }

        echo "  Da go ma hoa chong lop cho $sua o du lieu.\n";
    }

    public function down(){
        // KHÔNG mã hoá ngược lại.
        //
        // Chiều xuôi mất thông tin: gỡ xong thì không còn biết ô nào vốn bị
        // chồng mấy lớp. Mã hoá lại toàn bộ là làm hỏng cả những ô vốn đúng.
        // Đây là migration SỬA DỮ LIỆU HỎNG, quay ngược lại chẳng để làm gì.
        echo "  (khong co chieu nguoc - day la migration sua du lieu hong)\n";
    }
};

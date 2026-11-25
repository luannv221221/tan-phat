<?php
/**
 * VIDEO CHO HÀNG HOÁ — khách ghi 25/11/2026 ở dòng "Cập nhật nhanh giá hàng hóa":
 * "Thêm thông số cơ bản, video". Thông số kỹ thuật đã có; video thì `parts` chưa
 * có cột nào.
 *
 * Một link YouTube cho mỗi mặt hàng, lưu dạng chuẩn
 * https://www.youtube.com/watch?v=<mã> (link_youtube()). NULL = không có video.
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: cột mới, cho phép NULL — code cũ không biết
 * tới nó. Chạy lại được: có cột rồi thì bỏ qua.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   ALTER TABLE `parts` ADD COLUMN `video_url` VARCHAR(255) NULL DEFAULT NULL;
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('parts')) return;
        if ($this->coCot('parts', 'video_url')){
            echo "  `parts`.`video_url` da co — bo qua.\n";
            return;
        }
        $this->run("ALTER TABLE `parts` ADD COLUMN `video_url` VARCHAR(255) NULL DEFAULT NULL");
        echo "  Da them `parts`.`video_url`.\n";
    }

    public function down(){
        if ($this->hasTable('parts') && $this->coCot('parts', 'video_url')){
            $this->run("ALTER TABLE `parts` DROP COLUMN `video_url`");
        }
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    private function coCot($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){ if (isset($r['Field']) && $r['Field'] === $cot) return true; }
        return false;
    }
};

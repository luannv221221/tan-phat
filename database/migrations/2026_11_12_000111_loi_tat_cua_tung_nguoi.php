<?php
/**
 * LỐI TẮT TRÊN THANH TRÊN CÙNG DO TỪNG NGƯỜI TỰ ĐẶT (khách yêu cầu 12/11/2026:
 * "làm cả cái nút shortcut trên menu cho anh người ta có thể chủ động set").
 *
 * Trước đây thanh trên có ba nút viết cứng — Bán hàng / Mua hàng / Chăm sóc —
 * theo tài liệu khách 05/11 ("thao tác nhanh, gắn link của báo giá, đơn mua,
 * danh sách khách hàng"). Ai cũng thấy đúng ba nút đó, dù người làm kho chẳng
 * bao giờ bấm "Bán hàng" còn thủ quỹ thì cần "Phiếu thu".
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  MỘT CỘT TRÊN `users`, KHÔNG PHẢI MỘT BẢNG MỚI
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Đây là danh sách ngắn (tối đa vài mục) của MỘT người, luôn đọc và ghi cả
 * khối, không bao giờ truy vấn "ai đã ghim màn X". Bảng riêng
 * (user_id, target, sort_order) là ba cột và một khoá duy nhất cho một thứ mà
 * hai ngón tay đếm được. Và `$infoUser` trong header chính là nguyên dòng
 * `users`, nên cột mới tự có mặt ở mọi màn mà không thêm một truy vấn nào.
 *
 * LƯU DẠNG JSON, BA TRẠNG THÁI KHÁC NHAU — đây là chỗ dễ nhầm:
 *
 *   NULL        chưa từng tuỳ chỉnh -> dùng bộ mặc định (ba nút cũ)
 *   '[]'        chủ động bỏ hết    -> không hiện nút nào
 *   '["a","b"]' danh sách đã chọn
 *
 * Gộp NULL với '[]' thì người bỏ hết nút bấm Lưu xong mở lại thấy ba nút cũ
 * mọc về — nhìn như chương trình không nghe lời.
 *
 * MẶC ĐỊNH LÀ NULL: không ai bị đổi gì sau lưng. Người chưa động vào vẫn thấy
 * đúng ba nút như hôm qua.
 *
 * CHẠY LẠI ĐƯỢC: cột có rồi thì bỏ qua.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        if (!$this->hasTable('users')) return;

        if (!$this->hasColumn('users', 'shortcuts')){
            $this->run("ALTER TABLE `users` ADD COLUMN `shortcuts` TEXT NULL DEFAULT NULL AFTER `current_activity`");
            echo "  Da them `users`.`shortcuts`.\n";
        }
    }

    public function down(){

        if ($this->hasColumn('users', 'shortcuts')){
            $this->run("ALTER TABLE `users` DROP COLUMN `shortcuts`");
        }
    }

    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }
};

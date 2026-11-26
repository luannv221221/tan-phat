<?php
/**
 * CHAT: "ĐANG NHẬP…" HAI CHIỀU — hai cột ghi lúc mỗi bên gõ phím gần nhất.
 *
 * Khách thử chat 26/11/2026 (ảnh anh gửi, công ty DMSG): "cái chat cứ phải f5
 * mới nhận tin", và gõ ngay trong cuộc chat "Nhưng ko thấy typing".
 *
 *   customer_typing_at  DATETIME NULL  khách gõ trong khung chat trên web
 *   staff_typing_at     DATETIME NULL  nhân viên gõ ở màn Hội thoại
 *
 * Bên kia hỏi tin mới (poll) vài giây một lần; mốc trong vòng 6 giây thì hiện
 * "… đang nhập…". Gửi tin xong thì mốc của bên gửi về NULL (tắt ngay).
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: chỉ thêm cột NULL, code cũ không đọc /
 * ghi. Code mới mà chưa chạy migration thì chỉ mất dòng "đang nhập…" (đường báo
 * gõ trả lỗi lặng lẽ) — tự nhận tin vẫn chạy. Chạy lại được.
 *
 * Chạy tay trong phpMyAdmin (dòng nào báo "Duplicate column" là đã có, bỏ qua):
 *
 *   ALTER TABLE `chat_conversations` ADD COLUMN `customer_typing_at` DATETIME NULL DEFAULT NULL;
 *   ALTER TABLE `chat_conversations` ADD COLUMN `staff_typing_at`    DATETIME NULL DEFAULT NULL;
 *
 * Gỡ (tương đương down()):
 *
 *   ALTER TABLE `chat_conversations` DROP COLUMN `staff_typing_at`, DROP COLUMN `customer_typing_at`;
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('chat_conversations')) return;
        foreach (['customer_typing_at', 'staff_typing_at'] as $cot){
            if (!$this->hasColumn('chat_conversations', $cot)){
                $this->run("ALTER TABLE `chat_conversations` ADD COLUMN `$cot` DATETIME NULL DEFAULT NULL");
                echo "  Da them `chat_conversations`.`$cot`.\n";
            }
        }
    }

    public function down(){
        foreach (['staff_typing_at', 'customer_typing_at'] as $cot){
            if ($this->hasColumn('chat_conversations', $cot)){
                $this->run("ALTER TABLE `chat_conversations` DROP COLUMN `$cot`");
            }
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

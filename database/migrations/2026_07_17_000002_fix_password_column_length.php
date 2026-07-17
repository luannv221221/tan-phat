<?php
/**
 * 🔴 SỬA LỖI CHẶN ĐĂNG NHẬP — cột users.password quá ngắn cho bcrypt.
 *
 * Dump cũ khai báo:  `password` varchar(50)
 * Vì md5 chỉ dài 32 ký tự nên 50 là đủ.
 *
 * Nhưng hash bcrypt dài ĐÚNG 60 ký tự (vd: $2y$10$....).
 * Ghi hash 60 ký tự vào cột VARCHAR(50) trên MySQL:
 *   - Chế độ mặc định (STRICT_TRANS_TABLES): lỗi "Data too long for column"
 *   - Chế độ không strict: MySQL CẮT CỤT còn 50 ký tự, không báo gì
 *     => password_verify() luôn trả về false => KHÔNG AI ĐĂNG NHẬP ĐƯỢC,
 *        và cũng không có thông báo lỗi nào để lần ra nguyên nhân.
 *
 * Lỗi này không lộ ra khi test bằng SQLite vì SQLite bỏ qua độ dài VARCHAR.
 *
 * Để 255 (không phải 60) theo thông lệ: đủ chỗ cho argon2id và các thuật toán sau này.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("
            ALTER TABLE `users`
            MODIFY `password` VARCHAR(255)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            DEFAULT NULL
        ");
    }

    public function down(){
        // CANH BAO: quay lai 50 se cat cut moi hash bcrypt dang co
        // => khong ai dang nhap duoc nua. Chi rollback khi DB con toan hash md5.
        $this->run("
            ALTER TABLE `users`
            MODIFY `password` VARCHAR(50)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            DEFAULT NULL
        ");
    }
};

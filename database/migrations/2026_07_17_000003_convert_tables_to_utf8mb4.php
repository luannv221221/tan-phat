<?php
/**
 * 🔴 Chuyển các bảng CŨ sang utf8mb4.
 *
 * VÌ SAO CẦN — lỗi trong chính migration 000001:
 *   Migration 000001 dùng `CREATE TABLE IF NOT EXISTS ... CHARSET=utf8mb4`.
 *   Với DB TRẮNG thì đúng. Nhưng với DB ĐANG CHẠY (import từ dump cũ),
 *   bảng đã tồn tại => `IF NOT EXISTS` BỎ QUA HOÀN TOÀN => bảng vẫn là utf8mb3.
 *   Comment trong 000001 ghi "utf8mb4 thay utf8" nhưng thực tế không đổi gì.
 *
 * HẬU QUẢ THẬT (bắt được khi chạy trên MySQL 8.0.44):
 *   Connection nay dùng charset=utf8mb4. Khi INSERT một tham số utf8mb4
 *   vào cột utf8mb3, MySQL 8 từ chối:
 *     SQLSTATE[HY000]: General error: 3988
 *     Conversion from collation utf8mb4_general_ci into utf8mb3_unicode_ci impossible
 *   => Không lưu được emoji và một số ký tự 4 byte. Với dữ liệu ERP
 *      (tên hàng hoá, ghi chú khách hàng) đây là lỗi chặn nghiệp vụ.
 *
 * Lỗi này KHÔNG lộ ra khi test bằng SQLite vì SQLite không có khái niệm collation.
 *
 * Dùng CONVERT TO CHARACTER SET để đổi cả bảng lẫn mọi cột text bên trong.
 */

use App\core\Migration;

return new class extends Migration {

    /** Các bảng nền cần chuyển */
    protected $tables = ['groups', 'users', 'modules', 'permissions', 'options', 'login_token'];

    public function up(){
        foreach ($this->tables as $t){
            if (!$this->hasTable($t)) continue;

            $this->run("ALTER TABLE `$t` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(){
        // Quay lai utf8mb3 se lam MAT du lieu 4 byte (emoji) da luu.
        // Chi rollback khi chac chan chua co du lieu 4 byte nao.
        foreach ($this->tables as $t){
            if (!$this->hasTable($t)) continue;

            $this->run("ALTER TABLE `$t` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci");
        }
    }
};

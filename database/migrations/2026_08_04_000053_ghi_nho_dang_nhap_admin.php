<?php
/**
 * GHI NHỚ ĐĂNG NHẬP cho trang quản trị.
 *
 * Vì sao phiên hay hết: AuthMiddleware gọi LoginToken::removeExpired() ở MỌI
 * request, xoá token sau _SESSION_IDLE_MINUTES (mặc định 15) phút không thao
 * tác. Không liên quan gì tới session PHP.
 *
 * `remember`      — token này có được gia hạn dài hay không.
 * `remember_hash` — SHA-256 của giá trị nằm trong cookie ghi nhớ.
 *
 * Lưu HASH chứ không lưu nguyên giá trị cookie: cookie này sống 30 ngày và
 * dùng được thẳng để vào tài khoản, nên nếu CSDL bị lộ mà lưu nguyên thì kẻ
 * lấy được sẽ đăng nhập được ngay vào mọi tài khoản đang ghi nhớ.
 * Cột `token` cũ giữ nguyên, không đụng tới.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $this->run("ALTER TABLE `login_tokens`
                    ADD COLUMN `remember` TINYINT(1) NOT NULL DEFAULT 0 AFTER `token`,
                    ADD COLUMN `remember_hash` VARCHAR(64) DEFAULT NULL AFTER `remember`");

        // UNIQUE vừa chặn trùng vừa tạo index cho câu tra cứu lúc khôi phục phiên
        $this->run("ALTER TABLE `login_tokens`
                    ADD UNIQUE KEY `uq_login_tokens_remember` (`remember_hash`)");
    }

    public function down(){
        $this->run("ALTER TABLE `login_tokens` DROP INDEX `uq_login_tokens_remember`");
        $this->run("ALTER TABLE `login_tokens` DROP COLUMN `remember_hash`, DROP COLUMN `remember`");
    }
};

<?php
/**
 * HỒ SƠ CỦA TÔI — số điện thoại, địa chỉ, ảnh đại diện cho từng tài khoản.
 *
 * Khách ghi 25/11/2026 ở dòng "Xây dựng chức năng đăng xuất, đăng nhập":
 * "Cho sửa profile thay cho đổi mật khẩu, ảnh, tên, địa chỉ…". Bảng `users`
 * trước đó chỉ có tên + email + mật khẩu; không có chỗ nào để ghi điện thoại,
 * địa chỉ hay ảnh của chính người dùng.
 *
 * Ba cột đều NULL, không mặc định: người chưa khai thì vẫn như hôm qua (thanh
 * trên hiện biểu tượng người, không hiện ảnh vỡ).
 *
 *   phone    VARCHAR(20)   lưu nguyên văn như người dùng gõ ("0912 345 678"),
 *                          kiểm bằng is_phone() lúc lưu — 20 đủ cho cả dạng
 *                          có dấu cách / dấu chấm / +84.
 *   address  VARCHAR(255)  một dòng tự do. Không tách tỉnh / phường như địa chỉ
 *                          giao hàng: đây là thông tin liên hệ của nhân viên,
 *                          không ai lọc hay tính phí theo nó.
 *   avatar   VARCHAR(255)  đường dẫn TƯƠNG ĐỐI do upload_image() trả về
 *                          (public/assets/uploads/avatars/<tên ngẫu nhiên>),
 *                          giống cột `garages`.`logo`.
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: chỉ thêm cột NULL; code cũ không đọc
 * cũng không ghi ba cột này (màn Người dùng ghi theo danh sách cột cố định),
 * nên chạy xong mà chưa đẩy code thì không có gì đổi.
 * Chạy lại được: cột nào có rồi thì bỏ qua.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php) — chạy từng dòng,
 * dòng nào báo "Duplicate column" là cột đó đã có, bỏ qua:
 *
 *   ALTER TABLE `users` ADD COLUMN `phone`   VARCHAR(20)  NULL DEFAULT NULL AFTER `email`;
 *   ALTER TABLE `users` ADD COLUMN `address` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`;
 *   ALTER TABLE `users` ADD COLUMN `avatar`  VARCHAR(255) NULL DEFAULT NULL AFTER `address`;
 *
 * Gỡ (tương đương down()):
 *
 *   ALTER TABLE `users` DROP COLUMN `avatar`, DROP COLUMN `address`, DROP COLUMN `phone`;
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        if (!$this->hasTable('users')) return;

        if (!$this->hasColumn('users', 'phone')){
            $this->run("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) NULL DEFAULT NULL AFTER `email`");
            echo "  Da them `users`.`phone`.\n";
        }
        if (!$this->hasColumn('users', 'address')){
            $this->run("ALTER TABLE `users` ADD COLUMN `address` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`");
            echo "  Da them `users`.`address`.\n";
        }
        if (!$this->hasColumn('users', 'avatar')){
            $this->run("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL DEFAULT NULL AFTER `address`");
            echo "  Da them `users`.`avatar`.\n";
        }
    }

    public function down(){

        foreach (['avatar', 'address', 'phone'] as $cot){
            if ($this->hasColumn('users', $cot)){
                $this->run("ALTER TABLE `users` DROP COLUMN `$cot`");
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

<?php
/**
 * KẾ TOÁN — GÁN / BỎ GÁN DANH MỤC DÙNG CHUNG THEO TỪNG CÔNG TY.
 *
 * Khách hỏi 26/11/2026 "danh mục tk, danh mục loại phiếu nó dùng chung nhau à?
 * vừa tạo công ty thử nó có đủ luôn"; anh chốt: "tân phát dựng sẵn nhưng cho
 * thể gán hoặc không gán theo từng cty, không phải cty con nào cũng giống nhau".
 *
 * Bảng ghi các dòng dùng chung (tài khoản / loại phiếu, `garage_id` NULL) đã bị
 * BỎ GÁN cho một công ty. Không có dòng = được gán — nên công ty đang có và
 * công ty mở mới vẫn thấy đủ như trước, Tân Phát thêm tài khoản chuẩn mới thì
 * mọi công ty có luôn; chỉ chỗ nào Tân Phát bỏ tick mới khác.
 *
 *   kind      'account' (acc_accounts) | 'vtype' (acc_voucher_types)
 *   item_id   id dòng dùng chung
 *   garage_id công ty bị bỏ gán
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: chỉ thêm bảng mới, code cũ không đọc.
 * Code mới mà chưa có bảng thì coi như gán hết (như hôm nay). Chạy lại được.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   CREATE TABLE IF NOT EXISTS `acc_shared_hidden` (
 *     `kind`      VARCHAR(16)  NOT NULL,
 *     `item_id`   INT UNSIGNED NOT NULL,
 *     `garage_id` INT UNSIGNED NOT NULL,
 *     `create_at` DATETIME     NULL DEFAULT NULL,
 *     PRIMARY KEY (`kind`, `item_id`, `garage_id`),
 *     KEY `idx_ash_garage` (`garage_id`)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * Gỡ: DROP TABLE IF EXISTS `acc_shared_hidden`;
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if ($this->hasTable('acc_shared_hidden')) return;
        $this->run("CREATE TABLE IF NOT EXISTS `acc_shared_hidden` (
            `kind`      VARCHAR(16)  NOT NULL,
            `item_id`   INT UNSIGNED NOT NULL,
            `garage_id` INT UNSIGNED NOT NULL,
            `create_at` DATETIME     NULL DEFAULT NULL,
            PRIMARY KEY (`kind`, `item_id`, `garage_id`),
            KEY `idx_ash_garage` (`garage_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "  Da tao bang `acc_shared_hidden`.\n";
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `acc_shared_hidden`");
    }
};

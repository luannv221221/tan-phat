<?php
/**
 * PHÂN HỆ CSKH (Chăm sóc khách hàng) — lát cắt CSKH-1.
 *
 * - customer_groups : nhóm khách hàng (gắn vào partners).
 * - warranty_requests : phiếu yêu cầu bảo hành / sửa chữa (biểu mẫu CSKH lõi),
 *     có luồng trạng thái tiếp nhận -> đang xử lý -> hoàn tất / huỷ; dùng cho
 *     Lịch bảo hành + Báo cáo CSKH (tính động).
 * - product_reviews : đánh giá / bình luận sản phẩm (TASK_84) từ thành viên web,
 *     admin kiểm duyệt trước khi hiển thị.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $now = date('Y-m-d H:i:s');

        // ---------- Nhóm khách hàng ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `customer_groups` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Gắn nhóm vào đối tượng (khách/NCC dùng chung partners)
        $this->run("ALTER TABLE `partners` ADD COLUMN `group_id` INT(11) DEFAULT NULL AFTER `type`");
        $this->run("ALTER TABLE `partners`
                    ADD CONSTRAINT `fk_partners_group` FOREIGN KEY (`group_id`)
                    REFERENCES `customer_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");

        // ---------- Phiếu bảo hành / sửa chữa ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `warranty_requests` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `request_no` VARCHAR(50) NOT NULL,
                `partner_id` INT(11) DEFAULT NULL,
                `customer_name` VARCHAR(255) DEFAULT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `part_id` INT(11) DEFAULT NULL,
                `product_name` VARCHAR(255) DEFAULT NULL,
                `serial_no` VARCHAR(100) DEFAULT NULL,
                `received_date` DATE NOT NULL,
                `appointment_date` DATE DEFAULT NULL,
                `completed_date` DATE DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'received',
                `issue` TEXT,
                `diagnosis` TEXT,
                `technician` VARCHAR(150) DEFAULT NULL,
                `fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `note` VARCHAR(255) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_warranty_no` (`request_no`),
                KEY `idx_warranty_status` (`status`),
                KEY `idx_warranty_partner` (`partner_id`),
                CONSTRAINT `fk_warranty_partner`
                    FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_warranty_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đánh giá / bình luận sản phẩm (TASK_84) ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `product_reviews` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `part_id` INT(11) NOT NULL,
                `member_id` INT(11) DEFAULT NULL,
                `author_name` VARCHAR(150) NOT NULL,
                `rating` TINYINT(1) NOT NULL DEFAULT 5,
                `comment` TEXT,
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_review_part` (`part_id`, `status`),
                CONSTRAINT `fk_review_part`
                    FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_review_member`
                    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dữ liệu mồi nhóm KH ----------
        foreach ([['Khách lẻ', 0], ['Đại lý', 5], ['Garage đối tác', 8]] as $i => $g){
            $ex = $this->db->table('customer_groups')->where('name', '=', $g[0])->first();
            if (empty($ex)){
                $this->db->insert('customer_groups', [
                    'name' => $g[0], 'discount_percent' => $g[1], 'sort_order' => $i,
                    'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'warranty'        => 'Phiếu bảo hành',
            'lich-bao-hanh'   => 'Lịch bảo hành',
            'customer-groups' => 'Nhóm khách hàng',
            'reviews'         => 'Kiểm duyệt đánh giá',
            'bao-cao-cskh'    => 'Báo cáo CSKH',
        ];
        $viewOnly = ['lich-bao-hanh' => true, 'bao-cao-cskh' => true];

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($admin) || empty($module)) continue;
            $roles = isset($viewOnly[$link]) ? ['view'] : ['view', 'add', 'edit', 'delete'];
            foreach ($roles as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])
                    ->where('group_id', '=', $admin['id'])
                    ->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `product_reviews`");
        $this->run("DROP TABLE IF EXISTS `warranty_requests`");
        // gỡ FK + cột group_id trước khi drop customer_groups
        $this->run("ALTER TABLE `partners` DROP FOREIGN KEY `fk_partners_group`");
        $this->run("ALTER TABLE `partners` DROP COLUMN `group_id`");
        $this->run("DROP TABLE IF EXISTS `customer_groups`");

        foreach (['warranty', 'lich-bao-hanh', 'customer-groups', 'reviews', 'bao-cao-cskh'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

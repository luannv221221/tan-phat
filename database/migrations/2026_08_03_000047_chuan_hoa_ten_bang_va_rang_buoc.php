<?php
/**
 * CHUẨN HOÁ LƯỢC ĐỒ CSDL TRƯỚC KHI BÀN GIAO.
 *
 * Rà soát 71 bảng phát hiện ba nhóm vấn đề, migration này xử lý cả ba.
 *
 * 1) BẢNG THỪA
 *    `options` là tàn dư của bộ khung 2021: không có model, không dòng mã nào
 *    đọc nó, và trùng chức năng với `site_settings` (cùng là kho key-value cấu
 *    hình). Giữ lại chỉ gây nhầm "cấu hình nằm ở đâu".
 *
 * 2) TÊN BẢNG LỆCH QUY ƯỚC
 *    - `login_token` là bảng DUY NHẤT trong 71 bảng ở dạng số ít.
 *    - `attributes` quá chung cho một bảng chỉ chứa thuộc tính hàng hoá; bảng
 *      con của nó đã tên là `part_attribute_values`.
 *    - Năm bảng `product_*` và mười bảng `part_*` cùng mô tả một thực thể
 *      (bảng chính là `parts`) — một khái niệm mà hai tiền tố.
 *    - `projects` (portfolio website) quá dễ nhầm với `acc_projects` (mã vụ
 *      việc kế toán); trong ProjectsModel đã phải viết hẳn một dòng chú thích
 *      để cảnh báo nhầm lẫn này.
 *
 * 3) THIẾU RÀNG BUỘC
 *    Nhóm bảng nền 2021 (`users`, `login_token`, `permissions`, `visits`) được
 *    tạo trước khi dự án áp dụng khoá ngoại, nên đang thiếu:
 *    - `users.email` không UNIQUE  => tạo được hai tài khoản cùng email.
 *    - `users` có index thừa tên `id` trùng hoàn toàn với PRIMARY KEY.
 *    - `login_token.token` không index, trong khi mỗi request đều tra theo nó.
 *    - `permissions` không có index lẫn khoá ngoại nào, dù 201 dòng và được
 *      đọc mỗi lần kiểm tra quyền.
 *    Đã kiểm tra trước: không có dòng nào mồ côi nên thêm khoá ngoại không vỡ.
 *
 * KHÔNG đụng tới `create_at`/`update_at` (lệch chuẩn `created_at` thông
 * thường nhưng nhất quán ở cả 71 bảng — đổi là sửa hàng trăm chỗ mà không
 * thêm giá trị), và không đổi `groups` (là từ khoá dành riêng của MySQL 8
 * nhưng QueryBuilder đã bọc backtick và có test canh giữ).
 */

use App\core\Migration;

return new class extends Migration {

    /** [tên cũ => tên mới] */
    protected $renames = [
        'login_token'           => 'login_tokens',
        'attributes'            => 'part_attributes',
        'product_brands'        => 'part_brands',
        'product_manufacturers' => 'part_manufacturers',
        'product_origins'       => 'part_origins',
        'product_units'         => 'part_units',
        'product_reviews'       => 'part_reviews',
        'projects'              => 'site_projects',
    ];

    /** [bảng (tên MỚI) => [index cũ => index mới]] */
    protected $indexRenames = [
        'part_attributes'     => ['uq_attributes_slug'             => 'uq_part_attributes_slug'],
        'part_brands'         => ['uq_product_brands_slug'         => 'uq_part_brands_slug'],
        'part_manufacturers'  => ['uq_product_manufacturers_slug'  => 'uq_part_manufacturers_slug'],
        'part_origins'        => ['uq_product_origins_slug'        => 'uq_part_origins_slug'],
        'part_units'          => ['uq_product_units_slug'          => 'uq_part_units_slug'],
        'site_projects'       => ['uq_projects_slug'               => 'uq_site_projects_slug'],
    ];

    public function up(){

        // ---------- 1) Bỏ bảng thừa ----------
        $this->run("DROP TABLE IF EXISTS `options`");

        // ---------- 2) Đổi tên bảng ----------
        // Khoá ngoại trỏ TỚI bảng được đổi tên sẽ tự trỏ theo tên mới, không
        // cần dựng lại. Chỉ đổi khi bảng cũ còn và bảng mới chưa có, để chạy
        // lại nhiều lần vẫn an toàn.
        foreach ($this->renames as $old => $new){
            if ($this->tableExists($old) && !$this->tableExists($new)){
                $this->run("RENAME TABLE `$old` TO `$new`");
            }
        }

        // ---------- 2b) Đổi tên index còn mang tên bảng cũ ----------
        foreach ($this->indexRenames as $table => $pairs){
            foreach ($pairs as $old => $new){
                if ($this->indexExists($table, $old) && !$this->indexExists($table, $new)){
                    $this->run("ALTER TABLE `$table` RENAME INDEX `$old` TO `$new`");
                }
            }
        }

        // ---------- 3) Bổ sung ràng buộc còn thiếu ----------

        // users: bỏ index thừa trùng PRIMARY, thêm UNIQUE email + FK nhóm
        if ($this->indexExists('users', 'id')){
            $this->run("ALTER TABLE `users` DROP INDEX `id`");
        }
        if (!$this->indexExists('users', 'uq_users_email')){
            $this->run("ALTER TABLE `users` ADD UNIQUE KEY `uq_users_email` (`email`)");
        }
        if (!$this->fkExists('users', 'fk_users_group')){
            $this->run("ALTER TABLE `users`
                        ADD CONSTRAINT `fk_users_group` FOREIGN KEY (`group_id`)
                        REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        }

        // login_tokens: index cho cột tra cứu + FK về người dùng
        if (!$this->indexExists('login_tokens', 'idx_login_tokens_token')){
            $this->run("ALTER TABLE `login_tokens` ADD KEY `idx_login_tokens_token` (`token`)");
        }
        if (!$this->fkExists('login_tokens', 'fk_login_tokens_user')){
            $this->run("ALTER TABLE `login_tokens`
                        ADD CONSTRAINT `fk_login_tokens_user` FOREIGN KEY (`user_id`)
                        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        }

        // permissions: xoá quyền theo nhóm/module thì không còn dòng rác
        if (!$this->indexExists('permissions', 'idx_permissions_group')){
            $this->run("ALTER TABLE `permissions` ADD KEY `idx_permissions_group` (`group_id`)");
        }
        if (!$this->indexExists('permissions', 'idx_permissions_module')){
            $this->run("ALTER TABLE `permissions` ADD KEY `idx_permissions_module` (`module_id`)");
        }
        if (!$this->fkExists('permissions', 'fk_permissions_group')){
            $this->run("ALTER TABLE `permissions`
                        ADD CONSTRAINT `fk_permissions_group` FOREIGN KEY (`group_id`)
                        REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        }
        if (!$this->fkExists('permissions', 'fk_permissions_module')){
            $this->run("ALTER TABLE `permissions`
                        ADD CONSTRAINT `fk_permissions_module` FOREIGN KEY (`module_id`)
                        REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        }

        // visits: bảng log, chỉ cần index (không FK để xoá member không vướng)
        if (!$this->indexExists('visits', 'idx_visits_member')){
            $this->run("ALTER TABLE `visits` ADD KEY `idx_visits_member` (`member_id`)");
        }
    }

    public function down(){

        // Gỡ ràng buộc đã thêm
        foreach ([
            ['visits',      'idx_visits_member',        'INDEX'],
            ['permissions', 'fk_permissions_module',    'FK'],
            ['permissions', 'fk_permissions_group',     'FK'],
            ['permissions', 'idx_permissions_module',   'INDEX'],
            ['permissions', 'idx_permissions_group',    'INDEX'],
            ['login_tokens','fk_login_tokens_user',     'FK'],
            ['login_tokens','idx_login_tokens_token',   'INDEX'],
            ['users',       'fk_users_group',           'FK'],
            ['users',       'uq_users_email',           'INDEX'],
        ] as $item){
            list($table, $name, $kind) = $item;
            if ($kind === 'FK' && $this->fkExists($table, $name)){
                $this->run("ALTER TABLE `$table` DROP FOREIGN KEY `$name`");
            }
            if ($kind === 'INDEX' && $this->indexExists($table, $name)){
                $this->run("ALTER TABLE `$table` DROP INDEX `$name`");
            }
        }
        if ($this->tableExists('users') && !$this->indexExists('users', 'id')){
            $this->run("ALTER TABLE `users` ADD KEY `id` (`id`)");
        }

        // Trả lại tên index rồi tên bảng
        foreach ($this->indexRenames as $table => $pairs){
            foreach ($pairs as $old => $new){
                if ($this->indexExists($table, $new) && !$this->indexExists($table, $old)){
                    $this->run("ALTER TABLE `$table` RENAME INDEX `$new` TO `$old`");
                }
            }
        }
        foreach ($this->renames as $old => $new){
            if ($this->tableExists($new) && !$this->tableExists($old)){
                $this->run("RENAME TABLE `$new` TO `$old`");
            }
        }

        // Dựng lại bảng options (rỗng — dữ liệu cũ không khôi phục được)
        $this->run("
            CREATE TABLE IF NOT EXISTS `options` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `opt_name` VARCHAR(200) DEFAULT NULL,
                `opt_value` MEDIUMTEXT DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /* ---------- Tiện ích: hỏi information_schema ---------- */

    protected function tableExists($table){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$table]);
        return !empty($r['c']);
    }

    protected function indexExists($table, $index){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $index]);
        return !empty($r['c']);
    }

    protected function fkExists($table, $name){
        $r = $this->db->firstRaw(
            "SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $name]);
        return !empty($r['c']);
    }
};

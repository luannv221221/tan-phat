<?php
/**
 * CMS NỘI DUNG WEBSITE — Tin tức + Dự án (SRS Phần B mục 4.9, TASK_82).
 *
 * - news_categories + news : tin tức có phân mục, hiển thị trên storefront.
 * - projects : dự án / công trình đã thực hiện (portfolio), showcase trên web.
 *
 * ⚠️ Module link `projects` ĐÃ bị Mã vụ việc kế toán chiếm -> dùng `du-an`.
 *   Controller Projects (kế toán) cũng đã tồn tại -> admin dùng `Projectportfolio`.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        // ---------- Danh mục tin ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `news_categories` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `slug` VARCHAR(180) NOT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_news_cat_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Tin tức ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `news` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `category_id` INT(11) DEFAULT NULL,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(280) NOT NULL,
                `summary` VARCHAR(500) DEFAULT NULL,
                `content` LONGTEXT,
                `thumbnail` VARCHAR(255) DEFAULT NULL,
                `is_published` TINYINT(1) NOT NULL DEFAULT 0,
                `published_at` DATETIME DEFAULT NULL,
                `view_count` INT(11) NOT NULL DEFAULT 0,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_news_slug` (`slug`),
                KEY `idx_news_cat` (`category_id`),
                KEY `idx_news_pub` (`is_published`, `published_at`),
                CONSTRAINT `fk_news_cat`
                    FOREIGN KEY (`category_id`) REFERENCES `news_categories` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dự án / công trình (portfolio) ----------
        $this->run("
            CREATE TABLE IF NOT EXISTS `projects` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(280) NOT NULL,
                `client` VARCHAR(200) DEFAULT NULL,
                `location` VARCHAR(200) DEFAULT NULL,
                `summary` VARCHAR(500) DEFAULT NULL,
                `content` LONGTEXT,
                `thumbnail` VARCHAR(255) DEFAULT NULL,
                `completed_at` DATE DEFAULT NULL,
                `is_published` TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_projects_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dữ liệu mồi danh mục tin ----------
        foreach ([['Tin công ty', 'tin-cong-ty'], ['Kiến thức kỹ thuật', 'kien-thuc-ky-thuat'], ['Khuyến mãi', 'khuyen-mai']] as $i => $c){
            $ex = $this->db->table('news_categories')->where('slug', '=', $c[1])->first();
            if (empty($ex)){
                $this->db->insert('news_categories', [
                    'name' => $c[0], 'slug' => $c[1], 'sort_order' => $i, 'status' => 1, 'create_at' => $now,
                ]);
            }
        }

        // ---------- Đăng ký module + quyền Admin ----------
        // 'news' đã có sẵn (id 6) -> chỉ cần đảm bảo đủ quyền CRUD.
        $modules = [
            'news'            => 'Quản lý tin tức',
            'news-categories' => 'Danh mục tin',
            'du-an'           => 'Dự án',
        ];
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($admin) || empty($module)) continue;
            foreach (['view', 'add', 'edit', 'delete'] as $role){
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
        $this->run("DROP TABLE IF EXISTS `news`");
        $this->run("DROP TABLE IF EXISTS `news_categories`");
        $this->run("DROP TABLE IF EXISTS `projects`");
        // giữ module 'news' (có sẵn từ đầu); gỡ 2 module mới
        foreach (['news-categories', 'du-an'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

<?php
/**
 * NHÂN SỰ — phòng ban, chức vụ, nhân viên, đơn nghỉ phép.
 *
 * DỰNG LẠI (03/08/2026): file gốc của migration này bị mất khỏi mã nguồn —
 * bảng `migrations` có ghi nhận đã chạy nhưng trên đĩa không còn file, nên
 * `php migrate.php status` báo cảnh báo, và quan trọng hơn: ai dựng CSDL
 * hoàn toàn từ migration sẽ KHÔNG có 4 bảng nhân sự.
 *
 * Nội dung dựng lại đúng theo lược đồ đang chạy (SHOW CREATE TABLE) nên trên
 * CSDL hiện có nó không đổi gì; chỉ có tác dụng với bản cài mới.
 *
 * Quan hệ:
 *   employees.department_id -> departments  (xoá phòng ban: nhân viên còn, mất phòng)
 *   employees.position_id   -> positions    (tương tự)
 *   leave_requests.employee_id -> employees (xoá nhân viên: đơn nghỉ xoá theo)
 */

use App\core\Migration;

return new class extends Migration {

    /** [link module => tên hiển thị] */
    protected $modules = [
        'departments'    => 'Phòng ban',
        'positions'      => 'Chức vụ',
        'employees'      => 'Nhân viên',
        'leave-requests' => 'Đơn nghỉ phép',
    ];

    public function up(){

        $this->run("
            CREATE TABLE IF NOT EXISTS `departments` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) DEFAULT NULL,
                `name` VARCHAR(150) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `positions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `sort_order` INT(11) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `employees` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `department_id` INT(11) DEFAULT NULL,
                `position_id` INT(11) DEFAULT NULL,
                `gender` VARCHAR(10) DEFAULT NULL,
                `dob` DATE DEFAULT NULL,
                `phone` VARCHAR(30) DEFAULT NULL,
                `email` VARCHAR(150) DEFAULT NULL,
                `address` VARCHAR(255) DEFAULT NULL,
                `hire_date` DATE DEFAULT NULL,
                `salary_base` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `note` VARCHAR(255) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_employees_code` (`code`),
                KEY `idx_emp_dept` (`department_id`),
                KEY `idx_emp_pos` (`position_id`),
                CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`)
                    REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_emp_pos` FOREIGN KEY (`position_id`)
                    REFERENCES `positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `leave_requests` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `employee_id` INT(11) NOT NULL,
                `leave_type` VARCHAR(20) NOT NULL DEFAULT 'annual',
                `from_date` DATE NOT NULL,
                `to_date` DATE NOT NULL,
                `days` DECIMAL(4,1) NOT NULL DEFAULT 1.0,
                `reason` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `approver_note` VARCHAR(255) DEFAULT NULL,
                `created_by` INT(11) DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_leave_emp` (`employee_id`),
                KEY `idx_leave_status` (`status`),
                CONSTRAINT `fk_leave_emp` FOREIGN KEY (`employee_id`)
                    REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Đăng ký menu + cấp toàn quyền cho nhóm Admin.
        // Thiếu dòng trong `modules` thì RoleMiddleware chặn, menu không hiện.
        $now   = date('Y-m-d H:i:s');
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();

        foreach ($this->modules as $link => $name){
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($module)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
                $moduleId = $this->db->lastId();
            } else {
                $moduleId = $module['id'];
            }

            if (empty($admin)) continue;

            foreach (['view', 'add', 'edit', 'delete'] as $role){
                $has = $this->db->table('permissions')
                                ->where('module_id', '=', $moduleId)
                                ->where('group_id', '=', $admin['id'])
                                ->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', [
                        'module_id' => $moduleId, 'group_id' => $admin['id'], 'role' => $role,
                    ]);
                }
            }
        }
    }

    public function down(){
        foreach (array_keys($this->modules) as $link){
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($module)){
                $this->db->delete('permissions', '`module_id` = ?', [$module['id']]);
                $this->db->delete('modules', '`id` = ?', [$module['id']]);
            }
        }
        $this->run("DROP TABLE IF EXISTS `leave_requests`");
        $this->run("DROP TABLE IF EXISTS `employees`");
        $this->run("DROP TABLE IF EXISTS `positions`");
        $this->run("DROP TABLE IF EXISTS `departments`");
    }
};

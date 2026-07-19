<?php
/**
 * PHÂN HỆ NHÂN SỰ (HR) — lát cắt HR-1.
 *
 * - departments (phòng ban) + positions (chức vụ): danh mục.
 * - employees (hồ sơ nhân viên): gắn phòng ban + chức vụ.
 * - leave_requests (đơn nghỉ phép): luồng duyệt pending -> approved/rejected.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        // ---------- Phòng ban ----------
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

        // ---------- Chức vụ ----------
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

        // ---------- Nhân viên ----------
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
                CONSTRAINT `fk_emp_dept`
                    FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE,
                CONSTRAINT `fk_emp_pos`
                    FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Đơn nghỉ phép ----------
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
                CONSTRAINT `fk_leave_emp`
                    FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ---------- Dữ liệu mồi ----------
        foreach ([['KD', 'Phòng Kinh doanh'], ['KHO', 'Phòng Kho vận'], ['KT', 'Phòng Kế toán'], ['KT2', 'Phòng Kỹ thuật']] as $i => $d){
            $ex = $this->db->table('departments')->where('name', '=', $d[1])->first();
            if (empty($ex)){
                $this->db->insert('departments', ['code' => $d[0], 'name' => $d[1], 'sort_order' => $i, 'status' => 1, 'create_at' => $now]);
            }
        }
        foreach ([['Nhân viên'], ['Trưởng phòng'], ['Phó phòng'], ['Giám đốc']] as $i => $p){
            $ex = $this->db->table('positions')->where('name', '=', $p[0])->first();
            if (empty($ex)){
                $this->db->insert('positions', ['name' => $p[0], 'sort_order' => $i, 'status' => 1, 'create_at' => $now]);
            }
        }

        // ---------- Đăng ký module + quyền Admin ----------
        $modules = [
            'departments'    => 'Phòng ban',
            'positions'      => 'Chức vụ',
            'employees'      => 'Nhân viên',
            'leave-requests' => 'Đơn nghỉ phép',
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
                    $this->db->insert('permissions', ['module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `leave_requests`");
        $this->run("DROP TABLE IF EXISTS `employees`");
        $this->run("DROP TABLE IF EXISTS `positions`");
        $this->run("DROP TABLE IF EXISTS `departments`");
        foreach (['departments', 'positions', 'employees', 'leave-requests'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (!empty($m)){
                $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
                $this->db->delete('modules', '`id` = ?', [$m['id']]);
            }
        }
    }
};

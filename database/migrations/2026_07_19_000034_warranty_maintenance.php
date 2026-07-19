<?php
/**
 * CSKH — Nhắc bảo trì tự động.
 *
 * Thêm warranty_requests.reminded_at (ngày đã nhắc KH gần nhất). Ngày bảo trì
 * kế tiếp KHÔNG lưu cứng — tính động = completed_date + chu kỳ (cấu hình
 * site_settings 'maintenance_interval_months', mặc định 6) để đổi chu kỳ là
 * áp cho toàn bộ phiếu cũ. Đăng ký module 'nhac-bao-tri'.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("ALTER TABLE `warranty_requests`
                    ADD COLUMN `reminded_at` DATE DEFAULT NULL AFTER `completed_date`");

        // Cấu hình chu kỳ + cửa sổ nhắc mặc định
        foreach (['maintenance_interval_months' => '6', 'maintenance_window_days' => '30'] as $k => $v){
            $ex = $this->db->table('site_settings')->where('skey', '=', $k)->first();
            if (empty($ex)){
                $this->db->insert('site_settings', ['skey' => $k, 'svalue' => $v]);
            }
        }

        $link = 'nhac-bao-tri'; $name = 'Nhắc bảo trì';
        $ex = $this->db->table('modules')->where('link', '=', $link)->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', $link)->first();
        $admin  = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        if (!empty($admin) && !empty($module)){
            foreach (['view', 'edit'] as $role){
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
        $this->run("ALTER TABLE `warranty_requests` DROP COLUMN `reminded_at`");
        foreach (['maintenance_interval_months', 'maintenance_window_days'] as $k){
            $this->db->delete('site_settings', '`skey` = ?', [$k]);
        }
        $m = $this->db->table('modules')->where('link', '=', 'nhac-bao-tri')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

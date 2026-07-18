<?php
/**
 * KẾ TOÁN KT-5 — Sổ sách. Chỉ đăng ký module (không bảng mới; báo cáo tính
 * động từ getPostedLedger).
 *   - nhat-ky-chung : Nhật ký chung
 *   - so-cai        : Sổ cái / sổ chi tiết 1 tài khoản
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');
        $modules = ['nhat-ky-chung' => 'Nhật ký chung', 'so-cai' => 'Sổ cái / chi tiết TK'];
        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();

        foreach ($modules as $link => $name){
            $ex = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($ex)){
                $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
            }
            if (empty($admin)) continue;
            $module = $this->db->table('modules')->where('link', '=', $link)->first();
            $has = $this->db->table('permissions')
                ->where('module_id', '=', $module['id'])
                ->where('group_id', '=', $admin['id'])
                ->where('role', '=', 'view')->first();
            if (empty($has)){
                $this->db->insert('permissions', [
                    'module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => 'view',
                ]);
            }
        }
    }

    public function down(){
        foreach (['nhat-ky-chung', 'so-cai'] as $link){
            $m = $this->db->table('modules')->where('link', '=', $link)->first();
            if (empty($m)) continue;
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

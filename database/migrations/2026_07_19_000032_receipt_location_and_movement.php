<?php
/**
 * KHO-3 (nốt) — gắn vị trí trong kho vào dòng phiếu nhập + báo cáo biến động tồn.
 *
 * - goods_receipt_items.location_id : FK tới warehouse_locations (SET NULL),
 *   giữ song song cột `location` text = snapshot full_path để báo cáo/thẻ kho
 *   không phụ thuộc danh mục về sau.
 * - Đăng ký module 'bien-dong-ton' (biểu đồ biến động tồn, chỉ xem).
 *
 * (Migration chạy 1 lần theo batch nên ALTER trực tiếp, không cần check tồn tại.)
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        $now = date('Y-m-d H:i:s');

        $this->run("ALTER TABLE `goods_receipt_items`
                    ADD COLUMN `location_id` INT(11) DEFAULT NULL AFTER `location`");
        $this->run("ALTER TABLE `goods_receipt_items`
                    ADD CONSTRAINT `fk_ri_location` FOREIGN KEY (`location_id`)
                    REFERENCES `warehouse_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");

        // Module báo cáo biến động tồn (chỉ xem)
        $link = 'bien-dong-ton'; $name = 'Biến động tồn';
        $ex = $this->db->table('modules')->where('link', '=', $link)->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => $name, 'link' => $link, 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', $link)->first();
        $admin  = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        if (!empty($admin) && !empty($module)){
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
        $this->run("ALTER TABLE `goods_receipt_items` DROP FOREIGN KEY `fk_ri_location`");
        $this->run("ALTER TABLE `goods_receipt_items` DROP COLUMN `location_id`");

        $m = $this->db->table('modules')->where('link', '=', 'bien-dong-ton')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

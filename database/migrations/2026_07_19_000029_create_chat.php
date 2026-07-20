<?php
/**
 * STOREFRONT — Webchat (TASK_112-113). Polling (không websocket).
 *
 * - chat_conversations: mỗi khách 1 hội thoại (theo session_key / member).
 * - chat_messages: tin nhắn (customer / staff).
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        $now = date('Y-m-d H:i:s');

        $this->run("
            CREATE TABLE IF NOT EXISTS `chat_conversations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `member_id` INT(11) DEFAULT NULL,
                `session_key` VARCHAR(64) NOT NULL,
                `guest_name` VARCHAR(150) DEFAULT NULL,
                `guest_phone` VARCHAR(30) DEFAULT NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'open',
                `unread` TINYINT(1) NOT NULL DEFAULT 0,
                `last_message_at` DATETIME DEFAULT NULL,
                `create_at` DATETIME DEFAULT NULL,
                `update_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_chat_session` (`session_key`),
                KEY `idx_chat_member` (`member_id`),
                CONSTRAINT `fk_chat_member`
                    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->run("
            CREATE TABLE IF NOT EXISTS `chat_messages` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `conversation_id` INT(11) NOT NULL,
                `sender` VARCHAR(10) NOT NULL DEFAULT 'customer',
                `body` TEXT,
                `create_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_msg_conv` (`conversation_id`, `id`),
                CONSTRAINT `fk_msg_conv`
                    FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $admin = $this->db->table('groups')->where('name', '=', 'Admin')->first();
        $ex = $this->db->table('modules')->where('link', '=', 'chat')->first();
        if (empty($ex)){
            $this->db->insert('modules', ['name' => 'Hỗ trợ / Chat', 'link' => 'chat', 'create_at' => $now]);
        }
        $module = $this->db->table('modules')->where('link', '=', 'chat')->first();
        if (!empty($admin) && !empty($module)){
            foreach (['view', 'edit', 'delete'] as $role){
                $has = $this->db->table('permissions')
                    ->where('module_id', '=', $module['id'])->where('group_id', '=', $admin['id'])->where('role', '=', $role)->first();
                if (empty($has)){
                    $this->db->insert('permissions', ['module_id' => $module['id'], 'group_id' => $admin['id'], 'role' => $role]);
                }
            }
        }
    }

    public function down(){
        $this->run("DROP TABLE IF EXISTS `chat_messages`");
        $this->run("DROP TABLE IF EXISTS `chat_conversations`");
        $m = $this->db->table('modules')->where('link', '=', 'chat')->first();
        if (!empty($m)){
            $this->db->delete('permissions', '`module_id` = ?', [$m['id']]);
            $this->db->delete('modules', '`id` = ?', [$m['id']]);
        }
    }
};

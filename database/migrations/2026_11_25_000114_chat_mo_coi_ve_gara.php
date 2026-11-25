<?php
/**
 * HỘI THOẠI CHAT MỒ CÔI -> GÁN VỀ GARA. Khách báo 25/11/2026: "cái chat cũng
 * không chạy".
 *
 * Từ 07/10 (migration 000089) `chat_conversations` có cột `garage_id`, nhưng
 * hàm tạo hội thoại vẫn ghi insert() thô, không điền gara. Mọi hội thoại mở từ
 * ngày đẩy bản đó tới nay mang `garage_id` NULL:
 *   - admin không thấy (hộp thư lọc theo gara) — tin khách gửi nằm im;
 *   - khách ấy gửi tin nữa (cùng phiên) thì đụng khoá duy nhất `session_key`
 *     và báo lỗi — code mới vẫn đụng nếu dòng NULL còn đó.
 * Code đã sửa (ChatConversationsModel::create dùng addNew). Migration này đưa
 * các dòng đã lỡ về chỗ có người đọc:
 *   1. Khách có tài khoản web (`member_id`) -> gara của tài khoản đó.
 *   2. Còn lại (khách vãng lai, không biết gõ từ website gara nào) -> GARA TỔNG,
 *      như migration 000089 đã làm với dữ liệu cũ.
 *
 * AN TOÀN KHI CHẠY TRƯỚC HAY SAU KHI ĐẨY CODE: chỉ điền chỗ đang NULL, không
 * đổi cấu trúc. Chạy lại được: hết dòng NULL thì không làm gì.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   UPDATE chat_conversations c JOIN members m ON m.id = c.member_id
 *      SET c.garage_id = m.garage_id
 *    WHERE c.garage_id IS NULL AND m.garage_id IS NOT NULL;
 *   UPDATE chat_conversations
 *      SET garage_id = (SELECT id FROM garages WHERE is_master = 1 LIMIT 1)
 *    WHERE garage_id IS NULL;
 *
 * Khong doi cau truc CSDL.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('chat_conversations')) return;

        $tong = $this->db->table('garages')->where('is_master', '=', 1)->first();
        if (empty($tong)){
            echo "  Khong tim thay gara tong — dung lai, khong doi gi.\n";
            return;
        }

        $truoc = $this->dem();
        if ($truoc === 0){ echo "  Khong co hoi thoai chat nao thieu gara — bo qua.\n"; return; }

        if ($this->hasTable('members')){
            $this->db->query("UPDATE `chat_conversations` c JOIN `members` m ON m.`id` = c.`member_id`
                                 SET c.`garage_id` = m.`garage_id`
                               WHERE c.`garage_id` IS NULL AND m.`garage_id` IS NOT NULL");
        }
        $this->db->query("UPDATE `chat_conversations` SET `garage_id` = ? WHERE `garage_id` IS NULL",
                         [(int) $tong['id']]);

        echo "  Da gan gara cho $truoc hoi thoai chat mo coi, con " . $this->dem() . " dong thieu gara.\n";
    }

    /* Không hoàn tác: không biết dòng nào trước đây NULL, và để NULL lại là
       làm hỏng chat lần nữa. */
    public function down(){}

    private function dem(){
        $r = $this->db->query("SELECT COUNT(*) AS c FROM `chat_conversations` WHERE `garage_id` IS NULL")
                      ->fetch(\PDO::FETCH_ASSOC);
        return (int) $r['c'];
    }
};

<?php
/**
 * BƯỚC 4: KHÁCH WEB, ĐƠN HÀNG, LIÊN HỆ, ĐÁNH GIÁ THEO TỪNG GARA.
 *
 * Ba bước trước đã cho mỗi gara một tên miền, một bộ nhận diện và nội dung web
 * riêng. Nhưng NGƯỜI DÙNG và GIAO DỊCH trên web vẫn đổ chung một chỗ:
 *
 *   - tài khoản khách đăng ký ở trang gara A hiện trong danh sách của Tân Phát;
 *   - đơn hàng đặt trên web gara A về hộp đơn của Tân Phát;
 *   - thư liên hệ, đăng ký bản tin, đánh giá sản phẩm, tin nhắn chat — tất cả
 *     dùng chung.
 *
 * Đây là bước NGUY HIỂM NHẤT của cả kế hoạch, vì khác ba bước trước ở chỗ: dữ
 * liệu đang có là dữ liệu THẬT của người dùng thật, không phải nội dung biên
 * tập. Gán nhầm là đơn hàng của khách này nằm trong gara khác.
 *
 * Dữ liệu đang có gán hết về GARA TỔNG — toàn bộ phát sinh trước hôm nay đều
 * đến từ website duy nhất của Tân Phát, không có nguồn nào khác.
 *
 * BẢNG CON KHÔNG THÊM CỘT: `order_items` theo `orders`, `chat_messages` theo
 * `chat_conversations`, `member_vehicles` theo `members`. Bảng cha đã mang gara;
 * thêm cột ở con là hai nguồn sự thật cho cùng một việc.
 *
 * ON DELETE CASCADE: xoá gara thì khách web và đơn web của gara đó đi theo.
 * Chúng chỉ tồn tại trong phạm vi website của gara ấy — khác chứng từ gara
 * (RESTRICT) vốn là sổ sách phải giữ.
 *
 * Cột để NULL được: migration chạy TRƯỚC khi đẩy code, nên trong khoảng giữa
 * hai bước code cũ vẫn ghi mà chưa biết điền gara.
 */

use App\core\Migration;

return new class extends Migration {

    const BANG = [
        'members'                => 'fk_members_garage',
        'orders'                 => 'fk_orders_garage',
        'contact_messages'       => 'fk_contact_msg_garage',
        'newsletter_subscribers' => 'fk_newsletter_garage',
        'part_reviews'           => 'fk_part_reviews_garage',
        'chat_conversations'     => 'fk_chat_conv_garage',
    ];

    public function up(){
        $garaTong = $this->db->table('garages')->where('is_master', '=', 1)->first();
        if (empty($garaTong)){
            echo "  Khong tim thay gara tong — dung lai, khong doi gi.\n";
            return;
        }
        $idTong = (int) $garaTong['id'];

        foreach (self::BANG as $bang => $fk){
            if (!$this->hasTable($bang)){ echo "  Khong co bang `$bang` — bo qua.\n"; continue; }

            if (!$this->coCot($bang, 'garage_id')){
                $this->run("ALTER TABLE `$bang`
                            ADD COLUMN `garage_id` INT DEFAULT NULL,
                            ADD KEY `idx_{$bang}_garage` (`garage_id`),
                            ADD CONSTRAINT `$fk` FOREIGN KEY (`garage_id`)
                                REFERENCES `garages` (`id`) ON DELETE CASCADE");
                echo "  Da them `$bang`.`garage_id`.\n";
            }

            $this->db->query("UPDATE `$bang` SET `garage_id` = ? WHERE `garage_id` IS NULL", [$idTong]);
            $con = $this->db->query("SELECT COUNT(*) AS c FROM `$bang` WHERE `garage_id` IS NULL")
                            ->fetch(\PDO::FETCH_ASSOC);
            echo "  `$bang`: da gan ve gara tong, con " . (int) $con['c'] . " dong chua co gara.\n";
        }
    }

    public function down(){
        foreach (self::BANG as $bang => $fk){
            if (!$this->hasTable($bang) || !$this->coCot($bang, 'garage_id')) continue;
            $this->run("ALTER TABLE `$bang` DROP FOREIGN KEY `$fk`");
            $this->run("ALTER TABLE `$bang` DROP COLUMN `garage_id`");
            echo "  Da go `$bang`.`garage_id`.\n";
        }
    }

    private function coCot($bang, $cot){
        try {
            $this->db->query("SELECT `$cot` FROM `$bang` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};

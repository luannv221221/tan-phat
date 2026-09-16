<?php
/**
 * PHIẾU BẢO TRÌ bên cạnh phiếu bảo hành.
 *
 * Trước đây chỉ có một loại phiếu (bảo hành), và "Nhắc bảo trì" suy ra từ
 * ngày HOÀN TẤT PHIẾU BẢO HÀNH + 6 tháng. Tức là xe chưa hỏng lần nào thì
 * không bao giờ được nhắc bảo trì, còn xe vừa sửa bảo hành lại bị nhắc như
 * vừa bảo dưỡng. Hai việc khác bản chất, không suy ra nhau được.
 *
 * - warranty_requests.loai: 'bao_hanh' (MẶC ĐỊNH — mọi phiếu cũ giữ đúng
 *   nghĩa cũ) | 'bao_tri'. Dùng CHUNG bảng: cùng khách, cùng xe / thiết bị,
 *   cùng trạng thái, cùng biên bản giao nhận — tách bảng là nhân đôi mọi thứ.
 * - Số phiếu hai dãy riêng: BH-xxxxxx / BT-xxxxxx (cột request_no vốn UNIQUE).
 * - maintenance_interval_km (mặc định 5000): nhắc theo tháng HOẶC km, cái
 *   nào tới trước.
 * - Đổi tên hai màn trên menu cho khớp (menu đọc tên từ bảng modules).
 */

use App\core\Migration;

return new class extends Migration {

    const TEN_MOI = ['warranty' => 'Phiếu bảo hành / bảo trì', 'lich-bao-hanh' => 'Lịch bảo hành / bảo trì'];
    const TEN_CU  = ['warranty' => 'Phiếu bảo hành',            'lich-bao-hanh' => 'Lịch bảo hành'];

    public function up(){
        $this->run("ALTER TABLE `warranty_requests`
                    ADD COLUMN `loai` VARCHAR(10) NOT NULL DEFAULT 'bao_hanh' AFTER `request_no`,
                    ADD KEY `idx_wr_loai_status` (`loai`, `status`)");

        $ex = $this->db->table('site_settings')->where('skey', '=', 'maintenance_interval_km')->first();
        if (empty($ex)){
            $this->db->insert('site_settings', ['skey' => 'maintenance_interval_km', 'svalue' => '5000']);
        }

        // Tên là hằng số viết tay ở trên, không có dấu nháy — ghép thẳng được.
        foreach (self::TEN_MOI as $link => $ten){
            $this->run("UPDATE `modules` SET `name` = '$ten' WHERE `link` = '$link'");
        }
    }

    public function down(){
        $this->run("ALTER TABLE `warranty_requests` DROP KEY `idx_wr_loai_status`, DROP COLUMN `loai`");
        $this->db->delete('site_settings', '`skey` = ?', ['maintenance_interval_km']);
        foreach (self::TEN_CU as $link => $ten){
            $this->run("UPDATE `modules` SET `name` = '$ten' WHERE `link` = '$link'");
        }
    }
};

<?php
/**
 * BIỂN SỐ XE + SỐ KM trên phiếu bảo hành.
 *
 * VÌ SAO BẢO HÀNH CẦN CÁI NÀY CÒN HƠN CẢ BÁO GIÁ
 * Nhìn các cột đang có của `warranty_requests` là rõ đây không phải bán hàng
 * qua quầy: `technician`, `diagnosis`, `issue`, `received_date`,
 * `appointment_date` — xe vào xưởng, thợ chẩn đoán, hẹn ngày trả. Bảo hành
 * một cái đĩa phanh mà không biết nó lắp trên xe nào thì gần như vô nghĩa.
 *
 * Và khi khách quay lại, BIỂN SỐ mới là thứ người ta tra. Không ai nhớ số
 * serial của cái phụ tùng mình đã thay sáu tháng trước.
 *
 * `serial_no` đang có KHÔNG thay được: đó là serial của PHỤ TÙNG, không phải
 * của XE. Hai thứ khác nhau hoàn toàn.
 *
 * CHƯA NỐI ĐƯỢC VỚI HOÁ ĐƠN
 * `warranty_requests` chỉ có `part_id` trỏ sang `parts`, không có đường nào
 * lần tới hoá đơn đã bán cái phụ tùng đó. Nên kể cả khi hoá đơn đã ghi biển
 * số, phiếu bảo hành cũng không mượn lại được — phải có cột riêng.
 *
 * Cùng cách làm với báo giá / hoá đơn (migration 000066): giữ nguyên văn để
 * in, kèm bản chuẩn hoá có chỉ mục để tra. Ba cột đều để trống được — bảo
 * hành thiết bị cầm tay thì không có xe nào cả.
 */

use App\core\Migration;

return new class extends Migration {

    protected $bang = 'warranty_requests';

    public function up(){
        if (!$this->hasTable($this->bang)) return;

        if (!$this->hasColumn($this->bang, 'bien_so')){
            $this->run("ALTER TABLE `{$this->bang}` ADD COLUMN `bien_so` VARCHAR(20) DEFAULT NULL");
            $this->run("ALTER TABLE `{$this->bang}` ADD COLUMN `bien_so_chuan` VARCHAR(20) DEFAULT NULL");
            $this->run("ALTER TABLE `{$this->bang}` ADD COLUMN `so_km` INT DEFAULT NULL");
            // Chỉ mục trên cột CHUẨN HOÁ — tra cứu bao giờ cũng so bản chuẩn hoá
            $this->run("ALTER TABLE `{$this->bang}` ADD KEY `idx_wr_bien_so` (`bien_so_chuan`)");
            echo "  Da them bien_so / bien_so_chuan / so_km vao `{$this->bang}`.\n";
        }
    }

    /** SHOW COLUMNS rồi lọc bằng PHP — `SHOW COLUMNS ... LIKE ?` bị lỗi 1064 */
    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }

    public function down(){
        if (!$this->hasTable($this->bang) || !$this->hasColumn($this->bang, 'bien_so')) return;
        try { $this->run("ALTER TABLE `{$this->bang}` DROP KEY `idx_wr_bien_so`"); } catch (\Throwable $e){}
        $this->run("ALTER TABLE `{$this->bang}` DROP COLUMN `so_km`");
        $this->run("ALTER TABLE `{$this->bang}` DROP COLUMN `bien_so_chuan`");
        $this->run("ALTER TABLE `{$this->bang}` DROP COLUMN `bien_so`");
    }
};

<?php
/**
 * LINK THANH TOÁN CHO HOÁ ĐƠN — anh yêu cầu 18/11/2026: "cho anh cái nút gửi
 * link qrcode luôn không phải vào in".
 *
 * Gara bấm "Gửi" ở danh sách hoá đơn -> gửi cho khách một link
 * /thanh-toan/<mã>. Khách mở (không cần đăng nhập) là thấy mã QR chuyển khoản,
 * số tài khoản, số tiền, nội dung — xem app/controllers/Thanhtoan.php.
 *
 * VÌ SAO CẦN CỘT RIÊNG mà không dùng id: id tăng dần — đổi /thanh-toan/112 thành
 * /113 là xem được hoá đơn của người khác. Mã ở đây là 32 ký tự hex ngẫu nhiên
 * (random_bytes, 128 bit), không đoán được. Cùng cách với link tải trực tiếp
 * (`downloads`.`token`, migration 000105).
 *
 * NULL = hoá đơn chưa từng gửi link. Mã chỉ sinh khi gara bấm "Gửi" lần đầu
 * (SalesInvoicesModel::maThanhToan) và giữ nguyên về sau, nên link đã gửi không
 * chết. UNIQUE để hai hoá đơn không bao giờ chung một link.
 *
 * AN TOÀN KHI CHẠY TRƯỚC KHI ĐẨY CODE: cột mới, cho phép NULL — code cũ không
 * biết tới nó và vẫn chạy y như trước.
 *
 * Chạy tay trong phpMyAdmin (nếu không chạy migrate.php):
 *
 *   ALTER TABLE `sales_invoices`
 *     ADD COLUMN `pay_token` VARCHAR(32) NULL DEFAULT NULL,
 *     ADD UNIQUE KEY `uq_si_pay_token` (`pay_token`);
 *
 * CHẠY LẠI ĐƯỢC: có cột rồi thì bỏ qua.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        if (!$this->hasTable('sales_invoices')) return;

        if (!$this->hasColumn('sales_invoices', 'pay_token')){
            $this->run("ALTER TABLE `sales_invoices`
                          ADD COLUMN `pay_token` VARCHAR(32) NULL DEFAULT NULL,
                          ADD UNIQUE KEY `uq_si_pay_token` (`pay_token`)");
            echo "  Da them `sales_invoices`.`pay_token` (UNIQUE).\n";
        } else {
            echo "  `sales_invoices`.`pay_token` da co — bo qua.\n";
        }
    }

    public function down(){
        if ($this->hasColumn('sales_invoices', 'pay_token')){
            $this->run("ALTER TABLE `sales_invoices` DROP INDEX `uq_si_pay_token`, DROP COLUMN `pay_token`");
        }
    }

    protected function hasColumn($bang, $cot){
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM `$bang`")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e){ return false; }
        foreach ($rows as $r){
            if (isset($r['Field']) && $r['Field'] === $cot) return true;
        }
        return false;
    }
};

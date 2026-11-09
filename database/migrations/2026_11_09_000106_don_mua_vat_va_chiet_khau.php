<?php
/**
 * ĐƠN MUA CÓ VAT VÀ CHIẾT KHẤU.
 *
 * Báo giá và hoá đơn bán đã có đủ bộ này từ lâu (`vat_rate`, `subtotal`,
 * `tax_amount` ở đầu phiếu, `discount_percent` trên từng dòng). Đơn mua thì
 * chưa — mà gara mua hàng có hoá đơn đỏ thì tờ hoá đơn NCC dán ngay bên trên
 * đơn mua luôn có hai con số đó. Không khai được là mỗi lần đối chiếu phải
 * nhẩm tay, và đơn mua ghi một số còn hoá đơn NCC ghi một số khác.
 *
 * DÙNG ĐÚNG TÊN CỘT VÀ ĐÚNG KIỂU của `quotations` / `sales_invoices`. Đặt tên
 * khác (vd `thue_suat`) thì mỗi lần đọc code phải nhớ bên nào gọi là gì.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VAT KHÔNG ĐƯỢC CHẢY VÀO GIÁ VỐN — chỗ dễ làm sai nhất của cả việc này.
 * ──────────────────────────────────────────────────────────────────────────
 * Thuế GTGT đầu vào là khoản được khấu trừ, KHÔNG phải giá trị hàng tồn. Nên
 * lúc đơn mua sinh ra phiếu nhập kho, phiếu nhập vẫn lấy số CHƯA THUẾ. Cộng
 * VAT vào đó là thổi giá vốn lên 8–10%, kéo theo lãi gộp của mọi hoá đơn bán
 * sau này sai — mà không có dòng nào báo lỗi.
 *
 * CHIẾT KHẤU thì NGƯỢC LẠI: giảm giá 10% là tiền thật trả cho hàng giảm đi,
 * nên nó PHẢI đi vào giá vốn. Phiếu nhập lấy đơn giá đã trừ chiết khấu.
 *
 * ĐƠN CŨ CHẠY Y NHƯ CŨ: vat_rate và discount_percent đều mặc định 0, nên
 * thành tiền không đổi một đồng nào. `subtotal` chép lại từ `total_amount`
 * đang có — đơn cũ không có thuế nên hai số đó bằng nhau.
 *
 * CHẠY LẠI ĐƯỢC: cột nào có rồi thì bỏ qua cột đó.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('purchase_orders') || !$this->hasTable('purchase_order_items')){
            echo "  (chua co bang don mua, bo qua)\n"; return;
        }

        // ---------------------------------------------- Chiết khấu từng dòng
        if (!$this->hasColumn('purchase_order_items', 'discount_percent')){
            /* Đặt NGAY SAU `unit_price` và TRƯỚC `amount`, đúng thứ tự của
               `quotation_items`: đọc ngang một dòng là ra đúng mạch tính
               tiền — số lượng, đơn giá, chiết khấu, thành tiền. */
            $this->run("ALTER TABLE `purchase_order_items`
                        ADD COLUMN `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `unit_price`");
            echo "  Da them `purchase_order_items`.`discount_percent`.\n";
        }

        // ------------------------------------------------ VAT ở đầu đơn mua
        if (!$this->hasColumn('purchase_orders', 'vat_rate')){
            $this->run("ALTER TABLE `purchase_orders`
                        ADD COLUMN `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `note`");
            echo "  Da them `purchase_orders`.`vat_rate`.\n";
        }
        if (!$this->hasColumn('purchase_orders', 'subtotal')){
            $this->run("ALTER TABLE `purchase_orders`
                        ADD COLUMN `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `vat_rate`");
            echo "  Da them `purchase_orders`.`subtotal`.\n";
        }
        if (!$this->hasColumn('purchase_orders', 'tax_amount')){
            $this->run("ALTER TABLE `purchase_orders`
                        ADD COLUMN `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `subtotal`");
            echo "  Da them `purchase_orders`.`tax_amount`.\n";
        }

        /* ĐƠN CŨ: chưa có thuế bao giờ, nên tiền hàng chưa thuế CHÍNH LÀ tổng
           đang lưu. Không chép thì màn hình hiện "Cộng chưa thuế: 0 đ" bên
           cạnh "Tổng cộng: 12.000.000 đ" — nhìn như dữ liệu hỏng.

           Chỉ chép khi subtotal đang là 0: chạy lại migration lần hai không
           được đè lên số đã tính đúng. */
        $n = $this->db->query(
            'UPDATE `purchase_orders` SET `subtotal` = `total_amount`
              WHERE `subtotal` = 0 AND `total_amount` <> 0')->rowCount();
        if ($n > 0) echo "  Da chep tien hang chua thue cho $n don mua cu.\n";
    }

    /**
     * Quay lui được: bỏ bốn cột là về đúng hình dạng của 000103.
     *
     * MẤT SỐ THUẾ VÀ CHIẾT KHẤU ĐÃ KHAI — không tránh được, chúng chỉ nằm ở
     * bốn cột này. `total_amount` giữ nguyên nên màn danh sách vẫn đúng con
     * số tổng; chỉ là không còn tách ra được phần nào là thuế.
     */
    public function down(){
        foreach ([['purchase_order_items', 'discount_percent'],
                  ['purchase_orders', 'tax_amount'],
                  ['purchase_orders', 'subtotal'],
                  ['purchase_orders', 'vat_rate']] as $c){
            list($bang, $cot) = $c;
            if ($this->hasColumn($bang, $cot)){
                $this->run("ALTER TABLE `$bang` DROP COLUMN `$cot`");
                echo "  Da bo `$bang`.`$cot`.\n";
            }
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

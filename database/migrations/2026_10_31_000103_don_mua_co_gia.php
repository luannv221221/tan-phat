<?php
/**
 * ĐƠN MUA CÓ GIÁ — đảo lại một quyết định của chính 29/10/2026.
 *
 * Tài liệu khách gửi 29/10 ghi "không cần giá nhập", nên `purchase_order_items`
 * dựng ra chỉ có số lượng (migration 000102, quyết định số 2). Ngày 31/10 khách
 * mở màn hình lên và hỏi lại: "đơn mua sao không có giá nhỉ".
 *
 * Khách hỏi đúng. Một đơn mua không có tiền thì:
 *   - không ai duyệt được là đơn này mua hết bao nhiêu
 *   - không đối chiếu được với chính tờ hoá đơn NCC dán ngay bên trên nó
 *   - công nợ nhà cung cấp chỉ biết sau khi hàng đã về kho, muộn hơn một bước
 *
 * GIÁ ĐỂ TRỐNG VẪN LƯU ĐƯỢC (mặc định 0) — nhà cung cấp chưa báo giá lúc lập
 * đơn là chuyện thường. Lúc chuyển sang phiếu nhập kho:
 *
 *     đơn giá > 0   ->  lấy ĐÚNG giá đã khai trên đơn mua
 *     đơn giá = 0   ->  rơi về GIÁ NHẬP GẦN NHẤT, y như trước
 *
 * Nên mọi đơn lập trước hôm nay (đều có unit_price = 0) chạy không khác gì cũ.
 * Đây là lý do cột để DEFAULT 0 chứ không NULL: một đường rơi, không hai.
 *
 * `purchase_orders`.`total_amount` LÀ SỐ CHÉP LẠI, không phải nguồn sự thật.
 * Nguồn là tổng `amount` của các dòng; controller ghi lại cột này sau mỗi lần
 * lưu để màn danh sách khỏi cộng dồn từng đơn một. Hai số lệch nhau thì tin
 * dòng hàng — giống hệt `goods_receipts`.`total_amount` đang làm.
 *
 * CHẠY LẠI ĐƯỢC: cột nào có rồi thì bỏ qua cột đó.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('purchase_order_items')){
            echo "  (chua co bang `purchase_order_items`, bo qua)\n"; return;
        }

        if (!$this->hasColumn('purchase_order_items', 'unit_price')){
            $this->run("ALTER TABLE `purchase_order_items`
                        ADD COLUMN `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `quantity`");
            echo "  Da them `purchase_order_items`.`unit_price`.\n";
        }

        /* `amount` ghi sẵn thay vì nhân lại mỗi lần đọc: cùng khuôn với
           `goods_receipt_items`.`amount`, và để câu SUM() ở màn danh sách
           không phải nhân hai cột rồi làm tròn khác chỗ khác. */
        if (!$this->hasColumn('purchase_order_items', 'amount')){
            $this->run("ALTER TABLE `purchase_order_items`
                        ADD COLUMN `amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `unit_price`");
            echo "  Da them `purchase_order_items`.`amount`.\n";
        }

        if (!$this->hasColumn('purchase_orders', 'total_amount')){
            $this->run("ALTER TABLE `purchase_orders`
                        ADD COLUMN `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `note`");
            echo "  Da them `purchase_orders`.`total_amount`.\n";
        }

        /* Đơn cũ: dòng hàng đều unit_price = 0 nên tổng cũng là 0 — đúng với
           thực tế "đơn này chưa khai giá", không phải "đơn này mua 0 đồng".
           Màn hình phân biệt hai cái đó bằng dấu "—". */
        $r = $this->db->firstRaw("SELECT COUNT(*) AS c FROM `purchase_orders`");
        $n = (int) (isset($r['c']) ? $r['c'] : 0);
        if ($n > 0) echo "  $n don mua cu giu nguyen gia 0 — mo ra khai gia luc nao cung duoc.\n";
    }

    /**
     * Quay lui được: bỏ ba cột là về đúng hình dạng của 000102.
     *
     * MẤT DỮ LIỆU GIÁ ĐÃ KHAI — không tránh được, giá chỉ nằm ở ba cột này.
     * Phiếu nhập kho đã sinh ra thì không ảnh hưởng: giá đã chép sang
     * `goods_receipt_items`.`unit_cost` rồi, nằm bên đó.
     */
    public function down(){
        foreach ([['purchase_order_items', 'amount'],
                  ['purchase_order_items', 'unit_price'],
                  ['purchase_orders', 'total_amount']] as $c){
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

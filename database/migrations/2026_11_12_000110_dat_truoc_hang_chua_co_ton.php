<?php
/**
 * ĐẶT TRƯỚC: KHÁCH ĐẶT ĐƯỢC CẢ PHẦN GARA CHƯA CÓ TỒN (anh chốt 12/11/2026).
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VÌ SAO
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Gian hàng của gara bày hàng của kho tổng, nhưng lúc chốt đơn thì đo tồn
 * CỦA CHÍNH GARA (chốt 04/08/2026). Gara mẫu Sài Gòn không có tồn mặt hàng
 * kho tổng nào, nên MỌI đơn trên gian hàng đó đều bị từ chối — anh đặt thử
 * 12/11/2026 và không đặt được. Bày hàng rồi báo hết là mời khách vào rồi
 * đuổi ra.
 *
 * Nay đơn vẫn vào, phần gara chưa có thì ghi là ĐẶT TRƯỚC để gara tự lấy từ
 * kho tổng về.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  VÌ SAO LÀ MỘT CỘT SỐ LƯỢNG, KHÔNG PHẢI MỘT Ô TÍCH "ĐƠN ĐẶT TRƯỚC"
 * ──────────────────────────────────────────────────────────────────────────
 *
 * Một đơn có thể vừa có hàng sẵn vừa có hàng phải đợi, và ngay trong MỘT DÒNG
 * cũng vậy: khách đặt 5 cái mà kho còn 2 thì 2 cái giao ngay, 3 cái đợi. Cờ
 * bật/tắt ở đầu đơn không nói được điều đó, mà đây đúng là điều người soạn
 * hàng cần biết để gọi kho tổng lấy mấy cái.
 *
 * ĐỂ Ở `order_items` CHỨ KHÔNG Ở `orders`: số này gắn với từng mặt hàng.
 *
 * MẶC ĐỊNH 0 nên mọi đơn cũ tự khai là "có hàng đủ" — đúng, vì trước hôm nay
 * đơn nào thiếu hàng là bị chặn, không có đơn nào lọt vào CSDL ở trạng thái
 * thiếu cả.
 *
 * KHÔNG ĐỤNG `stock_reservations`. Phần đặt trước KHÔNG được giữ tồn: giữ cái
 * không có là đẩy `sellableByPart()` xuống số âm, rồi khách sau vào thấy hết
 * hàng trong khi kho vẫn còn. Giữ đúng phần có thật, phần thiếu ghi vào cột
 * này — xem Cart::placeOrder().
 *
 * CHẠY LẠI ĐƯỢC: cột có rồi thì bỏ qua.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){

        if (!$this->hasTable('order_items')) return;

        if (!$this->hasColumn('order_items', 'preorder_qty')){
            /* Đặt NGAY SAU `quantity`: đọc ngang một dòng là ra "đặt 5, trong
               đó 3 phải đợi" — hai con số đi với nhau thì phải đứng cạnh nhau. */
            $this->run("ALTER TABLE `order_items`
                        ADD COLUMN `preorder_qty` DECIMAL(15,3) NOT NULL DEFAULT 0 AFTER `quantity`");
            echo "  Da them `order_items`.`preorder_qty`.\n";
        }
    }

    public function down(){

        if ($this->hasColumn('order_items', 'preorder_qty')){
            $this->run("ALTER TABLE `order_items` DROP COLUMN `preorder_qty`");
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

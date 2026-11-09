<?php

use App\core\Model;

/**
 * DÒNG HÀNG CỦA ĐƠN MUA — xem ghi chú ở PurchaseOrdersModel.
 *
 * KHÔNG có cột `garage_id`: dòng luôn thuộc về một đơn mua, mà đơn mua đã theo
 * gara. Thêm cột nữa là hai nguồn sự thật cho cùng một việc, và có ngày lệch
 * nhau. Mọi truy vấn vào đây đều phải đi qua `po_id` của một đơn ĐÃ LẤY ĐÚNG
 * GARA (PurchaseOrdersModel::getDetail), chứ đừng lọc thẳng bảng này.
 */
class PurchaseOrderItemsModel extends Model {

    protected $_table   = 'purchase_order_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Dòng hàng của một đơn, kèm tên + đơn vị tính của mặt hàng */
    public function getByPo($poId){
        return $this->table($this->_table)
            ->select('`purchase_order_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `part_units`.`name` AS unit_name')
            ->joinOn('parts', 'purchase_order_items.part_id', 'parts.id')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id')
            ->where('purchase_order_items.po_id', '=', (int) $poId)
            ->orderBy('purchase_order_items.id', 'ASC')->get();
    }

    /**
     * Ghi lại toàn bộ dòng hàng của một đơn.
     *
     * XOÁ RỒI GHI LẠI, không cố so khớp từng dòng — giống syncForQuotation.
     * Nhưng ở đây việc xoá KÉO THEO SERIAL (`purchase_serials`.`poi_id` có
     * CASCADE), nên controller phải ghi serial LẠI SAU khi gọi hàm này. Nếu
     * không thì sửa số lượng một dòng là bay hết serial đã nhập.
     *
     * @return array [poi_id => dòng] để controller gắn serial vào đúng dòng
     */
    public function syncForPo($poId, array $rows){
        $poId = (int) $poId;
        $this->delete($this->_table, '`po_id` = ?', [$poId]);

        $ra = [];
        foreach ($rows as $r){
            $partId = isset($r['part_id']) ? (int) $r['part_id'] : 0;
            $qty    = isset($r['quantity']) ? (float) $r['quantity'] : 0;
            if ($partId <= 0 || $qty <= 0) continue;   // dòng trống thì bỏ

            /* ÂM LÀ 0. Đơn giá âm không có nghĩa gì ở đây, mà nếu lọt xuống thì
               tổng đơn bị trừ đi — một dòng âm đủ lớn là đơn triệu đồng thành
               đơn vài nghìn, không ai nhìn ra trên màn danh sách. */
            $gia = isset($r['unit_price']) ? (float) $r['unit_price'] : 0;
            if ($gia < 0) $gia = 0;

            /* CHIẾT KHẤU kẹp trong 0–100. Âm thì thành tiền phình ra, trên
               100 thì thành tiền âm — cả hai đều làm tổng đơn sai mà màn danh
               sách không có dấu hiệu gì. Kẹp ở đây, nơi duy nhất ghi dòng
               hàng, thay vì tin vào ô nhập trên form. */
            $ck = isset($r['discount_percent']) ? (float) $r['discount_percent'] : 0;
            if ($ck < 0)   $ck = 0;
            if ($ck > 100) $ck = 100;

            $tt  = round($gia * $qty * (1 - $ck / 100), 2);

            $this->insert($this->_table, [
                'po_id'            => $poId,
                'part_id'          => $partId,
                'quantity'         => $qty,
                'unit_price'       => $gia,
                'discount_percent' => $ck,
                'amount'           => $tt,
                'note'             => isset($r['note']) && $r['note'] !== '' ? mb_substr($r['note'], 0, 255) : null,
            ]);
            $ra[(int) $this->lastId()] = ['part_id' => $partId, 'quantity' => $qty,
                                          'unit_price' => $gia, 'discount_percent' => $ck,
                                          'amount' => $tt];
        }
        return $ra;
    }

    /**
     * TỔNG TIỀN CỦA MỘT ĐƠN — cộng từ dòng hàng, không đọc
     * `purchase_orders`.`total_amount`.
     *
     * Cột total_amount bên đơn chỉ là số chép lại cho màn danh sách khỏi cộng
     * dồn từng đơn. Nguồn sự thật là ở đây, và controller gọi hàm này để
     * ghi lại cột kia sau mỗi lần lưu.
     */
    public function tongTheoPo($poId){
        $r = $this->firstRaw('SELECT COALESCE(SUM(`amount`), 0) AS t FROM `purchase_order_items` WHERE `po_id` = ?',
                             [(int) $poId]);
        return (float) (isset($r['t']) ? $r['t'] : 0);
    }

    public function demTheoPo($poId){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `purchase_order_items` WHERE `po_id` = ?',
                             [(int) $poId]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }
}

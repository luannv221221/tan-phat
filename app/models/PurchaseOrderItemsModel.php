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

            $this->insert($this->_table, [
                'po_id'    => $poId,
                'part_id'  => $partId,
                'quantity' => $qty,
                'note'     => isset($r['note']) && $r['note'] !== '' ? mb_substr($r['note'], 0, 255) : null,
            ]);
            $ra[(int) $this->lastId()] = ['part_id' => $partId, 'quantity' => $qty];
        }
        return $ra;
    }

    public function demTheoPo($poId){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `purchase_order_items` WHERE `po_id` = ?',
                             [(int) $poId]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }
}

<?php

use App\core\Model;

/** STOREFRONT — Dòng hàng của đơn hàng (lưu snapshot tên/giá). */
class OrderItemsModel extends Model {

    protected $_table   = 'order_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByOrder($orderId){
        return $this->table($this->_table)
            ->where('order_id', '=', (int) $orderId)
            ->orderBy('id', 'ASC')->get();
    }

    /**
     * Lưu dòng hàng từ giỏ (transaction).
     * @param array $rows mỗi phần tử: ['part'=>partRow, 'qty'=>, 'price'=>, 'amount'=>]
     * @return float tổng tiền
     */
    public function syncForOrder($orderId, array $rows){
        $orderId = (int) $orderId;
        return $this->transaction(function($db) use ($orderId, $rows){
            $db->delete('order_items', '`order_id` = ?', [$orderId]);
            $total = 0.0;
            foreach ($rows as $r){
                $p = $r['part'];
                $amount = (float) $r['amount'];
                $db->insert('order_items', [
                    'order_id'   => $orderId,
                    'part_id'    => (int) $p['id'],
                    'part_name'  => $p['name'],
                    'part_code'  => $p['code'],
                    'quantity'   => (int) $r['qty'],
                    'unit_price' => (float) $r['price'],
                    'amount'     => $amount,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }
}

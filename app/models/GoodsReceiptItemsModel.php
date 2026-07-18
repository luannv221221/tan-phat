<?php

use App\core\Model;

/**
 * KHO — Dòng hàng của phiếu nhập kho.
 */
class GoodsReceiptItemsModel extends Model {

    protected $_table   = 'goods_receipt_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Dòng hàng kèm mã/tên phụ tùng + đơn vị */
    public function getByReceipt($receiptId){
        return $this->table($this->_table)
            ->select('`goods_receipt_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `product_units`.`name` AS unit_name')
            ->joinOn('parts', 'goods_receipt_items.part_id', 'parts.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id')
            ->where('goods_receipt_items.receipt_id', '=', (int) $receiptId)
            ->orderBy('goods_receipt_items.id', 'ASC')->get();
    }

    /**
     * Thay toàn bộ dòng hàng (transaction).
     * @param array $lines mỗi phần tử: [part_id, quantity, unit_cost, amount, location, note]
     * @return float tổng tiền
     */
    public function syncForReceipt($receiptId, array $lines){
        $receiptId = (int) $receiptId;
        return $this->transaction(function($db) use ($receiptId, $lines){
            $db->delete('goods_receipt_items', '`receipt_id` = ?', [$receiptId]);
            $total = 0.0;
            foreach ($lines as $ln){
                $partId = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
                $qty    = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
                $cost   = isset($ln['unit_cost']) ? (float) $ln['unit_cost'] : 0;
                if ($partId <= 0 || $qty <= 0) continue;
                $amount = round($qty * $cost, 2);
                $db->insert('goods_receipt_items', [
                    'receipt_id' => $receiptId,
                    'part_id'    => $partId,
                    'quantity'   => $qty,
                    'unit_cost'  => $cost,
                    'amount'     => $amount,
                    'location'   => !empty($ln['location']) ? $ln['location'] : null,
                    'note'       => !empty($ln['note']) ? $ln['note'] : null,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }
}

<?php

use App\core\Model;

/**
 * BÁN HÀNG — Dòng hàng của báo giá.
 */
class QuotationItemsModel extends Model {

    protected $_table   = 'quotation_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByQuotation($quotationId){
        return $this->table($this->_table)
            ->select('`quotation_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `product_units`.`name` AS unit_name')
            ->joinOn('parts', 'quotation_items.part_id', 'parts.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id')
            ->where('quotation_items.quotation_id', '=', (int) $quotationId)
            ->orderBy('quotation_items.id', 'ASC')->get();
    }

    /**
     * Thay toàn bộ dòng (transaction). @param array $lines [part_id, quantity, unit_price, note]
     * @return float tổng tiền chưa thuế
     */
    public function syncForQuotation($quotationId, array $lines){
        $quotationId = (int) $quotationId;
        return $this->transaction(function($db) use ($quotationId, $lines){
            $db->delete('quotation_items', '`quotation_id` = ?', [$quotationId]);
            $total = 0.0;
            foreach ($lines as $ln){
                $partId = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
                $qty    = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
                $price  = isset($ln['unit_price']) ? (float) $ln['unit_price'] : 0;
                if ($partId <= 0 || $qty <= 0) continue;
                $amount = round($qty * $price, 2);
                $db->insert('quotation_items', [
                    'quotation_id' => $quotationId,
                    'part_id'      => $partId,
                    'quantity'     => $qty,
                    'unit_price'   => $price,
                    'amount'       => $amount,
                    'note'         => !empty($ln['note']) ? $ln['note'] : null,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }
}

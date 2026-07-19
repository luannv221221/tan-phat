<?php

use App\core\Model;

/**
 * BÁN HÀNG — Dòng hàng của hoá đơn bán.
 * unit_cost/cost_amount (giá vốn) điền khi GHI SỔ theo bình quân gia quyền.
 */
class SalesInvoiceItemsModel extends Model {

    protected $_table   = 'sales_invoice_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByInvoice($invoiceId){
        return $this->table($this->_table)
            ->select('`sales_invoice_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `product_units`.`name` AS unit_name')
            ->joinOn('parts', 'sales_invoice_items.part_id', 'parts.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id')
            ->where('sales_invoice_items.invoice_id', '=', (int) $invoiceId)
            ->orderBy('sales_invoice_items.id', 'ASC')->get();
    }

    /**
     * Thay toàn bộ dòng (transaction). @param array $lines [part_id, quantity, unit_price, note]
     * @return float tổng tiền chưa thuế
     */
    public function syncForInvoice($invoiceId, array $lines){
        $invoiceId = (int) $invoiceId;
        return $this->transaction(function($db) use ($invoiceId, $lines){
            $db->delete('sales_invoice_items', '`invoice_id` = ?', [$invoiceId]);
            $total = 0.0;
            foreach ($lines as $ln){
                $partId = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
                $qty    = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
                $price  = isset($ln['unit_price']) ? (float) $ln['unit_price'] : 0;
                if ($partId <= 0 || $qty <= 0) continue;
                $disc   = isset($ln['discount_percent']) ? (float) $ln['discount_percent'] : 0;
                if ($disc < 0) $disc = 0; if ($disc > 100) $disc = 100;
                $amount = round($qty * $price * (1 - $disc / 100), 2);
                $db->insert('sales_invoice_items', [
                    'invoice_id'       => $invoiceId,
                    'part_id'          => $partId,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'discount_percent' => $disc,
                    'amount'           => $amount,
                    'unit_cost'        => 0,
                    'cost_amount'      => 0,
                    'note'             => !empty($ln['note']) ? $ln['note'] : null,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }

    public function setCost($itemId, $unitCost, $costAmount){
        return $this->update('sales_invoice_items',
            ['unit_cost' => (float) $unitCost, 'cost_amount' => (float) $costAmount],
            '`id` = ?', [(int) $itemId]);
    }
}

<?php

use App\core\Model;

/**
 * KHO-2 — Dòng kiểm kê. book_qty/diff/unit_cost/diff_value điền khi chốt (ghi sổ).
 */
class StockTakeItemsModel extends Model {

    protected $_table   = 'stock_take_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByTake($takeId){
        return $this->table($this->_table)
            ->select('`stock_take_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `product_units`.`name` AS unit_name')
            ->joinOn('parts', 'stock_take_items.part_id', 'parts.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id')
            ->where('stock_take_items.take_id', '=', (int) $takeId)
            ->orderBy('stock_take_items.id', 'ASC')->get();
    }

    /** @param array $lines [part_id, actual_qty, note] */
    public function syncForTake($takeId, array $lines){
        $takeId = (int) $takeId;
        return $this->transaction(function($db) use ($takeId, $lines){
            $db->delete('stock_take_items', '`take_id` = ?', [$takeId]);
            foreach ($lines as $ln){
                $partId = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
                if ($partId <= 0) continue;
                $db->insert('stock_take_items', [
                    'take_id'    => $takeId,
                    'part_id'    => $partId,
                    'book_qty'   => 0,
                    'actual_qty' => isset($ln['actual_qty']) ? (float) $ln['actual_qty'] : 0,
                    'diff_qty'   => 0,
                    'unit_cost'  => 0,
                    'diff_value' => 0,
                    'note'       => !empty($ln['note']) ? $ln['note'] : null,
                ]);
            }
        });
    }

    public function setResult($itemId, $bookQty, $diffQty, $unitCost, $diffValue){
        return $this->update('stock_take_items', [
            'book_qty'   => (float) $bookQty,
            'diff_qty'   => (float) $diffQty,
            'unit_cost'  => (float) $unitCost,
            'diff_value' => (float) $diffValue,
        ], '`id` = ?', [(int) $itemId]);
    }
}

<?php

use App\core\Model;

/**
 * KHO-2 — Dòng hàng phiếu điều chuyển kho. unit_cost/amount điền khi ghi sổ.
 */
class WarehouseTransferItemsModel extends Model {

    protected $_table   = 'warehouse_transfer_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByTransfer($transferId){
        return $this->table($this->_table)
            ->select('`warehouse_transfer_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `product_units`.`name` AS unit_name')
            ->joinOn('parts', 'warehouse_transfer_items.part_id', 'parts.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id')
            ->where('warehouse_transfer_items.transfer_id', '=', (int) $transferId)
            ->orderBy('warehouse_transfer_items.id', 'ASC')->get();
    }

    /** @param array $lines [part_id, quantity, note] */
    public function syncForTransfer($transferId, array $lines){
        $transferId = (int) $transferId;
        return $this->transaction(function($db) use ($transferId, $lines){
            $db->delete('warehouse_transfer_items', '`transfer_id` = ?', [$transferId]);
            foreach ($lines as $ln){
                $partId = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
                $qty    = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
                if ($partId <= 0 || $qty <= 0) continue;
                $db->insert('warehouse_transfer_items', [
                    'transfer_id' => $transferId,
                    'part_id'     => $partId,
                    'quantity'    => $qty,
                    'unit_cost'   => 0,
                    'amount'      => 0,
                    'note'        => !empty($ln['note']) ? $ln['note'] : null,
                ]);
            }
        });
    }

    public function setCost($itemId, $unitCost, $amount){
        return $this->update('warehouse_transfer_items',
            ['unit_cost' => (float) $unitCost, 'amount' => (float) $amount],
            '`id` = ?', [(int) $itemId]);
    }
}

<?php

use App\core\Model;

/**
 * KHO — Dòng hàng của phiếu xuất kho.
 *
 * unit_cost/amount (giá vốn) để 0 khi còn nháp; điền tại thời điểm ghi sổ
 * theo bình quân gia quyền hiện tại (StocksModel::applyOut).
 */
class GoodsIssueItemsModel extends Model {

    protected $_table   = 'goods_issue_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByIssue($issueId){
        return $this->table($this->_table)
            ->select('`goods_issue_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `product_units`.`name` AS unit_name')
            ->joinOn('parts', 'goods_issue_items.part_id', 'parts.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id')
            ->where('goods_issue_items.issue_id', '=', (int) $issueId)
            ->orderBy('goods_issue_items.id', 'ASC')->get();
    }

    /**
     * Thay toàn bộ dòng hàng (transaction). Chỉ lưu part_id + quantity + note
     * (giá vốn tính khi ghi sổ).
     * @param array $lines mỗi phần tử: [part_id, quantity, note]
     */
    public function syncForIssue($issueId, array $lines){
        $issueId = (int) $issueId;
        return $this->transaction(function($db) use ($issueId, $lines){
            $db->delete('goods_issue_items', '`issue_id` = ?', [$issueId]);
            foreach ($lines as $ln){
                $partId = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
                $qty    = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
                if ($partId <= 0 || $qty <= 0) continue;
                $db->insert('goods_issue_items', [
                    'issue_id'  => $issueId,
                    'part_id'   => $partId,
                    'quantity'  => $qty,
                    'unit_cost' => 0,
                    'amount'    => 0,
                    'note'      => !empty($ln['note']) ? $ln['note'] : null,
                ]);
            }
        });
    }

    /** Cập nhật giá vốn 1 dòng (khi ghi sổ) */
    public function setCost($itemId, $unitCost, $amount){
        return $this->update('goods_issue_items',
            ['unit_cost' => (float) $unitCost, 'amount' => (float) $amount],
            '`id` = ?', [(int) $itemId]);
    }
}

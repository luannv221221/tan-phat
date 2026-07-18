<?php

use App\core\Model;

/**
 * KHO — Engine tồn kho BÌNH QUÂN GIA QUYỀN tức thời.
 *
 * `stocks`      = số dư tồn tức thời theo (kho x phụ tùng).
 * `stock_cards` = sổ append-only; mỗi nhập/xuất 1 dòng, có số dư luỹ kế.
 *
 * ⚠️ Các hàm applyIn/applyOut/reverseDoc KHÔNG tự mở transaction — controller
 * bọc cả phiên ghi sổ trong 1 transaction để nguyên tử (tồn + thẻ kho + bút toán).
 */
class StocksModel extends Model {

    protected $_table   = 'stocks';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Dòng tồn hiện tại của (kho, phụ tùng) hoặc null */
    public function getRow($warehouseId, $partId){
        return $this->table($this->_table)
                    ->where('warehouse_id', '=', (int) $warehouseId)
                    ->where('part_id', '=', (int) $partId)
                    ->first();
    }

    /** Số lượng tồn hiện tại (0 nếu chưa có) */
    public function available($warehouseId, $partId){
        $r = $this->getRow($warehouseId, $partId);
        return $r ? (float) $r['quantity'] : 0.0;
    }

    /** Đơn giá bình quân hiện tại */
    public function avgCost($warehouseId, $partId){
        $r = $this->getRow($warehouseId, $partId);
        return $r ? (float) $r['avg_cost'] : 0.0;
    }

    /** Ghi thẳng số dư stocks (upsert theo kho+phụ tùng) */
    private function setBalance($warehouseId, $partId, $qty, $avg){
        $now = date('Y-m-d H:i:s');
        $row = $this->getRow($warehouseId, $partId);
        if (empty($row)){
            $this->insert('stocks', [
                'warehouse_id' => (int) $warehouseId,
                'part_id'      => (int) $partId,
                'quantity'     => $qty,
                'avg_cost'     => $avg,
                'update_at'    => $now,
            ]);
        } else {
            $this->update('stocks',
                ['quantity' => $qty, 'avg_cost' => $avg, 'update_at' => $now],
                '`id` = ?', [(int) $row['id']]);
        }
    }

    /**
     * NHẬP: cộng tồn + cập nhật bình quân gia quyền, ghi 1 dòng thẻ kho.
     *   bq_mới = (SL_cũ*bq_cũ + SL_nhập*giá_nhập) / (SL_cũ + SL_nhập)
     */
    public function applyIn($warehouseId, $partId, $qty, $unitCost, $docType, $docId, $docNo, $date, $note = null){
        $qty      = (float) $qty;
        $unitCost = (float) $unitCost;

        $r      = $this->getRow($warehouseId, $partId);
        $oldQty = $r ? (float) $r['quantity'] : 0.0;
        $oldAvg = $r ? (float) $r['avg_cost'] : 0.0;

        $newQty = $oldQty + $qty;
        $newAvg = $newQty > 0 ? (($oldQty * $oldAvg) + ($qty * $unitCost)) / $newQty : 0.0;
        $newAvg = round($newAvg, 2);

        $this->setBalance($warehouseId, $partId, $newQty, $newAvg);
        $this->addCard($warehouseId, $partId, $date, $docType, $docId, $docNo,
                       $qty, 0, $unitCost, $newQty, round($newQty * $newAvg, 2), $note);
    }

    /**
     * XUẤT: trừ tồn theo bình quân hiện tại (bq KHÔNG đổi khi xuất).
     * @return float đơn giá bình quân đã dùng (giá vốn/đơn vị)
     */
    public function applyOut($warehouseId, $partId, $qty, $docType, $docId, $docNo, $date, $note = null){
        $qty = (float) $qty;

        $r      = $this->getRow($warehouseId, $partId);
        $oldQty = $r ? (float) $r['quantity'] : 0.0;
        $avg    = $r ? (float) $r['avg_cost'] : 0.0;

        $newQty = $oldQty - $qty;
        $this->setBalance($warehouseId, $partId, $newQty, $avg);
        $this->addCard($warehouseId, $partId, $date, $docType, $docId, $docNo,
                       0, $qty, $avg, $newQty, round($newQty * $avg, 2), $note);
        return $avg;
    }

    private function addCard($warehouseId, $partId, $date, $docType, $docId, $docNo,
                             $qtyIn, $qtyOut, $unitCost, $balanceQty, $balanceValue, $note){
        $this->insert('stock_cards', [
            'warehouse_id'  => (int) $warehouseId,
            'part_id'       => (int) $partId,
            'move_date'     => $date,
            'doc_type'      => $docType,
            'doc_id'        => (int) $docId,
            'doc_no'        => $docNo,
            'qty_in'        => $qtyIn,
            'qty_out'       => $qtyOut,
            'unit_cost'     => $unitCost,
            'balance_qty'   => $balanceQty,
            'balance_value' => $balanceValue,
            'note'          => $note,
            'create_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Phiếu này có phải PHÁT SINH CUỐI CÙNG của (kho, phụ tùng)?
     * Chỉ khi đúng mới cho huỷ ghi sổ (bình quân gia quyền không đảo ngược được
     * nếu đã có nhập/xuất khác chen sau).
     */
    public function isLastMovement($warehouseId, $partId, $docType, $docId){
        $globalMax = $this->table('stock_cards')
            ->select('`id`')
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->where('part_id', '=', (int) $partId)
            ->orderBy('id', 'DESC')->first();
        if (empty($globalMax)) return true; // không có thẻ nào -> không chặn

        $myMax = $this->table('stock_cards')
            ->select('`id`')
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->where('part_id', '=', (int) $partId)
            ->where('doc_type', '=', $docType)
            ->where('doc_id', '=', (int) $docId)
            ->orderBy('id', 'DESC')->first();
        if (empty($myMax)) return true; // phiếu chưa ghi thẻ cho phụ tùng này

        return (int) $globalMax['id'] === (int) $myMax['id'];
    }

    /**
     * Đảo phát sinh của 1 phiếu cho (kho, phụ tùng): xoá thẻ kho của phiếu,
     * khôi phục tồn về số dư của thẻ liền trước. Giả định đã kiểm isLastMovement.
     */
    public function reverseDoc($warehouseId, $partId, $docType, $docId){
        $this->delete('stock_cards',
            '`warehouse_id` = ? AND `part_id` = ? AND `doc_type` = ? AND `doc_id` = ?',
            [(int) $warehouseId, (int) $partId, $docType, (int) $docId]);

        $prev = $this->table('stock_cards')
            ->where('warehouse_id', '=', (int) $warehouseId)
            ->where('part_id', '=', (int) $partId)
            ->orderBy('id', 'DESC')->first();

        if (empty($prev)){
            // Không còn phát sinh -> tồn 0
            $this->setBalance($warehouseId, $partId, 0, 0);
        } else {
            $qty = (float) $prev['balance_qty'];
            $val = (float) $prev['balance_value'];
            $avg = $qty > 0 ? round($val / $qty, 2) : 0.0;
            $this->setBalance($warehouseId, $partId, $qty, $avg);
        }
    }

    /** Tổng tồn của 1 phụ tùng trên MỌI kho (cho storefront — TASK_79) */
    public function totalByPart($partId){
        $r = $this->table($this->_table)
                  ->select('SUM(`quantity`) AS total')
                  ->where('part_id', '=', (int) $partId)->first();
        return (float) ($r['total'] ?? 0);
    }

    // ---------- Báo cáo ----------

    /** Tồn kho hiện tại kèm thông tin phụ tùng; lọc theo kho + từ khoá */
    public function getStockList($warehouseId = 0, $keyword = ''){
        $q = $this->table($this->_table)
            ->select('`stocks`.*, `parts`.`code` AS part_code, `parts`.`name` AS part_name, '
                   . '`parts`.`oem_code` AS oem_code, `product_units`.`name` AS unit_name, '
                   . '`warehouses`.`name` AS warehouse_name, `warehouses`.`code` AS warehouse_code')
            ->joinOn('parts', 'stocks.part_id', 'parts.id')
            ->joinOn('warehouses', 'stocks.warehouse_id', 'warehouses.id')
            ->leftJoinOn('product_units', 'parts.unit_id', 'product_units.id');

        if ($warehouseId > 0){
            $q = $q->where('stocks.warehouse_id', '=', (int) $warehouseId);
        }
        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('parts.name', $like);
                $sub->whereOrLike('parts.code', $like);
                $sub->whereOrLike('parts.oem_code', $like);
            });
        }

        return $q->orderBy('parts.name', 'ASC')->get();
    }

    /** Thẻ kho 1 phụ tùng trong khoảng ngày (tăng dần để cộng dồn) */
    public function getCards($partId, $warehouseId = 0, $from = '', $to = ''){
        $q = $this->table('stock_cards')
            ->select('`stock_cards`.*, `warehouses`.`name` AS warehouse_name')
            ->joinOn('warehouses', 'stock_cards.warehouse_id', 'warehouses.id')
            ->where('stock_cards.part_id', '=', (int) $partId);

        if ($warehouseId > 0) $q = $q->where('stock_cards.warehouse_id', '=', (int) $warehouseId);
        if ($from !== '')     $q = $q->where('stock_cards.move_date', '>=', $from);
        if ($to !== '')       $q = $q->where('stock_cards.move_date', '<=', $to);

        return $q->orderBy('stock_cards.id', 'ASC')->get();
    }

    /**
     * Số dư (số lượng, giá trị) của phụ tùng NGAY TRƯỚC ngày $from — cho tồn đầu kỳ
     * của báo cáo thẻ kho. Lấy thẻ cuối cùng có move_date < $from.
     */
    public function getBalanceBefore($partId, $warehouseId, $from){
        if ($from === '') return ['qty' => 0.0, 'value' => 0.0];

        $q = $this->table('stock_cards')
            ->where('part_id', '=', (int) $partId)
            ->where('move_date', '<', $from);
        if ($warehouseId > 0) $q = $q->where('warehouse_id', '=', (int) $warehouseId);

        $row = $q->orderBy('id', 'DESC')->first();
        if (empty($row)) return ['qty' => 0.0, 'value' => 0.0];
        return ['qty' => (float) $row['balance_qty'], 'value' => (float) $row['balance_value']];
    }
}

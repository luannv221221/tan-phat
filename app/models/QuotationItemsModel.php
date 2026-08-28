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
            // item_type để form báo giá xếp dòng vào đúng tab (Hàng hoá / Dịch vụ)
            ->select('`quotation_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `parts`.`item_type` AS item_type, '
                   . '`part_units`.`name` AS unit_name')
            ->joinOn('parts', 'quotation_items.part_id', 'parts.id')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id')
            ->where('quotation_items.quotation_id', '=', (int) $quotationId)
            ->orderBy('quotation_items.id', 'ASC')->get();
    }

    /**
     * ⭐ Dòng của một báo giá, dạng dùng cho nút "Chép từ báo giá cũ".
     *
     * Khác getByQuotation ở hai chỗ, và cả hai đều cần thiết:
     *
     *   1. Kèm GIÁ HIỆN TẠI của mặt hàng (`gia_bay_gio`). Báo giá cũ giữ giá
     *      tại thời điểm lập; chép nguyên si là dễ chào lại mức giá đã lỗi
     *      thời. Trả về cả hai để người lập tự chọn lấy giá nào.
     *
     *   2. Kèm `con_ban` — mặt hàng đó còn kinh doanh không. Chép về một mặt
     *      hàng đã ngừng bán thì lúc lưu mới báo lỗi, phiền hơn là loại ngay.
     *
     * INNER JOIN `parts` giống getByQuotation: mặt hàng đã bị XOÁ hẳn thì dòng
     * đó biến mất khỏi kết quả. Bên controller đếm chênh lệch để báo cho người
     * dùng biết đã bỏ qua mấy dòng, không im lặng nuốt mất.
     */
    public function dongDeChep($quotationId){
        return $this->table($this->_table)
            ->select('`quotation_items`.`part_id`, `quotation_items`.`quantity`, '
                   . '`quotation_items`.`unit_price`, `quotation_items`.`discount_percent`, '
                   . '`quotation_items`.`note`, '
                   . '`parts`.`item_type`, `parts`.`code` AS part_code, `parts`.`name` AS part_name, '
                   . 'COALESCE(NULLIF(`parts`.`sale_price`, 0), `parts`.`price`) AS gia_bay_gio, '
                   . '`parts`.`status` AS con_ban')
            ->joinOn('parts', 'quotation_items.part_id', 'parts.id')
            ->where('quotation_items.quotation_id', '=', (int) $quotationId)
            ->orderBy('quotation_items.id', 'ASC')->get();
    }

    /** Đếm TỔNG số dòng, kể cả dòng có mặt hàng đã bị xoá (để so với dongDeChep) */
    public function demDong($quotationId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('quotation_id', '=', (int) $quotationId)->first();
        return (int) ($r['c'] ?? 0);
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
                $disc   = isset($ln['discount_percent']) ? (float) $ln['discount_percent'] : 0;
                if ($disc < 0) $disc = 0; if ($disc > 100) $disc = 100;
                $amount = round($qty * $price * (1 - $disc / 100), 2);
                $db->insert('quotation_items', [
                    'quotation_id'     => $quotationId,
                    'part_id'          => $partId,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'discount_percent' => $disc,
                    'amount'           => $amount,
                    'note'             => !empty($ln['note']) ? $ln['note'] : null,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }
}

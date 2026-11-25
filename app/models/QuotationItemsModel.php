<?php

use App\core\Model;

/**
 * BÁN HÀNG — Dòng hàng của báo giá.
 *
 * VAT TỪNG DÒNG (khách 25/11/2026, migration 000117): mỗi dòng mang `vat_rate`
 * + `tax_amount`; thuế đầu báo giá = tongThue(). Xem vat_cho_dong() /
 * vat_dong_phieu_cu() ở app/helpers/functions.php.
 */
class QuotationItemsModel extends Model {

    protected $_table   = 'quotation_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByQuotation($quotationId){
        $dong = $this->table($this->_table)
            // item_type để form báo giá xếp dòng vào đúng tab (Hàng hoá / Dịch vụ)
            ->select('`quotation_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `parts`.`item_type` AS item_type, '
                   . '`part_units`.`name` AS unit_name')
            ->joinOn('parts', 'quotation_items.part_id', 'parts.id')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id')
            ->where('quotation_items.quotation_id', '=', (int) $quotationId)
            ->orderBy('quotation_items.id', 'ASC')->get();
        return vat_dong_phieu_cu($dong, $this->dauPhieu($quotationId));
    }

    /**
     * Thuế suất + thuế + tiền hàng ở ĐẦU báo giá — để nhận ra phiếu lập trước khi
     * có VAT từng dòng (vat_dong_phieu_cu). Đọc thẳng theo id: dòng hàng ở trên
     * cũng đọc thẳng theo id, nơi gọi đã lấy báo giá đúng gara trước rồi.
     */
    private function dauPhieu($quotationId){
        return $this->firstRaw('SELECT `vat_rate`, `tax_amount`, `subtotal` FROM `quotations` WHERE `id` = ?',
                               [(int) $quotationId]);
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
     * Kèm `vat_rate` của dòng: chép là chép y nguyên thuế suất từng dòng như chép
     * chiết khấu (khách 25/11/2026).
     *
     * INNER JOIN `parts` giống getByQuotation: mặt hàng đã bị XOÁ hẳn thì dòng
     * đó biến mất khỏi kết quả. Bên controller đếm chênh lệch để báo cho người
     * dùng biết đã bỏ qua mấy dòng, không im lặng nuốt mất.
     */
    public function dongDeChep($quotationId){
        $dong = $this->table($this->_table)
            ->select('`quotation_items`.`part_id`, `quotation_items`.`quantity`, '
                   . '`quotation_items`.`unit_price`, `quotation_items`.`discount_percent`, '
                   . '`quotation_items`.`note`, `quotation_items`.`amount`, '
                   . '`quotation_items`.`vat_rate`, `quotation_items`.`tax_amount`, '
                   . '`parts`.`item_type`, `parts`.`code` AS part_code, `parts`.`name` AS part_name, '
                   . 'COALESCE(NULLIF(`parts`.`sale_price`, 0), `parts`.`price`) AS gia_bay_gio, '
                   . '`parts`.`status` AS con_ban')
            ->joinOn('parts', 'quotation_items.part_id', 'parts.id')
            ->where('quotation_items.quotation_id', '=', (int) $quotationId)
            ->orderBy('quotation_items.id', 'ASC')->get();
        return vat_dong_phieu_cu($dong, $this->dauPhieu($quotationId));
    }

    /** Đếm TỔNG số dòng, kể cả dòng có mặt hàng đã bị xoá (để so với dongDeChep) */
    public function demDong($quotationId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('quotation_id', '=', (int) $quotationId)->first();
        return (int) ($r['c'] ?? 0);
    }

    /**
     * Thay toàn bộ dòng (transaction).
     * @param array $lines [part_id, quantity, unit_price, discount_percent, note, vat_rate]
     * @param float $vatMacDinh thuế suất cho dòng không mang `vat_rate` hợp lệ
     *                          (web / giỏ hàng gọi không truyền -> 0, như trước)
     * @return float tổng tiền chưa thuế — thuế thì hỏi tongThue()
     */
    public function syncForQuotation($quotationId, array $lines, $vatMacDinh = 0){
        $quotationId = (int) $quotationId;
        $macDinh = vat_hop_le($vatMacDinh);
        if ($macDinh === null) $macDinh = 0.0;
        return $this->transaction(function($db) use ($quotationId, $lines, $macDinh){
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
                /* Thuế suất ngoài danh sách được chọn không bao giờ xuống CSDL */
                $vat    = vat_hop_le(isset($ln['vat_rate']) ? $ln['vat_rate'] : null);
                if ($vat === null) $vat = $macDinh;
                $db->insert('quotation_items', [
                    'quotation_id'     => $quotationId,
                    'part_id'          => $partId,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'discount_percent' => $disc,
                    'amount'           => $amount,
                    'vat_rate'         => $vat,
                    'tax_amount'       => thue_dong($amount, $vat),
                    'note'             => !empty($ln['note']) ? $ln['note'] : null,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }

    /**
     * TỔNG THUẾ của một báo giá = cộng thuế từng dòng, đọc lại từ CSDL (cùng nếp
     * PurchaseOrderItemsModel::tongTheoPo — cộng ở hai nơi là có ngày lệch).
     */
    public function tongThue($quotationId){
        $r = $this->firstRaw('SELECT COALESCE(SUM(`tax_amount`), 0) AS t FROM `quotation_items` WHERE `quotation_id` = ?',
                             [(int) $quotationId]);
        return (float) (isset($r['t']) ? $r['t'] : 0);
    }
}

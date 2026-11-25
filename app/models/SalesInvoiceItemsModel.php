<?php

use App\core\Model;

/**
 * BÁN HÀNG — Dòng hàng của hoá đơn bán.
 * unit_cost/cost_amount (giá vốn) điền khi GHI SỔ theo bình quân gia quyền.
 *
 * VAT TỪNG DÒNG (khách 25/11/2026, migration 000117): mỗi dòng mang `vat_rate`
 * + `tax_amount`; thuế đầu hoá đơn (= thuế GTGT đầu ra, Có 3331) = tongThue().
 */
class SalesInvoiceItemsModel extends Model {

    protected $_table   = 'sales_invoice_items';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getByInvoice($invoiceId){
        $dong = $this->table($this->_table)
            // item_type để form hoá đơn xếp dòng vào đúng tab (Hàng hoá / Dịch vụ)
            ->select('`sales_invoice_items`.*, `parts`.`code` AS part_code, '
                   . '`parts`.`name` AS part_name, `parts`.`item_type` AS item_type, '
                   . '`part_units`.`name` AS unit_name')
            ->joinOn('parts', 'sales_invoice_items.part_id', 'parts.id')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id')
            ->where('sales_invoice_items.invoice_id', '=', (int) $invoiceId)
            ->orderBy('sales_invoice_items.id', 'ASC')->get();
        return vat_dong_phieu_cu($dong, $this->dauPhieu($invoiceId));
    }

    /**
     * Thuế suất + thuế + tiền hàng ở ĐẦU hoá đơn — để nhận ra hoá đơn lập trước
     * khi có VAT từng dòng (vat_dong_phieu_cu). Đọc thẳng theo id như dòng hàng.
     */
    private function dauPhieu($invoiceId){
        return $this->firstRaw('SELECT `vat_rate`, `tax_amount`, `subtotal` FROM `sales_invoices` WHERE `id` = ?',
                               [(int) $invoiceId]);
    }

    /**
     * Dòng hàng của một hoá đơn, kèm thông tin để chép sang hoá đơn mới.
     *
     * `gia_bay_gio` là giá HIỆN TẠI của mặt hàng, khác `unit_price` là giá đã
     * chốt trên hoá đơn cũ. Form chép hiện cả hai để người dùng thấy giá nào
     * đã đổi — chép mù giá cũ thì bán lỗ mà không ai biết.
     *
     * `con_ban` để bỏ ngay mặt hàng đã ngừng kinh doanh, thay vì để tới lúc lưu
     * mới báo lỗi. Dùng JOIN (không LEFT JOIN) nên dòng có mặt hàng đã bị XOÁ
     * hẳn khỏi `parts` sẽ không ra — demDong() lo việc đếm chênh lệch đó.
     *
     * Kèm `vat_rate` của dòng: chép y nguyên thuế suất như chép chiết khấu.
     */
    public function dongDeChep($invoiceId){
        $dong = $this->table($this->_table)
            ->select('`sales_invoice_items`.`part_id`, `sales_invoice_items`.`quantity`, '
                   . '`sales_invoice_items`.`unit_price`, `sales_invoice_items`.`discount_percent`, '
                   . '`sales_invoice_items`.`note`, `sales_invoice_items`.`amount`, '
                   . '`sales_invoice_items`.`vat_rate`, `sales_invoice_items`.`tax_amount`, '
                   . '`parts`.`item_type`, `parts`.`code` AS part_code, `parts`.`name` AS part_name, '
                   . 'COALESCE(NULLIF(`parts`.`sale_price`, 0), `parts`.`price`) AS gia_bay_gio, '
                   . '`parts`.`status` AS con_ban')
            ->joinOn('parts', 'sales_invoice_items.part_id', 'parts.id')
            ->where('sales_invoice_items.invoice_id', '=', (int) $invoiceId)
            ->orderBy('sales_invoice_items.id', 'ASC')->get();
        return vat_dong_phieu_cu($dong, $this->dauPhieu($invoiceId));
    }

    /** Đếm TỔNG số dòng, kể cả dòng có mặt hàng đã bị xoá (để so với dongDeChep) */
    public function demDong($invoiceId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('invoice_id', '=', (int) $invoiceId)->first();
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }

    /**
     * Thay toàn bộ dòng (transaction).
     * @param array $lines [part_id, quantity, unit_price, discount_percent, note, vat_rate]
     * @param float $vatMacDinh thuế suất cho dòng không mang `vat_rate` hợp lệ
     *                          (đơn hàng web gọi không truyền -> 0, như trước)
     * @return float tổng tiền chưa thuế — thuế thì hỏi tongThue()
     */
    public function syncForInvoice($invoiceId, array $lines, $vatMacDinh = 0){
        $invoiceId = (int) $invoiceId;
        $macDinh = vat_hop_le($vatMacDinh);
        if ($macDinh === null) $macDinh = 0.0;
        return $this->transaction(function($db) use ($invoiceId, $lines, $macDinh){
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
                /* Thuế suất ngoài danh sách được chọn không bao giờ xuống CSDL */
                $vat    = vat_hop_le(isset($ln['vat_rate']) ? $ln['vat_rate'] : null);
                if ($vat === null) $vat = $macDinh;
                $db->insert('sales_invoice_items', [
                    'invoice_id'       => $invoiceId,
                    'part_id'          => $partId,
                    'quantity'         => $qty,
                    'unit_price'       => $price,
                    'discount_percent' => $disc,
                    'amount'           => $amount,
                    'vat_rate'         => $vat,
                    'tax_amount'       => thue_dong($amount, $vat),
                    'unit_cost'        => 0,
                    'cost_amount'      => 0,
                    'note'             => !empty($ln['note']) ? $ln['note'] : null,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }

    /** TỔNG THUẾ của một hoá đơn = cộng thuế từng dòng, đọc lại từ CSDL. */
    public function tongThue($invoiceId){
        $r = $this->firstRaw('SELECT COALESCE(SUM(`tax_amount`), 0) AS t FROM `sales_invoice_items` WHERE `invoice_id` = ?',
                             [(int) $invoiceId]);
        return (float) (isset($r['t']) ? $r['t'] : 0);
    }

    public function setCost($itemId, $unitCost, $costAmount){
        return $this->update('sales_invoice_items',
            ['unit_cost' => (float) $unitCost, 'cost_amount' => (float) $costAmount],
            '`id` = ?', [(int) $itemId]);
    }
}

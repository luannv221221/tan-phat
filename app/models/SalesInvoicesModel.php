<?php

use App\core\Model;

/**
 * BÁN HÀNG — Hoá đơn bán. Ghi sổ -> doanh thu + thuế + giá vốn + trừ tồn (KT-6).
 */
class SalesInvoicesModel extends Model {

    protected $_table   = 'sales_invoices';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists($status = '', $from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`sales_invoices`.*, `partners`.`name` AS customer_full, '
                   . '`warehouses`.`name` AS warehouse_name')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->joinOn('warehouses', 'sales_invoices.warehouse_id', 'warehouses.id');

        if ($status !== '' && ($status === '0' || $status === '1')){
            $q = $q->where('sales_invoices.status', '=', (int) $status);
        }
        if ($from !== '') $q = $q->where('sales_invoices.invoice_date', '>=', $from);
        if ($to !== '')   $q = $q->where('sales_invoices.invoice_date', '<=', $to);

        return $q->orderBy('sales_invoices.invoice_date', 'DESC')
                 ->orderBy('sales_invoices.id', 'DESC')->get();
    }

    /**
     * Chi tiết hoá đơn — PHẢI join giống getLists().
     *
     * Bản cũ dùng getFirst() (SELECT * thuần, không join), nên trang chi tiết
     * thiếu `warehouse_name` và `customer_full`: ô "Kho xuất" trống kèm
     * PHP Warning "Undefined array key warehouse_name".
     *
     * leftJoin cho `warehouses` (khác getLists dùng join thường): hoá đơn cũ có
     * thể chưa gán kho, join thường sẽ làm mất luôn dòng và trang báo
     * "Không tìm thấy hoá đơn" — sai còn nặng hơn.
     */
    public function getDetail($id){
        return $this->table($this->_table)
            ->select('`sales_invoices`.*, `partners`.`name` AS customer_full, '
                   . '`warehouses`.`name` AS warehouse_name')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->leftJoinOn('warehouses', 'sales_invoices.warehouse_id', 'warehouses.id')
            ->where('sales_invoices.id', '=', (int) $id)
            ->first();
    }

    /**
     * Hoá đơn cũ để chép lại dòng hàng.
     *
     * Hoá đơn của CHÍNH khách đang chọn được đẩy lên đầu (`uu_tien`): gara hay
     * gặp khách quay lại làm đúng gói cũ, tìm thủ công giữa hàng trăm số hoá
     * đơn thì thà gõ tay còn nhanh hơn.
     *
     * KHÔNG lọc theo trạng thái: hoá đơn nháp cũng chép được. Người ta hay lập
     * nháp một cái mẫu rồi nhân bản ra nhiều lần.
     */
    public function danhSachDeChep($customerId = 0, $limit = 50){
        $customerId = (int) $customerId;

        return $this->table($this->_table)
            ->select('`sales_invoices`.`id`, `sales_invoices`.`invoice_no`, '
                   . '`sales_invoices`.`invoice_date`, `sales_invoices`.`total_amount`, '
                   . '`sales_invoices`.`status`, '
                   . 'COALESCE(`partners`.`name`, `sales_invoices`.`customer_name`, \'\') AS khach, '
                   . '(SELECT COUNT(*) FROM `sales_invoice_items` si WHERE si.`invoice_id` = `sales_invoices`.`id`) AS so_dong, '
                   . 'CASE WHEN `sales_invoices`.`customer_id` = ' . $customerId . ' THEN 0 ELSE 1 END AS uu_tien')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->orderBy('uu_tien', 'ASC')
            ->orderBy('sales_invoices.invoice_date', 'DESC')
            ->orderBy('sales_invoices.id', 'DESC')
            ->limit((int) $limit)
            ->get();
    }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`invoice_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['invoice_no'], $m)){ $n = (int) $m[1]; }
        return 'HD-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    /** Số HĐĐT kế tiếp (max số đã phát hành + 1, đệm 8 chữ số theo TT78) */
    public function nextEinvoiceNo(){
        $row = $this->table($this->_table)
            ->select('`einvoice_no`')
            ->whereNotNull('einvoice_no')
            ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)/', $row['einvoice_no'], $m)){ $n = (int) $m[1]; }
        return str_pad($n + 1, 8, '0', STR_PAD_LEFT);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){ return $this->deleteById($id); } // items CASCADE

    /**
     * Báo cáo bán hàng: hoá đơn ĐÃ GHI SỔ trong kỳ, kèm tên KH + người lập.
     * Controller tự gộp theo khách / nhân viên.
     */
    public function getPostedForReport($from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`sales_invoices`.*, `partners`.`name` AS customer_full, `users`.`name` AS staff_name')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->leftJoinOn('users', 'sales_invoices.created_by', 'users.id')
            ->where('sales_invoices.status', '=', 1);

        if ($from !== '') $q = $q->where('sales_invoices.invoice_date', '>=', $from);
        if ($to !== '')   $q = $q->where('sales_invoices.invoice_date', '<=', $to);

        return $q->orderBy('sales_invoices.invoice_date', 'ASC')
                 ->orderBy('sales_invoices.id', 'ASC')->get();
    }
}

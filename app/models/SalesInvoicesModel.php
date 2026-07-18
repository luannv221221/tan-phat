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

    public function getDetail($id){ return $this->getFirst($id); }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`invoice_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['invoice_no'], $m)){ $n = (int) $m[1]; }
        return 'HD-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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

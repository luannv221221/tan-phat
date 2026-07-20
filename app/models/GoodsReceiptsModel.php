<?php

use App\core\Model;

/**
 * KHO — Phiếu nhập kho (đầu chứng từ).
 *
 * Loại: nhap_mua (có công nợ NCC) / nhap_khac / nhap_tra (nhập trả lại).
 * status: 0 nháp / 1 đã ghi sổ. Ghi sổ -> cập nhật tồn + sinh bút toán (acc_voucher_id).
 */
class GoodsReceiptsModel extends Model {

    protected $_table   = 'goods_receipts';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $types = [
        'nhap_mua'  => 'Nhập mua (công nợ NCC)',
        'nhap_khac' => 'Nhập khác',
        'nhap_tra'  => 'Nhập trả lại',
    ];

    public function getLists($type = '', $from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`goods_receipts`.*, `warehouses`.`name` AS warehouse_name, '
                   . '`partners`.`name` AS partner_full')
            ->joinOn('warehouses', 'goods_receipts.warehouse_id', 'warehouses.id')
            ->leftJoinOn('partners', 'goods_receipts.partner_id', 'partners.id');

        if ($type !== '') $q = $q->where('goods_receipts.receipt_type', '=', $type);
        if ($from !== '') $q = $q->where('goods_receipts.receipt_date', '>=', $from);
        if ($to !== '')   $q = $q->where('goods_receipts.receipt_date', '<=', $to);

        return $q->orderBy('goods_receipts.receipt_date', 'DESC')
                 ->orderBy('goods_receipts.id', 'DESC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByNo($no){
        return $this->table($this->_table)->where('receipt_no', '=', $no)->first();
    }

    /** Sinh số phiếu kế tiếp PNK-000001 */
    public function nextNo(){
        $row = $this->table($this->_table)
                    ->select('`receipt_no`')
                    ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['receipt_no'], $m)){
            $n = (int) $m[1];
        }
        return 'PNK-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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

    public function remove($id){
        return $this->deleteById($id); // items CASCADE
    }
}

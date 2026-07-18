<?php

use App\core\Model;

/**
 * KHO — Phiếu xuất kho (đầu chứng từ).
 *
 * Loại: xuat_ban (giá vốn) / xuat_khac / xuat_tra (xuất trả NCC).
 * Giá vốn xuất tính tại thời điểm GHI SỔ theo bình quân gia quyền hiện tại.
 */
class GoodsIssuesModel extends Model {

    protected $_table   = 'goods_issues';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $types = [
        'xuat_ban'  => 'Xuất bán (giá vốn)',
        'xuat_khac' => 'Xuất khác',
        'xuat_tra'  => 'Xuất trả NCC',
    ];

    public function getLists($type = '', $from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`goods_issues`.*, `warehouses`.`name` AS warehouse_name, '
                   . '`partners`.`name` AS partner_full')
            ->joinOn('warehouses', 'goods_issues.warehouse_id', 'warehouses.id')
            ->leftJoinOn('partners', 'goods_issues.partner_id', 'partners.id');

        if ($type !== '') $q = $q->where('goods_issues.issue_type', '=', $type);
        if ($from !== '') $q = $q->where('goods_issues.issue_date', '>=', $from);
        if ($to !== '')   $q = $q->where('goods_issues.issue_date', '<=', $to);

        return $q->orderBy('goods_issues.issue_date', 'DESC')
                 ->orderBy('goods_issues.id', 'DESC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByNo($no){
        return $this->table($this->_table)->where('issue_no', '=', $no)->first();
    }

    /** Sinh số phiếu kế tiếp PXK-000001 */
    public function nextNo(){
        $row = $this->table($this->_table)
                    ->select('`issue_no`')
                    ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['issue_no'], $m)){
            $n = (int) $m[1];
        }
        return 'PXK-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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

<?php

use App\core\Model;

/**
 * BÁN HÀNG — Báo giá (chỉ đề xuất giá, không tác động tồn/kế toán).
 */
class QuotationsModel extends Model {

    protected $_table   = 'quotations';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $statuses = [
        'draft'    => 'Nháp',
        'sent'     => 'Đã gửi',
        'accepted' => 'Chấp nhận',
        'rejected' => 'Từ chối',
    ];

    public function getLists($status = '', $from = '', $to = ''){
        $q = $this->table($this->_table)
            ->select('`quotations`.*, `partners`.`name` AS customer_full')
            ->leftJoinOn('partners', 'quotations.customer_id', 'partners.id');

        if ($status !== '') $q = $q->where('quotations.status', '=', $status);
        if ($from !== '')   $q = $q->where('quotations.quote_date', '>=', $from);
        if ($to !== '')     $q = $q->where('quotations.quote_date', '<=', $to);

        return $q->orderBy('quotations.quote_date', 'DESC')
                 ->orderBy('quotations.id', 'DESC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`quote_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['quote_no'], $m)){ $n = (int) $m[1]; }
        return 'BG-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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
}

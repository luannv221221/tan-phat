<?php

use App\core\Model;

/**
 * CSKH-2 — Biên bản giao nhận thiết bị bảo hành (receive/return). RIÊNG từng gara.
 */
class WarrantyHandoversModel extends Model {

    protected $_table   = 'warranty_handovers';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_theoGara = true;

    public static $types = [
        'receive' => 'Biên bản NHẬN thiết bị',
        'return'  => 'Biên bản TRẢ thiết bị',
    ];

    public function getDetail($id){ return $this->getFirst($id); }

    /** Danh sách BB của 1 phiếu bảo hành (mới nhất trước) */
    public function getByWarranty($warrantyId){
        return $this->bangGara()
            ->where('warranty_id', '=', (int) $warrantyId)
            ->orderBy('id', 'DESC')->get();
    }

    public function nextNo(){
        $row = $this->bangGara()->select('`handover_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['handover_no'], $m)){ $n = (int) $m[1]; }
        return 'BBGN-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function remove($id){ return $this->deleteById($id); }
}

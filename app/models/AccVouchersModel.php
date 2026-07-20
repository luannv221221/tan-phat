<?php

use App\core\Model;

/**
 * KT-2 — Phiếu thu / chi (đầu chứng từ).
 *
 * Phiếu THU: Nợ TK quỹ (cash_account_id) / Có các TK ở acc_voucher_entries.
 * Phiếu CHI: Nợ các TK ở entries / Có TK quỹ.
 */
class AccVouchersModel extends Model {

    protected $_table   = 'acc_vouchers';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $types = ['thu' => 'Phiếu thu', 'chi' => 'Phiếu chi'];

    /** Danh sách phiếu kèm mã/tên TK quỹ; lọc theo type + khoảng ngày */
    public function getLists($type = '', $from = '', $to = ''){
        $q = $this->table($this->_table)
                  ->select('`acc_vouchers`.*, `acc_accounts`.`code` AS cash_code, `acc_accounts`.`name` AS cash_name')
                  ->joinOn('acc_accounts', 'acc_vouchers.cash_account_id', 'acc_accounts.id');

        if ($type !== '')  $q = $q->where('acc_vouchers.voucher_type', '=', $type);
        if ($from !== '')  $q = $q->where('acc_vouchers.voucher_date', '>=', $from);
        if ($to !== '')    $q = $q->where('acc_vouchers.voucher_date', '<=', $to);

        return $q->orderBy('acc_vouchers.voucher_date', 'DESC')
                 ->orderBy('acc_vouchers.id', 'DESC')
                 ->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function findByNo($no){
        return $this->table($this->_table)->where('voucher_no', '=', $no)->first();
    }

    /** Danh sách phiếu kế toán (voucher_type=ke_toan), không cần TK quỹ */
    public function getJournalList($from = '', $to = ''){
        $q = $this->table($this->_table)->where('voucher_type', '=', 'ke_toan');
        if ($from !== '') $q = $q->where('voucher_date', '>=', $from);
        if ($to !== '')   $q = $q->where('voucher_date', '<=', $to);
        return $q->orderBy('voucher_date', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /** Sinh số phiếu kế tiếp: PT (thu) / PC (chi) / PKT (kế toán) */
    public function nextNo($type){
        $prefixes = ['thu' => 'PT', 'chi' => 'PC', 'ke_toan' => 'PKT'];
        $prefix = isset($prefixes[$type]) ? $prefixes[$type] : 'PX';
        $row = $this->table($this->_table)
                    ->select('`voucher_no`')
                    ->where('voucher_type', '=', $type)
                    ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['voucher_no'], $m)){
            $n = (int) $m[1];
        }
        return $prefix . '-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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
        // acc_voucher_entries ON DELETE CASCADE -> định khoản tự xoá theo.
        return $this->deleteById($id);
    }

    // ---------- Sổ quỹ ----------

    /** Các phiếu ĐÃ GHI SỔ của 1 quỹ trong khoảng ngày (tăng dần để cộng dồn) */
    public function getCashBook($cashAccountId, $from = '', $to = ''){
        $q = $this->table($this->_table)
                  ->where('cash_account_id', '=', $cashAccountId)
                  ->where('status', '=', 1);
        if ($from !== '') $q = $q->where('voucher_date', '>=', $from);
        if ($to !== '')   $q = $q->where('voucher_date', '<=', $to);

        return $q->orderBy('voucher_date', 'ASC')->orderBy('id', 'ASC')->get();
    }

    /** Số dư đầu kỳ = tổng thu - tổng chi (đã ghi sổ) TRƯỚC ngày $from */
    public function getBalanceBefore($cashAccountId, $from){
        if ($from === '') return 0.0;

        $rows = $this->table($this->_table)
                     ->select('`voucher_type`, `amount`')
                     ->where('cash_account_id', '=', $cashAccountId)
                     ->where('status', '=', 1)
                     ->where('voucher_date', '<', $from)
                     ->get();

        $bal = 0.0;
        foreach ($rows ?: [] as $r){
            $bal += ($r['voucher_type'] === 'thu' ? 1 : -1) * (float) $r['amount'];
        }
        return $bal;
    }
}

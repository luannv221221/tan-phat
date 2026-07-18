<?php

use App\core\Model;

/**
 * KT-2 — Định khoản chi tiết của phiếu thu/chi (tài khoản đối ứng).
 */
class AccVoucherEntriesModel extends Model {

    protected $_table   = 'acc_voucher_entries';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Các dòng định khoản kèm mã/tên tài khoản đối ứng */
    public function getByVoucher($voucherId){
        return $this->table($this->_table)
            ->select('`acc_voucher_entries`.*, `acc_accounts`.`code` AS account_code, `acc_accounts`.`name` AS account_name')
            ->joinOn('acc_accounts', 'acc_voucher_entries.account_id', 'acc_accounts.id')
            ->where('acc_voucher_entries.voucher_id', '=', $voucherId)
            ->orderBy('acc_voucher_entries.id', 'ASC')
            ->get();
    }

    /** Định khoản của phiếu kế toán (Nợ/Có tự do) — trả về raw, controller tự map tên TK */
    public function getJournalByVoucher($voucherId){
        return $this->table($this->_table)
                    ->where('voucher_id', '=', $voucherId)
                    ->orderBy('id', 'ASC')
                    ->get();
    }

    /**
     * Thay toàn bộ định khoản PHIẾU KẾ TOÁN (mỗi dòng: Nợ TK / Có TK / số tiền).
     *
     * @param array $lines mỗi phần tử: [debit_account_id, credit_account_id, amount, description]
     * @return float tổng tiền
     */
    public function syncJournalForVoucher($voucherId, array $lines){
        $voucherId = (int) $voucherId;

        return $this->transaction(function($db) use ($voucherId, $lines){
            $db->delete('acc_voucher_entries', '`voucher_id` = ?', [$voucherId]);

            $now   = date('Y-m-d H:i:s');
            $total = 0.0;
            foreach ($lines as $ln){
                $dr  = isset($ln['debit_account_id'])  ? (int) $ln['debit_account_id']  : 0;
                $cr  = isset($ln['credit_account_id']) ? (int) $ln['credit_account_id'] : 0;
                $amt = isset($ln['amount']) ? (float) $ln['amount'] : 0;
                if ($dr <= 0 || $cr <= 0 || $amt <= 0) continue;

                $db->insert('acc_voucher_entries', [
                    'voucher_id'        => $voucherId,
                    'debit_account_id'  => $dr,
                    'credit_account_id' => $cr,
                    'amount'            => $amt,
                    'description'       => !empty($ln['description']) ? $ln['description'] : null,
                    'create_at'         => $now,
                ]);
                $total += $amt;
            }
            return $total;
        });
    }

    /**
     * Thay toàn bộ định khoản của 1 phiếu (transaction).
     *
     * @param array $lines mỗi phần tử: [account_id, amount, description, cost_item_id, project_id]
     * @return float tổng tiền các dòng hợp lệ
     */
    public function syncForVoucher($voucherId, array $lines){
        $voucherId = (int) $voucherId;

        return $this->transaction(function($db) use ($voucherId, $lines){
            $db->delete('acc_voucher_entries', '`voucher_id` = ?', [$voucherId]);

            $now   = date('Y-m-d H:i:s');
            $total = 0.0;
            foreach ($lines as $ln){
                $accId  = isset($ln['account_id']) ? (int) $ln['account_id'] : 0;
                $amount = isset($ln['amount']) ? (float) $ln['amount'] : 0;
                if ($accId <= 0 || $amount <= 0) continue;

                $db->insert('acc_voucher_entries', [
                    'voucher_id'   => $voucherId,
                    'account_id'   => $accId,
                    'amount'       => $amount,
                    'description'  => !empty($ln['description']) ? $ln['description'] : null,
                    'cost_item_id' => !empty($ln['cost_item_id']) ? (int) $ln['cost_item_id'] : null,
                    'project_id'   => !empty($ln['project_id']) ? (int) $ln['project_id'] : null,
                    'create_at'    => $now,
                ]);
                $total += $amount;
            }
            return $total;
        });
    }
}

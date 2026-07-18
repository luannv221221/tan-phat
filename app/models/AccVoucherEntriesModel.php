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

    /**
     * SỔ CÁI CHUẨN HOÁ — quy mọi phiếu ĐÃ GHI SỔ về dạng (Nợ TK / Có TK / số tiền).
     *   - phiếu kế toán: dùng debit/credit sẵn có
     *   - phiếu thu:  Nợ TK quỹ / Có account_id
     *   - phiếu chi:  Nợ account_id / Có TK quỹ
     * Dùng cho công nợ (KT-4) và nhật ký chung / sổ cái (KT-5).
     *
     * @param int    $partnerId lọc theo đối tượng (0 = tất cả)
     * @param string $from,$to  khoảng ngày
     * @param int    $accountId chỉ giữ dòng có TK này ở Nợ hoặc Có (0 = tất cả)
     */
    public function getPostedLedger($partnerId = 0, $from = '', $to = '', $accountId = 0){
        $q = $this->table($this->_table)
            ->select('`acc_voucher_entries`.*, `acc_vouchers`.`voucher_no`, `acc_vouchers`.`voucher_date`, '
                   . '`acc_vouchers`.`voucher_type`, `acc_vouchers`.`cash_account_id`, '
                   . '`acc_vouchers`.`partner_id`, `acc_vouchers`.`partner_name`, `acc_vouchers`.`reason`')
            ->joinOn('acc_vouchers', 'acc_voucher_entries.voucher_id', 'acc_vouchers.id')
            ->where('acc_vouchers.status', '=', 1);

        if ($partnerId > 0) $q = $q->where('acc_vouchers.partner_id', '=', $partnerId);
        if ($from !== '')   $q = $q->where('acc_vouchers.voucher_date', '>=', $from);
        if ($to !== '')     $q = $q->where('acc_vouchers.voucher_date', '<=', $to);

        $rows = $q->orderBy('acc_vouchers.voucher_date', 'ASC')
                  ->orderBy('acc_vouchers.id', 'ASC')
                  ->orderBy('acc_voucher_entries.id', 'ASC')
                  ->get();

        $out = [];
        foreach ($rows ?: [] as $r){
            $type = $r['voucher_type'];
            if ($type === 'ke_toan'){
                $dr = (int) $r['debit_account_id'];
                $cr = (int) $r['credit_account_id'];
            } elseif ($type === 'thu'){
                $dr = (int) $r['cash_account_id'];
                $cr = (int) $r['account_id'];
            } else { // chi
                $dr = (int) $r['account_id'];
                $cr = (int) $r['cash_account_id'];
            }

            if ($accountId > 0 && $dr !== $accountId && $cr !== $accountId) continue;

            $out[] = [
                'voucher_no'        => $r['voucher_no'],
                'voucher_date'      => $r['voucher_date'],
                'voucher_type'      => $type,
                'partner_id'        => $r['partner_id'] !== null ? (int) $r['partner_id'] : null,
                'partner_name'      => $r['partner_name'],
                'reason'            => $r['reason'],
                'description'       => $r['description'],
                'debit_account_id'  => $dr,
                'credit_account_id' => $cr,
                'amount'            => (float) $r['amount'],
            ];
        }
        return $out;
    }

    /**
     * Chèn 1 dòng định khoản Nợ/Có KHÔNG mở transaction — dùng khi ghi sổ phiếu
     * kho (KT-6): controller đã bọc cả phiên trong 1 transaction, không lồng được.
     */
    public function addJournalLine($voucherId, $debitId, $creditId, $amount, $description = null){
        $this->insert('acc_voucher_entries', [
            'voucher_id'        => (int) $voucherId,
            'debit_account_id'  => (int) $debitId,
            'credit_account_id' => (int) $creditId,
            'amount'            => (float) $amount,
            'description'       => $description,
            'create_at'         => date('Y-m-d H:i:s'),
        ]);
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

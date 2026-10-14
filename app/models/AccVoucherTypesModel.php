<?php

use App\core\Model;

/**
 * KẾ TOÁN — DANH MỤC LOẠI PHIẾU.
 *
 * Mỗi loại phiếu là một ĐỊNH KHOẢN VIẾT SẴN: mã, tên, thu hay chi, và cặp tài
 * khoản nợ – có lấy từ danh mục tài khoản. Người lập phiếu chỉ chọn loại, không
 * phải tự nghĩ ra định khoản.
 *
 * CHUNG VÀ RIÊNG như danh mục tài khoản: `garage_id IS NULL` là bộ loại phiếu
 * Tân Phát dựng sẵn (PT01 Thu tiền khách hàng, PC01 Chi trả nhà cung cấp…),
 * gara thêm loại của mình thì mang `garage_id`.
 */
class AccVoucherTypesModel extends Model {

    protected $_table   = 'acc_voucher_types';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_chungVaRieng = true;

    public static $loai = ['thu' => 'Phiếu thu', 'chi' => 'Phiếu chi'];

    /**
     * Danh sách kèm MÃ + TÊN tài khoản nợ / có.
     *
     * @param string $loai 'thu' | 'chi' | '' (tất cả)
     * @param bool   $chiHoatDong chỉ loại đang bật — cho ô chọn lúc lập phiếu
     */
    public function getLists($loai = '', $chiHoatDong = false){
        /* RAW chứ không QueryBuilder: cần join BẢNG `acc_accounts` HAI LẦN (một
           cho tài khoản nợ, một cho tài khoản có) nên phải có bí danh, mà
           joinOn() bọc backtick toàn bộ chuỗi và wrapField() NÉM LỖI với
           "acc_accounts AS tkno". Giá trị vẫn đi qua placeholder. */
        list($dkGara, $bd) = $this->dkChungVaRieng('v');

        $sql = 'SELECT `v`.*,
                       `tkno`.`code` AS `no_code`, `tkno`.`name` AS `no_name`,
                       `tkco`.`code` AS `co_code`, `tkco`.`name` AS `co_name`
                  FROM `acc_voucher_types` `v`
                  LEFT JOIN `acc_accounts` `tkno` ON `tkno`.`id` = `v`.`debit_id`
                  LEFT JOIN `acc_accounts` `tkco` ON `tkco`.`id` = `v`.`credit_id`
                 WHERE ' . $dkGara;

        if ($loai !== ''){ $sql .= ' AND `v`.`loai` = ?'; $bd[] = $loai; }
        if ($chiHoatDong)  $sql .= ' AND `v`.`status` = 1';

        return $this->getRaw($sql . ' ORDER BY `v`.`loai` ASC, `v`.`code` ASC', $bd);
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** Chi tiết KÈM mã tài khoản — lúc lập phiếu cần chép bản chụp nợ/có */
    public function chiTietKemTk($id){
        list($dk, $bd) = $this->dkChungVaRieng('v');
        return $this->firstRaw(
            'SELECT `v`.*, `tkno`.`code` AS `no_code`, `tkco`.`code` AS `co_code`
               FROM `acc_voucher_types` `v`
               LEFT JOIN `acc_accounts` `tkno` ON `tkno`.`id` = `v`.`debit_id`
               LEFT JOIN `acc_accounts` `tkco` ON `tkco`.`id` = `v`.`credit_id`
              WHERE `v`.`id` = ? AND ' . $dk . ' LIMIT 1',
            array_merge([(int) $id], $bd));
    }

    public function findByCode($code){
        return $this->bangChungVaRieng()->where('code', '=', trim((string) $code))->first();
    }

    /** Số phiếu đã lập theo loại này — còn phiếu thì không xoá */
    public function demPhieu($id){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `acc_vouchers` WHERE `vtype_id` = ?', [(int) $id]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
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

    /** Trả false khi còn phiếu trỏ vào — phiếu cũ phải giữ được loại của nó */
    public function remove($id){
        if ($this->demPhieu($id) > 0) return false;
        return $this->deleteById($id);
    }
}

<?php

use App\core\Model;

/**
 * KẾ TOÁN — GÁN / BỎ GÁN DANH MỤC DÙNG CHUNG THEO TỪNG CÔNG TY.
 *
 * Anh chốt 26/11/2026 (khách hỏi "danh mục tk, danh mục loại phiếu nó dùng
 * chung nhau à?"): "tân phát dựng sẵn nhưng cho thể gán hoặc không gán theo
 * từng cty, không phải cty con nào cũng giống nhau".
 *
 * Bảng `acc_shared_hidden` (migration 000124) ghi các dòng DÙNG CHUNG đã bị BỎ
 * GÁN cho một công ty. Không có dòng = được gán: công ty đang có, công ty mở
 * mới, tài khoản chuẩn Tân Phát thêm sau — đều thấy đủ như trước.
 *
 * Ai đọc danh mục thì ghép dk() vào truy vấn (AccAccountsModel,
 * AccVoucherTypesModel). Tân Phát (công ty tổng) không bị lọc: nó là nơi quản
 * lý bộ chuẩn.
 */
class AccSharedHiddenModel extends Model {

    protected $_table   = 'acc_shared_hidden';
    protected $_fields  = '*';
    protected $_primary = 'item_id';

    const TK = 'account';
    const LP = 'vtype';

    private static $__coBang = null;

    /** Chưa chạy 000124 thì coi như gán hết — đừng làm hỏng màn kế toán. */
    public function coBang(){
        if (self::$__coBang === null){
            try { self::$__coBang = (bool) $this->firstRaw("SHOW TABLES LIKE 'acc_shared_hidden'"); }
            catch (\Throwable $e){ self::$__coBang = false; }
        }
        return self::$__coBang;
    }

    /**
     * Điều kiện "dòng này KHÔNG bị bỏ gán cho công ty làm việc": [sql, bindings].
     * $cotId là cột id đã bọc sẵn, vd '`v`.`id`'.
     */
    public function dk($kind, $cotId){
        $g = self::garaLoc();
        if ($g === null || $g === 0 || $this->laGaraTongLoc() || !$this->coBang()) return ['1 = 1', []];
        return ['NOT EXISTS (SELECT 1 FROM `acc_shared_hidden` `bg` WHERE `bg`.`kind` = ? AND `bg`.`item_id` = '
                . $cotId . ' AND `bg`.`garage_id` = ?)', [$kind, (int) $g]];
    }

    /** Dòng $id có đang bị bỏ gán cho công ty làm việc không. */
    public function biAn($kind, $id){
        list($dk, $bd) = $this->dk($kind, '?');
        if ($dk === '1 = 1') return false;
        /* dk() đặt `?` vào chỗ cột id — bindings: kind, [id], garage */
        $r = $this->firstRaw('SELECT ' . $dk . ' AS `thay`', [$bd[0], (int) $id, $bd[1]]);
        return isset($r['thay']) && (int) $r['thay'] === 0;
    }

    /** Các id bị bỏ gán của một công ty: ['account' => [...], 'vtype' => [...]] */
    public function anCua($garaId){
        $ra = [self::TK => [], self::LP => []];
        if (!$this->coBang()) return $ra;
        foreach ((array) $this->getRaw('SELECT `kind`, `item_id` FROM `acc_shared_hidden` WHERE `garage_id` = ?', [(int) $garaId]) as $r){
            if (isset($ra[$r['kind']])) $ra[$r['kind']][] = (int) $r['item_id'];
        }
        return $ra;
    }

    /**
     * Dòng dùng chung mà công ty ĐANG DÙNG — không cho bỏ gán (bỏ thì phiếu cũ
     * sửa không được, sổ / báo cáo mất dòng): [kind => [id => lý do]].
     *   - loại phiếu: đã có phiếu của công ty;
     *   - tài khoản: đã có phiếu ghi nợ / có vào, loại phiếu RIÊNG của công ty
     *     định khoản vào, hoặc tài khoản riêng của công ty treo dưới nó.
     */
    public function dangDung($garaId){
        $g = (int) $garaId;
        $ra = [self::TK => [], self::LP => []];

        foreach ((array) $this->getRaw(
            'SELECT `t`.`id`, `t`.`code`, COUNT(*) AS `n` FROM `acc_vouchers` `v`
               JOIN `acc_voucher_types` `t` ON `t`.`id` = `v`.`vtype_id` AND `t`.`garage_id` IS NULL
              WHERE `v`.`garage_id` = ? GROUP BY `t`.`id`, `t`.`code`', [$g]) as $r){
            $ra[self::LP][(int) $r['id']] = 'đã có ' . (int) $r['n'] . ' phiếu';
        }

        $chung = [];
        foreach ((array) $this->getRaw('SELECT `id`, `code` FROM `acc_accounts` WHERE `garage_id` IS NULL') as $r){
            $chung[(string) $r['code']] = (int) $r['id'];
        }
        foreach ((array) $this->getRaw(
            'SELECT `ma`, COUNT(*) AS `n` FROM (
                 SELECT `debit_code` AS `ma` FROM `acc_vouchers` WHERE `garage_id` = ?
                 UNION ALL
                 SELECT `credit_code` FROM `acc_vouchers` WHERE `garage_id` = ?
             ) `x` WHERE `ma` IS NOT NULL AND `ma` <> \'\' GROUP BY `ma`', [$g, $g]) as $r){
            if (isset($chung[(string) $r['ma']])) $ra[self::TK][$chung[(string) $r['ma']]] = 'đã có ' . (int) $r['n'] . ' phiếu ghi vào';
        }
        foreach ((array) $this->getRaw(
            'SELECT `code`, `debit_id`, `credit_id` FROM `acc_voucher_types` WHERE `garage_id` = ?', [$g]) as $r){
            foreach (['debit_id', 'credit_id'] as $c){
                $id = (int) $r[$c];
                if ($id > 0 && in_array($id, $chung, true) && !isset($ra[self::TK][$id])){
                    $ra[self::TK][$id] = 'loại phiếu riêng ' . $r['code'] . ' của công ty dùng';
                }
            }
        }
        foreach ((array) $this->getRaw(
            'SELECT `code`, `parent_id` FROM `acc_accounts` WHERE `garage_id` = ? AND `parent_id` IS NOT NULL', [$g]) as $r){
            $id = (int) $r['parent_id'];
            if (in_array($id, $chung, true) && !isset($ra[self::TK][$id])){
                $ra[self::TK][$id] = 'tài khoản riêng ' . $r['code'] . ' của công ty treo dưới';
            }
        }
        return $ra;
    }

    /**
     * Lưu việc gán của một công ty. $tkGan / $lpGan: id dòng dùng chung ĐƯỢC
     * GÁN (tick). Phần còn lại bị bỏ gán — trừ thứ phải giữ:
     *   - đang dùng (dangDung);
     *   - tài khoản mà một loại phiếu còn gán định khoản vào;
     *   - cha của tài khoản được giữ (bỏ cha thì cả nhánh theo).
     *
     * @return array ['giuLai' => [câu báo vì sao giữ], 'anTk' => số, 'anLp' => số]
     */
    public function ganChoCongTy($garaId, array $tkGan, array $lpGan){
        $g = (int) $garaId;
        $tkGan = array_flip(array_map('intval', $tkGan));
        $lpGan = array_flip(array_map('intval', $lpGan));

        $tk = []; $lp = [];
        foreach ((array) $this->getRaw('SELECT `id`, `code`, `parent_id` FROM `acc_accounts` WHERE `garage_id` IS NULL') as $r){
            $tk[(int) $r['id']] = ['code' => $r['code'], 'cha' => $r['parent_id'] === null ? 0 : (int) $r['parent_id']];
        }
        foreach ((array) $this->getRaw('SELECT `id`, `code`, `debit_id`, `credit_id` FROM `acc_voucher_types` WHERE `garage_id` IS NULL') as $r){
            $lp[(int) $r['id']] = ['code' => $r['code'], 'tk' => array_filter([(int) $r['debit_id'], (int) $r['credit_id']])];
        }

        $dung = $this->dangDung($g);
        $giuLai = [];

        // Loại phiếu: tick hoặc đang dùng
        $lpGiu = [];
        foreach ($lp as $id => $x){
            if (isset($lpGan[$id])){ $lpGiu[$id] = true; continue; }
            if (isset($dung[self::LP][$id])){
                $lpGiu[$id] = true;
                $giuLai[] = 'Giữ loại phiếu ' . $x['code'] . ': ' . $dung[self::LP][$id] . '.';
            }
        }

        // Tài khoản: tick, đang dùng, hoặc loại phiếu còn gán cần
        $tkGiu = [];
        $vi = [];
        foreach ($lpGiu as $id => $_){
            foreach ($lp[$id]['tk'] as $t) if (isset($tk[$t])) $vi[$t][] = $lp[$id]['code'];
        }
        foreach ($tk as $id => $x){
            if (isset($tkGan[$id])){ $tkGiu[$id] = true; continue; }
            if (isset($dung[self::TK][$id])){
                $tkGiu[$id] = true;
                $giuLai[] = 'Giữ tài khoản ' . $x['code'] . ': ' . $dung[self::TK][$id] . '.';
            } elseif (!empty($vi[$id])){
                $tkGiu[$id] = true;
                $giuLai[] = 'Giữ tài khoản ' . $x['code'] . ': loại phiếu ' . implode(', ', array_unique($vi[$id]))
                          . ' còn gán đang định khoản vào (bỏ gán loại phiếu đó trước).';
            }
        }

        // Giữ con thì giữ cả cha — leo lên tới gốc (trần 20 bước phòng cây vòng)
        foreach (array_keys($tkGiu) as $id){
            $p = $tk[$id]['cha']; $buoc = 0;
            while ($p > 0 && isset($tk[$p]) && $buoc++ < 20){
                if (!isset($tkGiu[$p])){
                    $tkGiu[$p] = true;
                    if (!isset($tkGan[$p])) $giuLai[] = 'Giữ tài khoản ' . $tk[$p]['code'] . ': tài khoản con '
                                                       . $tk[$id]['code'] . ' còn gán.';
                }
                $p = $tk[$p]['cha'];
            }
        }

        $anTk = array_diff(array_keys($tk), array_keys($tkGiu));
        $anLp = array_diff(array_keys($lp), array_keys($lpGiu));

        $this->query('DELETE FROM `acc_shared_hidden` WHERE `garage_id` = ?', [$g]);
        $bay = date('Y-m-d H:i:s');
        foreach ($anTk as $id) $this->insert('acc_shared_hidden', ['kind' => self::TK, 'item_id' => (int) $id, 'garage_id' => $g, 'create_at' => $bay]);
        foreach ($anLp as $id) $this->insert('acc_shared_hidden', ['kind' => self::LP, 'item_id' => (int) $id, 'garage_id' => $g, 'create_at' => $bay]);

        return ['giuLai' => array_values(array_unique($giuLai)), 'anTk' => count($anTk), 'anLp' => count($anLp)];
    }

    /** Xoá công ty thì dọn luôn việc gán của nó. */
    public function xoaCuaCongTy($garaId){
        if ($this->coBang()) $this->query('DELETE FROM `acc_shared_hidden` WHERE `garage_id` = ?', [(int) $garaId]);
    }
}

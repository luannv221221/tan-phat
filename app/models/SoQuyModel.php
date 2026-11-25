<?php

use App\core\Model;

/**
 * KẾ TOÁN — SỔ QUỸ: tiền mặt và tiền gửi ngân hàng.
 *
 * Khách ghi 25/11/2026: "Thêm sổ quỹ (tiền mặt, tiền NH thì tự nhập)". Không
 * có nguồn dữ liệu ngân hàng — tiền ngân hàng nhập tay bằng phiếu thu / phiếu
 * chi mang loại phiếu định khoản vào 1121 (migration 000119).
 *
 * QUỸ NHẬN RA THEO TÀI KHOẢN TRÊN PHIẾU, không theo màn Phiếu thu / Phiếu chi:
 *     nợ 111* -> tiền VÀO quỹ tiền mặt     có 111* -> tiền RA khỏi quỹ tiền mặt
 *     nợ 112* -> tiền VÀO ngân hàng         có 112* -> tiền RA khỏi ngân hàng
 * Đọc `debit_code` / `credit_code` — bản chụp định khoản lúc lập phiếu — nên
 * sửa loại phiếu sau này không làm sổ cũ đổi số. Theo `loai` thì sai ngay khi
 * gara khai một loại phiếu thu không đụng tới quỹ (vd. bù trừ công nợ), hoặc
 * một phiếu chuyển tiền giữa hai quỹ (một phiếu, hai quỹ, hai chiều).
 *
 * Tài khoản con gara tự khai DƯỚI 111 / 112 mà mã không bắt đầu bằng 111 / 112
 * (vd. "VCB" dưới 112) cũng tính vào quỹ — xem maThem().
 *
 * TỒN ĐẦU KỲ = số dư ban đầu + (thu − chi) của mọi phiếu từ ngày số dư ban đầu
 * tới TRƯỚC ngày đầu kỳ. Phiếu trước ngày số dư ban đầu KHÔNG cộng: số dư ban
 * đầu là tiền thật đếm được hôm đó, đã gồm mọi thứ xảy ra trước nó.
 *
 * CHỈ PHIẾU HIỆU LỰC (`status` = 1) — phiếu không có nháp, 0 là đã huỷ; giống
 * mọi thẻ khác của Báo cáo kế toán.
 *
 * SỐ DƯ BAN ĐẦU nằm ở `garage_settings` (riêng từng gara, không đổi CSDL):
 *     so_quy_<quỹ>_so_du   số tiền
 *     so_quy_<quỹ>_ngay    ngày (Y-m-d)
 * Vì vậy bảng của model này là `garage_settings` + `$_theoGara`: bangGara(),
 * addNew(), updateById(), deleteById() tự khoá vào gara làm việc. Phiếu thì
 * đọc bằng truy vấn tự viết, có dkGara() — cờ `$_theoGara` không với tới đó.
 *
 * KHÔNG đọc qua GarageSettingsModel::val(): hàm đó rơi về Cấu hình chung khi
 * gara chưa khai — với tiền thì "chưa khai" phải là 0, không phải số của ai
 * khác. Và không ghi qua saveMany(): nó hỏi gara_hien_tai_id(), ở dòng lệnh
 * luôn ra gara tổng.
 */
class SoQuyModel extends Model {

    protected $_table    = 'garage_settings';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    const TIEN_MAT  = 'tien_mat';
    const NGAN_HANG = 'ngan_hang';

    /** Quỹ => tên + tài khoản gốc (Thông tư 133) */
    public static $quy = [
        self::TIEN_MAT  => ['ten' => 'Tiền mặt',           'tk' => '111'],
        self::NGAN_HANG => ['ten' => 'Tiền gửi ngân hàng', 'tk' => '112'],
    ];

    /** Quỹ hợp lệ, không thì quỹ tiền mặt — dùng cho tham số lọc trên URL */
    public static function quyHopLe($q){
        return is_string($q) && isset(self::$quy[$q]) ? $q : self::TIEN_MAT;
    }

    /** 'Y-m-d' hợp lệ thì trả lại chính nó, không thì null */
    public static function ngayHopLe($s){
        $s = trim((string) $s);
        $d = \DateTime::createFromFormat('!Y-m-d', $s);
        return ($d && $d->format('Y-m-d') === $s) ? $s : null;
    }

    private static function khoa($quy){
        return ['so_du' => 'so_quy_' . $quy . '_so_du', 'ngay' => 'so_quy_' . $quy . '_ngay'];
    }

    /**
     * Gara làm việc cụ thể (> 0), hoặc null.
     *
     * Dòng lệnh chưa ép gara (null) mà đọc / ghi thì bangGara() không lọc gì:
     * đọc ra số dư của một gara bất kỳ, ghi thì INSERT thiếu garage_id. Nên ở
     * đây null cũng là "không làm", y như 0.
     */
    private function garaCuThe(){
        $g = self::garaLoc();
        return !empty($g) ? (int) $g : null;
    }

    // ================= Số dư ban đầu =================

    /**
     * @return array ['so_du' => float, 'ngay' => 'Y-m-d' | null]
     *   ngay = null: gara chưa khai — sổ tính từ phiếu đầu tiên, số dư 0.
     */
    public function soDuBanDau($quy){
        $ra = ['so_du' => 0.0, 'ngay' => null];
        if (!isset(self::$quy[$quy]) || $this->garaCuThe() === null) return $ra;

        $k = self::khoa($quy);
        $map = [];
        foreach ((array) $this->bangGara()->select('`skey`, `svalue`')
                              ->whereIn('skey', [$k['so_du'], $k['ngay']])->get() as $r){
            $map[$r['skey']] = $r['svalue'];
        }

        /* Số dư mà không có ngày thì không biết cộng phiếu từ đâu — coi như
           chưa khai, thay vì đoán một ngày. */
        $ngay = isset($map[$k['ngay']]) ? self::ngayHopLe($map[$k['ngay']]) : null;
        if ($ngay === null) return $ra;

        return ['so_du' => isset($map[$k['so_du']]) ? round((float) $map[$k['so_du']], 2) : 0.0,
                'ngay'  => $ngay];
    }

    /**
     * Ghi số dư ban đầu của một quỹ cho GARA LÀM VIỆC.
     *
     * @param string      $ngay '' / null = BỎ số dư ban đầu (xoá dòng riêng)
     * @return bool false khi quỹ lạ, ngày sai dạng, hoặc không xác định được gara
     */
    public function luuSoDuBanDau($quy, $soDu, $ngay){
        if (!isset(self::$quy[$quy]) || $this->garaCuThe() === null) return false;
        $k = self::khoa($quy);

        if ($ngay === null || trim((string) $ngay) === ''){
            foreach ($k as $skey){
                $co = $this->bangGara()->select('`id`')->where('skey', '=', $skey)->first();
                if (!empty($co['id'])) $this->deleteById((int) $co['id']);
            }
            return true;
        }

        $ngay = self::ngayHopLe($ngay);
        if ($ngay === null || !is_numeric($soDu)) return false;

        $now = date('Y-m-d H:i:s');
        $ghi = [$k['so_du'] => (string) round((float) $soDu, 2), $k['ngay'] => $ngay];
        foreach ($ghi as $skey => $v){
            $co = $this->bangGara()->select('`id`')->where('skey', '=', $skey)->first();
            if (empty($co['id'])){
                $this->addNew(['skey' => $skey, 'svalue' => $v, 'update_at' => $now]);   // garage_id do addNew đặt
            } else {
                $this->updateById(['svalue' => $v, 'update_at' => $now], (int) $co['id']);
            }
        }
        return true;
    }

    // ================= Sổ =================

    /**
     * Sổ quỹ của gara làm việc trong [$tu, $den].
     *
     * @return array [
     *   'quy','ten','tk', 'tu','den',
     *   'so_du_ban_dau','ngay_ban_dau'   — như soDuBanDau()
     *   'tu_thuc'        — ngày sổ thực sự bắt đầu: $tu, hoặc ngày số dư ban
     *                      đầu nếu kỳ chồm qua trước ngày đó
     *   'truoc_ngay_dau' — true khi CẢ kỳ nằm trước ngày số dư ban đầu: không
     *                      biết quỹ lúc đó có bao nhiêu, không bày số đoán mò
     *   'dau_ky','tong_thu','tong_chi','cuoi_ky',
     *   'dong' => [[id, voucher_no, voucher_date, loai, dt_ten, ly_do, vtype_name,
     *               debit_code, credit_code, thu, chi, ton], ...]
     * ]
     */
    public function so($quy, $tu, $den){
        $quy = self::quyHopLe($quy);
        $tu  = self::ngayHopLe($tu)  ?: date('Y-m-01');
        $den = self::ngayHopLe($den) ?: date('Y-m-t');
        if ($tu > $den){ $t = $tu; $tu = $den; $den = $t; }

        $bd = $this->soDuBanDau($quy);
        $ra = [
            'quy' => $quy, 'ten' => self::$quy[$quy]['ten'], 'tk' => self::$quy[$quy]['tk'],
            'tu' => $tu, 'den' => $den, 'tu_thuc' => $tu,
            'so_du_ban_dau' => $bd['so_du'], 'ngay_ban_dau' => $bd['ngay'],
            'truoc_ngay_dau' => false,
            'dau_ky' => 0.0, 'tong_thu' => 0.0, 'tong_chi' => 0.0, 'cuoi_ky' => 0.0, 'dong' => [],
        ];

        if ($bd['ngay'] !== null && $den < $bd['ngay']){
            $ra['truoc_ngay_dau'] = true;
            return $ra;
        }
        if ($bd['ngay'] !== null && $tu < $bd['ngay']) $ra['tu_thuc'] = $bd['ngay'];

        $maThem = $this->maThem($quy);
        list($dkNo, $bdNo) = $this->dkTaiKhoan('v', 'debit_code', $quy, $maThem);
        list($dkCo, $bdCo) = $this->dkTaiKhoan('v', 'credit_code', $quy, $maThem);
        list($dkGara, $bdGara) = $this->dkGara('v');

        // --- Tồn đầu kỳ ---
        $sql = 'SELECT COALESCE(SUM(CASE WHEN ' . $dkNo . ' THEN `v`.`amount` ELSE 0 END), 0) AS `thu`,
                       COALESCE(SUM(CASE WHEN ' . $dkCo . ' THEN `v`.`amount` ELSE 0 END), 0) AS `chi`
                  FROM `acc_vouchers` `v`
                 WHERE ' . $dkGara . ' AND `v`.`status` = 1 AND `v`.`voucher_date` < ?';
        $bdSql = array_merge($bdNo, $bdCo, $bdGara, [$ra['tu_thuc']]);
        if ($bd['ngay'] !== null){ $sql .= ' AND `v`.`voucher_date` >= ?'; $bdSql[] = $bd['ngay']; }
        $truoc = $this->firstRaw($sql, $bdSql);

        $ton = round($bd['so_du'] + (float) $truoc['thu'] - (float) $truoc['chi'], 2);
        $ra['dau_ky'] = $ton;

        // --- Từng phiếu trong kỳ ---
        /* Cùng ngày thì theo thứ tự LẬP (id): tồn sau phiếu là tồn theo đúng
           trình tự tiền đổi tay mà người lập đã ghi. */
        $dong = $this->getRaw(
            'SELECT `v`.`id`, `v`.`voucher_no`, `v`.`voucher_date`, `v`.`loai`, `v`.`dt_ten`, `v`.`ly_do`,
                    `v`.`amount`, `v`.`debit_code`, `v`.`credit_code`, `t`.`name` AS `vtype_name`,
                    CASE WHEN ' . $dkNo . ' THEN 1 ELSE 0 END AS `vao`,
                    CASE WHEN ' . $dkCo . ' THEN 1 ELSE 0 END AS `ra`
               FROM `acc_vouchers` `v`
               LEFT JOIN `acc_voucher_types` `t` ON `t`.`id` = `v`.`vtype_id`
              WHERE ' . $dkGara . ' AND `v`.`status` = 1
                AND `v`.`voucher_date` BETWEEN ? AND ?
                AND (' . $dkNo . ' OR ' . $dkCo . ')
              ORDER BY `v`.`voucher_date` ASC, `v`.`id` ASC',
            array_merge($bdNo, $bdCo, $bdGara, [$ra['tu_thuc'], $den], $bdNo, $bdCo));

        foreach ((array) $dong as $r){
            $tien = (float) $r['amount'];
            $thu  = (int) $r['vao'] === 1 ? $tien : 0.0;
            $chi  = (int) $r['ra']  === 1 ? $tien : 0.0;
            $ton  = round($ton + $thu - $chi, 2);

            $ra['tong_thu'] += $thu;
            $ra['tong_chi'] += $chi;
            $ra['dong'][] = [
                'id' => (int) $r['id'], 'voucher_no' => $r['voucher_no'], 'voucher_date' => $r['voucher_date'],
                'loai' => $r['loai'], 'dt_ten' => $r['dt_ten'], 'ly_do' => $r['ly_do'],
                'vtype_name' => $r['vtype_name'], 'debit_code' => $r['debit_code'], 'credit_code' => $r['credit_code'],
                'thu' => $thu, 'chi' => $chi, 'ton' => $ton,
            ];
        }
        $ra['tong_thu'] = round($ra['tong_thu'], 2);
        $ra['tong_chi'] = round($ra['tong_chi'], 2);
        $ra['cuoi_ky']  = $ton;
        return $ra;
    }

    // ================= Tài khoản của quỹ =================

    /**
     * Điều kiện "cột $cot thuộc quỹ $quy": [sql, bindings].
     *     (`v`.`debit_code` LIKE '111%' OR `v`.`debit_code` IN ('TM01', ...))
     * $bi và $cot là tên cột viết cứng trong lớp này, không từ người dùng.
     * $them = maThem($quy), hỏi một lần cho cả sổ (nợ và có dùng chung).
     */
    private function dkTaiKhoan($bi, $cot, $quy, array $them){
        $c   = '`' . $bi . '`.`' . $cot . '`';
        $sql = $c . ' LIKE ?';
        $bd  = [self::$quy[$quy]['tk'] . '%'];
        if (!empty($them)){
            $sql .= ' OR ' . $c . ' IN (' . implode(', ', array_fill(0, count($them), '?')) . ')';
            $bd = array_merge($bd, $them);
        }
        return ['(' . $sql . ')', $bd];
    }

    /**
     * Mã tài khoản NẰM DƯỚI 111 / 112 trên cây mà KHÔNG bắt đầu bằng 111 / 112.
     *
     * Quy ước Việt Nam là tài khoản con nối dài mã cha (1121, 11211) — phần đó
     * LIKE '112%' đã bắt. Nhưng danh mục cho gara tự đặt mã: một gara khai "VCB"
     * dưới 112 thì mọi phiếu ngân hàng của họ lặng lẽ rơi khỏi sổ.
     *
     * Chỉ trong tầm thấy của gara làm việc (chung + riêng của nó).
     */
    private function maThem($quy){
        list($dk, $bd) = $this->dkChungVaRieng('a');
        $tk = [];
        foreach ((array) $this->getRaw('SELECT `a`.`id`, `a`.`code`, `a`.`parent_id`
                                          FROM `acc_accounts` `a` WHERE ' . $dk, $bd) as $r){
            $tk[(int) $r['id']] = ['code' => (string) $r['code'],
                                   'cha'  => $r['parent_id'] === null ? 0 : (int) $r['parent_id']];
        }

        $tienTo = self::$quy[$quy]['tk'];
        $ra = [];
        foreach ($tk as $id => $n){
            if (strpos($n['code'], $tienTo) === 0) continue;       // LIKE đã bắt
            /* Leo lên cha; đặt trần số bước phòng cây lỡ thành vòng (cây cũ
               trước khi màn tài khoản chặn vòng). */
            $cha = $n['cha'];
            for ($i = 0; $cha > 0 && isset($tk[$cha]) && $i < 50; $i++){
                if (strpos($tk[$cha]['code'], $tienTo) === 0){ $ra[] = $n['code']; break; }
                $cha = $tk[$cha]['cha'];
            }
        }
        return array_values(array_unique($ra));
    }
}

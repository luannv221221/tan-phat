<?php

use App\core\Model;

/**
 * KẾ TOÁN — PHIẾU THU / PHIẾU CHI.
 *
 * Một bảng cho cả hai, phân biệt bằng cột `loai` ('thu' | 'chi'). Hai màn hình
 * riêng nhưng dùng chung mọi thứ còn lại: cùng đối tượng, cùng cách đánh số,
 * cùng mẫu in, cùng cách vào báo cáo. Tách hai bảng là chép đôi tất cả rồi hai
 * bản lệch nhau.
 *
 * RIÊNG TỪNG GARA ($_theoGara) — khác hai danh mục tài khoản / loại phiếu vốn
 * dùng chung. Tiền thì không có phần chung: phiếu thu của gara Sài Gòn không
 * phải việc của gara Đà Nẵng.
 *
 * KHÔNG XOÁ ĐƯỢC, CHỈ HUỶ (`status` 1 → 0). Chứng từ tiền mà biến mất thì báo
 * cáo hôm qua khác hôm nay và không ai giải thích được. Phiếu huỷ vẫn nằm đó,
 * nhìn thấy được, và mọi báo cáo bỏ qua nó.
 *
 * BẢN CHỤP, KHÔNG PHẢI THAM CHIẾU: `dt_ten`, `debit_code`, `credit_code` ghi
 * ngay lúc lập. Sửa tên khách hay đổi định khoản của loại phiếu sang năm sau
 * KHÔNG được làm đổi phiếu đã in và đã ký.
 */
class AccVouchersModel extends Model {

    protected $_table   = 'acc_vouchers';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_theoGara = true;   // tiền thì không có phần dùng chung

    const THU = 'thu';
    const CHI = 'chi';

    /** Nguồn của ô "đối tượng" — tài liệu: khách hàng / nhà cung cấp / nhân viên */
    public static $doiTuong = [
        'partner' => 'Khách hàng / Nhà cung cấp',
        'user'    => 'Nhân viên',
        'khac'    => 'Khác (gõ tay)',
    ];

    /**
     * Danh sách phiếu, kèm tên loại phiếu.
     *
     * @param string $loai 'thu' | 'chi'
     * @param array  $loc  ['tu','den','vtype_id','keyword','status']
     */
    public function getLists($loai, array $loc = []){
        list($dkGara, $bd) = $this->dkGara('v');

        $sql = 'SELECT `v`.*, `t`.`name` AS `vtype_name`, `t`.`code` AS `vtype_code`
                  FROM `acc_vouchers` `v`
                  LEFT JOIN `acc_voucher_types` `t` ON `t`.`id` = `v`.`vtype_id`
                 WHERE ' . $dkGara . ' AND `v`.`loai` = ?';
        $bd[] = $loai;

        if (!empty($loc['tu'])){       $sql .= ' AND `v`.`voucher_date` >= ?'; $bd[] = $loc['tu']; }
        if (!empty($loc['den'])){      $sql .= ' AND `v`.`voucher_date` <= ?'; $bd[] = $loc['den']; }
        if (!empty($loc['vtype_id'])){ $sql .= ' AND `v`.`vtype_id` = ?';      $bd[] = (int) $loc['vtype_id']; }

        /* Mặc định CHỈ phiếu hiệu lực. Phiếu huỷ vẫn xem được, nhưng phải tự
           chọn — bày lẫn vào danh sách thường là người ta cộng nhầm. */
        if (isset($loc['status']) && $loc['status'] !== ''){
            $sql .= ' AND `v`.`status` = ?'; $bd[] = (int) $loc['status'];
        } else {
            $sql .= ' AND `v`.`status` = 1';
        }

        if (!empty($loc['keyword'])){
            $sql .= ' AND (`v`.`voucher_no` LIKE ? OR `v`.`dt_ten` LIKE ? OR `v`.`ly_do` LIKE ?)';
            $k = '%' . $loc['keyword'] . '%';
            $bd[] = $k; $bd[] = $k; $bd[] = $k;
        }

        return $this->getRaw($sql . ' ORDER BY `v`.`voucher_date` DESC, `v`.`id` DESC', $bd);
    }

    /** Chi tiết kèm tên loại phiếu — cho màn sửa và mẫu in */
    public function getDetail($id){
        list($dkGara, $bd) = $this->dkGara('v');
        return $this->firstRaw(
            'SELECT `v`.*, `t`.`name` AS `vtype_name`, `t`.`code` AS `vtype_code`
               FROM `acc_vouchers` `v`
               LEFT JOIN `acc_voucher_types` `t` ON `t`.`id` = `v`.`vtype_id`
              WHERE `v`.`id` = ? AND ' . $dkGara . ' LIMIT 1',
            array_merge([(int) $id], $bd));
    }

    /**
     * Số phiếu kế tiếp: PT-000001 / PC-000001, đánh RIÊNG trong mỗi gara.
     *
     * Đọc số ĐUÔI của số phiếu lớn nhất rồi +1, không đếm số dòng: huỷ một
     * phiếu giữa chừng mà đếm dòng thì số mới đè lên số cũ. Vẫn kiểm lại bằng
     * findByNo() phòng khi có số nhập tay chen vào.
     */
    public function nextNo($loai){
        $tienTo = $loai === self::CHI ? 'PC-' : 'PT-';
        $rows = $this->bangGara()->select('`voucher_no`')
                     ->where('loai', '=', $loai)
                     ->whereLike('voucher_no', $tienTo . '%')->get();

        $max = 0;
        foreach ((array) $rows as $r){
            if (preg_match('/(\d+)$/', $r['voucher_no'], $m)) $max = max($max, (int) $m[1]);
        }
        for ($i = $max + 1; $i < $max + 1000; $i++){
            $so = $tienTo . str_pad($i, 6, '0', STR_PAD_LEFT);
            if (empty($this->findByNo($so))) return $so;
        }
        return $tienTo . time();
    }

    public function findByNo($no){
        return $this->bangGara()->where('voucher_no', '=', $no)->first();
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        /* ÉP INT: lastInsertId() cua PDO tra ve CHUOI. Tra chuoi ra ngoai thi
           moi phep so sanh chat (in_array($id, $ids, true), $a === $b) deu
           truot ma khong bao gi — da mat mot luot do test vi dung cho nay. */
        return (int) $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    /** Huỷ / bỏ huỷ. KHÔNG có hàm xoá — xem ghi chú đầu lớp. */
    public function doiTrangThai($id, $moi){
        return $this->updateById(['status' => (int) $moi, 'update_at' => date('Y-m-d H:i:s')], $id);
    }

    // ===== Số liệu cho báo cáo =====

    /**
     * Tổng tiền theo loại trong một khoảng ngày. Chỉ phiếu HIỆU LỰC.
     * @return float
     */
    public function tong($loai, $tu, $den){
        list($dkGara, $bd) = $this->dkGara('acc_vouchers');
        $bd[] = $loai; $bd[] = $tu; $bd[] = $den;
        $r = $this->firstRaw(
            'SELECT COALESCE(SUM(`amount`), 0) AS `s` FROM `acc_vouchers`
              WHERE ' . $dkGara . ' AND `loai` = ? AND `status` = 1
                AND `voucher_date` BETWEEN ? AND ?', $bd);
        return (float) (isset($r['s']) ? $r['s'] : 0);
    }

    /**
     * Tổng tiền đã thu / đã chi theo TỪNG ĐỐI TƯỢNG là `partners`.
     * Dùng để tính công nợ: khách còn nợ = tiền hàng − đã thu.
     * @return array [partner_id => tổng tiền]
     */
    public function tongTheoDoiTuong($loai, $den = null){
        list($dkGara, $bd) = $this->dkGara('acc_vouchers');
        $bd[] = $loai;
        $sql = 'SELECT `partner_id`, COALESCE(SUM(`amount`), 0) AS `s`
                  FROM `acc_vouchers`
                 WHERE ' . $dkGara . ' AND `loai` = ? AND `status` = 1
                   AND `partner_id` IS NOT NULL';
        if ($den !== null){ $sql .= ' AND `voucher_date` <= ?'; $bd[] = $den; }

        $map = [];
        foreach ((array) $this->getRaw($sql . ' GROUP BY `partner_id`', $bd) as $r){
            $map[(int) $r['partner_id']] = (float) $r['s'];
        }
        return $map;
    }

    /**
     * Tổng theo NGÀY / THÁNG / NĂM — cho báo cáo "tổng thu theo ngày, tháng, năm".
     *
     * @param string $theo 'ngay' | 'thang' | 'nam'
     * @return array [['ky' => '2026-10-14', 's' => 1200000], ...]
     */
    public function tongTheoKy($loai, $theo, $tu, $den){
        $dinhDang = [
            'ngay'  => '%Y-%m-%d',
            'thang' => '%Y-%m',
            'nam'   => '%Y',
        ];
        $fmt = isset($dinhDang[$theo]) ? $dinhDang[$theo] : $dinhDang['thang'];

        list($dkGara, $bd) = $this->dkGara('acc_vouchers');
        /* DATE_FORMAT với chuỗi định dạng lấy từ BẢNG TRẮNG ở trên, không phải
           từ tham số người dùng — $theo chỉ dùng để tra bảng đó. */
        array_unshift($bd, $fmt);
        $bd[] = $loai; $bd[] = $tu; $bd[] = $den;

        return $this->getRaw(
            'SELECT DATE_FORMAT(`voucher_date`, ?) AS `ky`,
                    COALESCE(SUM(`amount`), 0) AS `s`, COUNT(*) AS `n`
               FROM `acc_vouchers`
              WHERE ' . $dkGara . ' AND `loai` = ? AND `status` = 1
                AND `voucher_date` BETWEEN ? AND ?
              GROUP BY `ky` ORDER BY `ky` ASC', $bd);
    }
}

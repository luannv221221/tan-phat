<?php

use App\core\Model;

/**
 * KT-4 — Đối tượng: khách hàng + nhà cung cấp CỦA TỪNG GARA.
 *
 * Gara độc lập (22/09/2026): mỗi gara một danh sách khách / NCC riêng, không
 * thấy của nhau ($_theoGara). Màn CSKH › Khách hàng và màn Bán hàng › Đối
 * tượng cùng đọc / sửa bảng này — một khách chỉ nằm MỘT chỗ.
 */
class PartnersModel extends Model {

    protected $_table    = 'partners';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    public static $types = [
        'customer' => 'Khách hàng',
        'supplier' => 'Nhà cung cấp',
        'both'     => 'Cả hai',
    ];

    /**
     * Danh sách đối tượng, có lọc.
     *
     * @param array $loc [
     *   'q'      => tìm theo mã / tên / SĐT / MST,
     *   'type'   => 'customer' | 'supplier' | 'both' | '',
     *   'group'  => id nhóm khách | 'none' (chưa xếp nhóm) | '',
     *   'status' => '1' | '0' | '',
     * ]
     * Không truyền gì = như cũ, lấy hết (giữ tương thích cho nơi gọi cũ).
     *
     * LOẠI "CẢ HAI" PHẢI HIỆN Ở CẢ HAI BỘ LỌC. Lọc "Khách hàng" mà so bằng
     * `type = 'customer'` thì đối tác vừa mua vừa bán (type = 'both') biến mất
     * khỏi danh sách khách — và cũng biến mất khỏi danh sách NCC. Họ không
     * thuộc danh sách nào dù là cả hai. Nên "Khách hàng" = customer + both,
     * "Nhà cung cấp" = supplier + both; chỉ chọn đích danh "Cả hai" mới ra
     * riêng nhóm đó.
     */
    public function getLists(array $loc = []){
        $q = $this->bangGara();

        $tu = isset($loc['q']) ? trim((string) $loc['q']) : '';
        if ($tu !== ''){
            $q = $q->where(function($sub) use ($tu){
                $like = '%' . $tu . '%';
                $sub->whereLike('code', $like);
                $sub->whereOrLike('name', $like);
                $sub->whereOrLike('phone', $like);
                $sub->whereOrLike('tax_code', $like);
            });
        }

        $loai = isset($loc['type']) ? (string) $loc['type'] : '';
        if ($loai === 'customer')     $q = $q->whereIn('type', ['customer', 'both']);
        elseif ($loai === 'supplier') $q = $q->whereIn('type', ['supplier', 'both']);
        elseif ($loai === 'both')     $q = $q->where('type', '=', 'both');

        $nhom = isset($loc['group']) ? (string) $loc['group'] : '';
        if ($nhom === 'none')            $q = $q->whereNull('group_id');
        elseif ((int) $nhom > 0)         $q = $q->where('group_id', '=', (int) $nhom);

        $tt = isset($loc['status']) ? (string) $loc['status'] : '';
        if ($tt === '1' || $tt === '0')  $q = $q->where('status', '=', (int) $tt);

        return $q->orderBy('sort_order', 'ASC')
                 ->orderBy('name', 'ASC')
                 ->get();
    }

    /**
     * Khách của gara cho màn CSKH › Khách hàng.
     *
     * Tìm được theo tên, mã, SĐT, email, và theo BIỂN SỐ / SỐ KHUNG của xe —
     * khách quay lại thường chỉ đọc biển số. Biển số so trên cột chuẩn hoá và
     * chuẩn hoá luôn từ khoá (xem VehiclesModel::getLists).
     *
     * @param array $loc    ['q' => ..., 'status' => '1' | '0' | '', 'group' => id | '']
     * @param int   $limit  0 = không giới hạn
     */
    public function khachHang(array $loc = [], $limit = 0, $offset = 0){
        list($where, $b) = $this->dkKhachHang($loc);
        $sql = "SELECT p.*, g.`name` AS nhom_ten
                  FROM `partners` p
                  LEFT JOIN `customer_groups` g ON g.`id` = p.`group_id`
                 WHERE $where ORDER BY p.`id` DESC";
        if ((int) $limit > 0) $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset);
        return $this->getRaw($sql, $b);
    }

    /** Số khách khớp bộ lọc — cho phân trang của màn Khách hàng */
    public function demKhachHang(array $loc = []){
        list($where, $b) = $this->dkKhachHang($loc);
        $r = $this->firstRaw("SELECT COUNT(*) AS c FROM `partners` p WHERE $where", $b);
        return !empty($r['c']) ? (int) $r['c'] : 0;
    }

    /** Mệnh đề WHERE dùng chung cho khachHang / demKhachHang. Giá trị luôn qua placeholder. */
    private function dkKhachHang(array $loc){
        list($dk, $b) = $this->dkGara('p');
        $where = "$dk AND p.`type` IN ('customer', 'both')";

        $tu = isset($loc['q']) ? trim((string) $loc['q']) : '';
        if ($tu !== ''){
            $like  = '%' . $tu . '%';
            $chuan = chuan_hoa_bien_so($tu);
            $where .= " AND (p.`name` LIKE ? OR p.`code` LIKE ? OR p.`phone` LIKE ? OR p.`email` LIKE ?
                         OR EXISTS (SELECT 1 FROM `vehicles` v WHERE v.`partner_id` = p.`id`
                                     AND (v.`bien_so_chuan` LIKE ? OR v.`so_khung` LIKE ?)))";
            array_push($b, $like, $like, $like, $like,
                       $chuan === '' ? "\x00" : '%' . $chuan . '%', $like);
        }
        $tt = isset($loc['status']) ? (string) $loc['status'] : '';
        if ($tt === '1' || $tt === '0'){ $where .= " AND p.`status` = ?"; $b[] = (int) $tt; }
        if (!empty($loc['group']) && (int) $loc['group'] > 0){ $where .= " AND p.`group_id` = ?"; $b[] = (int) $loc['group']; }
        return [$where, $b];
    }

    /** Tổng số đối tượng, không lọc — để hiện "đang xem 3 / 6" */
    public function demTatCa(){
        $r = $this->bangGara()->select('COUNT(*) AS c')->first();
        return !empty($r['c']) ? (int) $r['c'] : 0;
    }

    /** Đang hoạt động — cho dropdown chọn đối tượng trên phiếu */
    public function getActive(){
        return $this->bangGara()
                    ->where('status', '=', 1)
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** [partner_id => % chiết khấu nhóm KH] — cho auto-điền chiết khấu dòng */
    public function groupDiscountMap(){
        $rows = $this->bangGara()
            ->select('`partners`.`id`, `customer_groups`.`discount_percent`')
            ->joinOn('customer_groups', 'partners.group_id', 'customer_groups.id')
            ->get();
        $map = [];
        foreach ($rows ?: [] as $r){ $map[(int) $r['id']] = (float) $r['discount_percent']; }
        return $map;
    }

    /** Theo mã — trong gara làm việc (mã chỉ duy nhất trong một gara) */
    public function findByCode($code){
        return $this->bangGara()->where('code', '=', $code)->first();
    }

    /** Khách đầu tiên dùng số điện thoại này — để cảnh báo tạo trùng người */
    public function findByPhone($phone){
        $phone = trim((string) $phone);
        if ($phone === '') return [];
        return $this->bangGara()->where('phone', '=', $phone)->first();
    }

    /** Mã kế tiếp theo tiền tố trong gara làm việc: KH-0001, KH-0002... */
    public function nextCode($tienTo = 'KH-'){
        $n = 0;
        foreach ((array) $this->bangGara()->select('`code`')->whereLike('code', $tienTo . '%')->get() as $r){
            if (preg_match('/(\d+)$/', $r['code'], $m)) $n = max($n, (int) $m[1]);
        }
        return $tienTo . str_pad($n + 1, 4, '0', STR_PAD_LEFT);
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
        // acc_vouchers.partner_id ON DELETE SET NULL -> xoá an toàn (phiếu giữ partner_name).
        return $this->deleteById($id);
    }
}

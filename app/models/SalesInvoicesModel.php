<?php

use App\core\Model;

/**
 * BÁN HÀNG — Hoá đơn bán. Ghi sổ -> doanh thu + thuế + giá vốn + trừ tồn (KT-6).
 * RIÊNG từng gara; số hoá đơn và số HĐĐT đánh riêng trong mỗi gara.
 */
class SalesInvoicesModel extends Model {

    protected $_table   = 'sales_invoices';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_theoGara = true;

    public function getLists($status = '', $from = '', $to = ''){
        $q = $this->bangGara()
            ->select('`sales_invoices`.*, `partners`.`name` AS customer_full, '
                   . '`warehouses`.`name` AS warehouse_name')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->joinOn('warehouses', 'sales_invoices.warehouse_id', 'warehouses.id');

        if ($status !== '' && ($status === '0' || $status === '1')){
            $q = $q->where('sales_invoices.status', '=', (int) $status);
        }
        if ($from !== '') $q = $q->where('sales_invoices.invoice_date', '>=', $from);
        if ($to !== '')   $q = $q->where('sales_invoices.invoice_date', '<=', $to);

        return $q->orderBy('sales_invoices.invoice_date', 'DESC')
                 ->orderBy('sales_invoices.id', 'DESC')->get();
    }

    /**
     * Chi tiết hoá đơn — PHẢI join giống getLists().
     *
     * Bản cũ dùng getFirst() (SELECT * thuần, không join), nên trang chi tiết
     * thiếu `warehouse_name` và `customer_full`: ô "Kho xuất" trống kèm
     * PHP Warning "Undefined array key warehouse_name".
     *
     * leftJoin cho `warehouses` (khác getLists dùng join thường): hoá đơn cũ có
     * thể chưa gán kho, join thường sẽ làm mất luôn dòng và trang báo
     * "Không tìm thấy hoá đơn" — sai còn nặng hơn.
     */
    public function getDetail($id){
        return $this->bangGara()
            ->select('`sales_invoices`.*, `partners`.`name` AS customer_full, '
                   . '`warehouses`.`name` AS warehouse_name')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->leftJoinOn('warehouses', 'sales_invoices.warehouse_id', 'warehouses.id')
            ->where('sales_invoices.id', '=', (int) $id)
            ->first();
    }

    /**
     * MÃ LINK THANH TOÁN của hoá đơn (18/11/2026 — nút "Gửi" ở danh sách hoá đơn).
     *
     * 32 ký tự hex ngẫu nhiên (random_bytes, 128 bit), KHÔNG phải id: id tăng
     * dần, sửa /thanh-toan/112 thành /113 là xem được hoá đơn người khác.
     *
     * Sinh lần đầu được hỏi, GIỮ NGUYÊN về sau — link đã gửi cho khách không chết.
     * Chỉ trả cho hoá đơn thuộc GARA ĐANG LÀM VIỆC (bangGara()): gara khác hỏi
     * thì null, không lấy được link của nhau. Ghi kèm `pay_token IS NULL` để hai
     * lần bấm cùng lúc không đè mã của nhau (lần sau đọc lại mã lần trước).
     *
     * @return string|null
     */
    public function maThanhToan($id){
        $r = $this->bangGara()->select('`sales_invoices`.`id`, `sales_invoices`.`garage_id`, `sales_invoices`.`pay_token`')
            ->where('sales_invoices.id', '=', (int) $id)->first();
        if (empty($r)) return null;
        if (!empty($r['pay_token'])) return (string) $r['pay_token'];

        $this->query('UPDATE `sales_invoices` SET `pay_token` = ? WHERE `id` = ? AND `garage_id` = ? AND `pay_token` IS NULL',
                     [bin2hex(random_bytes(16)), (int) $r['id'], (int) $r['garage_id']]);
        $lai = $this->firstRaw('SELECT `pay_token` FROM `sales_invoices` WHERE `id` = ?', [(int) $r['id']]);
        return !empty($lai['pay_token']) ? (string) $lai['pay_token'] : null;
    }

    /**
     * Hoá đơn theo MÃ LINK THANH TOÁN — cho trang công khai /thanh-toan/<mã>.
     *
     * KHÔNG lọc theo gara đang mở (truy vấn thô): khách mở link trên tên miền nào
     * cũng phải ra đúng hoá đơn, và trang đó đọc tài khoản theo `garage_id` CỦA
     * HOÁ ĐƠN — tiền phải về gara lập hoá đơn. Mã sai hình dạng thì trả null
     * luôn, không chạm CSDL.
     *
     * Chỉ lấy những cột trang công khai cần: không tên khách, không số điện
     * thoại, không dòng hàng — link có thể bị chuyển tiếp cho người khác.
     */
    public function theoMaThanhToan($ma){
        $ma = (string) $ma;
        if (!preg_match('~^[a-f0-9]{32}$~', $ma)) return null;
        $r = $this->firstRaw('SELECT `id`, `garage_id`, `invoice_no`, `invoice_date`, `total_amount`, `status`, `bien_so`
                                FROM `sales_invoices` WHERE `pay_token` = ? LIMIT 1', [$ma]);
        return !empty($r) ? $r : null;
    }

    /**
     * HOÁ ĐƠN ĐÃ SINH RA TỪ MỘT BÁO GIÁ — để chặn chuyển hai lần.
     *
     * Không có chốt này thì bấm "Chuyển thành hoá đơn" hai lần ra HAI hoá đơn
     * cùng nội dung. Ghi sổ cả hai thì tồn kho bị trừ hai lần và doanh thu đếm
     * đôi — sai lệch không ai nhìn ra cho tới lúc kiểm kho.
     *
     * Lấy bản CŨ NHẤT (`id` ASC): dữ liệu cũ có thể đã lỡ có hai cái, và cái
     * đầu mới là cái người ta đang dùng.
     */
    public function theoBaoGia($quotationId){
        $quotationId = (int) $quotationId;
        if ($quotationId <= 0) return null;

        $r = $this->bangGara()
            ->select('`id`, `invoice_no`, `status`, `quotation_id`')
            ->where('quotation_id', '=', $quotationId)
            ->orderBy('id', 'ASC')->first();
        return empty($r) ? null : $r;
    }

    /**
     * Cùng việc trên nhưng cho CẢ MỘT TRANG danh sách báo giá — một truy vấn
     * thay vì một truy vấn mỗi dòng.
     *
     * @return array [quotation_id => ['id' =>, 'invoice_no' =>, 'status' =>]]
     */
    public function theoNhieuBaoGia(array $quotationIds){
        $ids = [];
        foreach ($quotationIds as $v){ $v = (int) $v; if ($v > 0) $ids[$v] = $v; }
        if (empty($ids)) return [];

        $rows = $this->bangGara()
            ->select('`id`, `invoice_no`, `status`, `quotation_id`')
            ->whereIn('quotation_id', array_values($ids))
            ->orderBy('id', 'ASC')->get();

        $ra = [];
        foreach ((array) $rows as $r){
            $k = (int) $r['quotation_id'];
            if (!isset($ra[$k])) $ra[$k] = $r;   // giữ bản cũ nhất
        }
        return $ra;
    }

    /**
     * Hoá đơn cũ để chép lại dòng hàng.
     *
     * Hoá đơn của CHÍNH khách đang chọn được đẩy lên đầu (`uu_tien`): gara hay
     * gặp khách quay lại làm đúng gói cũ, tìm thủ công giữa hàng trăm số hoá
     * đơn thì thà gõ tay còn nhanh hơn.
     *
     * KHÔNG lọc theo trạng thái: hoá đơn nháp cũng chép được. Người ta hay lập
     * nháp một cái mẫu rồi nhân bản ra nhiều lần.
     */
    public function danhSachDeChep($customerId = 0, $limit = 50){
        $customerId = (int) $customerId;

        return $this->bangGara()
            ->select('`sales_invoices`.`id`, `sales_invoices`.`invoice_no`, '
                   . '`sales_invoices`.`invoice_date`, `sales_invoices`.`total_amount`, '
                   . '`sales_invoices`.`status`, '
                   . 'COALESCE(`partners`.`name`, `sales_invoices`.`customer_name`, \'\') AS khach, '
                   . '(SELECT COUNT(*) FROM `sales_invoice_items` si WHERE si.`invoice_id` = `sales_invoices`.`id`) AS so_dong, '
                   . 'CASE WHEN `sales_invoices`.`customer_id` = ' . $customerId . ' THEN 0 ELSE 1 END AS uu_tien')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->orderBy('uu_tien', 'ASC')
            ->orderBy('sales_invoices.invoice_date', 'DESC')
            ->orderBy('sales_invoices.id', 'DESC')
            ->limit((int) $limit)
            ->get();
    }

    public function nextNo(){
        $row = $this->bangGara()->select('`invoice_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['invoice_no'], $m)){ $n = (int) $m[1]; }
        return 'HD-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    /** Số HĐĐT kế tiếp (max số đã phát hành + 1, đệm 8 chữ số theo TT78) */
    public function nextEinvoiceNo(){
        $row = $this->bangGara()
            ->select('`einvoice_no`')
            ->whereNotNull('einvoice_no')
            ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)/', $row['einvoice_no'], $m)){ $n = (int) $m[1]; }
        return str_pad($n + 1, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Số / tổng tiền hoá đơn trong kỳ theo trạng thái (0 nháp, 1 đã ghi sổ), của
     * GARA làm việc — [status => ['count' => n, 'sum' => tiền]] cho trang Tổng
     * quan. $to là mốc KHÔNG tính (đầu ngày hôm sau).
     */
    public function thongKeKy($from, $to){
        $rows = $this->bangGara()->select('`status`, COUNT(*) AS c, COALESCE(SUM(`total_amount`), 0) AS s')
            ->where('invoice_date', '>=', substr($from, 0, 10))->where('invoice_date', '<', substr($to, 0, 10))
            ->groupBy('status')->get();
        $out = [];
        foreach ((array) $rows as $r) $out[(string) $r['status']] = ['count' => (int) $r['c'], 'sum' => (float) $r['s']];
        return $out;
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

    /**
     * Báo cáo bán hàng: hoá đơn ĐÃ GHI SỔ trong kỳ, kèm tên KH + người lập.
     * Controller tự gộp theo khách / nhân viên.
     */
    public function getPostedForReport($from = '', $to = ''){
        $q = $this->bangGara()
            ->select('`sales_invoices`.*, `partners`.`name` AS customer_full, `users`.`name` AS staff_name')
            ->leftJoinOn('partners', 'sales_invoices.customer_id', 'partners.id')
            ->leftJoinOn('users', 'sales_invoices.created_by', 'users.id')
            ->where('sales_invoices.status', '=', 1);

        if ($from !== '') $q = $q->where('sales_invoices.invoice_date', '>=', $from);
        if ($to !== '')   $q = $q->where('sales_invoices.invoice_date', '<=', $to);

        return $q->orderBy('sales_invoices.invoice_date', 'ASC')
                 ->orderBy('sales_invoices.id', 'ASC')->get();
    }

    /**
     * TIỀN HÀNG ĐÃ BÁN cho từng khách, để tính "khách còn nợ gara bao nhiêu"
     * (báo cáo kế toán, tài liệu 14/10/2026).
     *
     * CHỈ HOÁ ĐƠN ĐÃ GHI SỔ (`status` = 1): hoá đơn nháp chưa phải là khoản
     * phải thu — tính vào thì gara đi đòi một món khách chưa từng nợ.
     *
     * Dùng `total_amount` (đã gồm thuế) vì đó là số khách phải trả, cũng là số
     * mà phiếu thu ghi nhận — hai vế phải cùng đơn vị thì trừ nhau mới ra đúng.
     *
     * Khách vãng lai (`customer_id` NULL) bị bỏ qua: không có ai để đi đòi, và
     * gộp họ vào một dòng "không rõ" chỉ làm bảng công nợ nhiễu.
     *
     * @return array [partner_id => ['ten' => , 'tien' => , 'so_hd' => ]]
     */
    /**
     * HÀNG BÁN CHẠY — khách ghi 25/11/2026 cạnh "Báo cáo hàng tồn lâu": "Báo cáo
     * hàng bán chạy". Gộp dòng hàng của hoá đơn ĐÃ GHI SỔ (nháp chưa phải là đã
     * bán) của gara đang mở, theo mặt hàng, xếp số lượng giảm dần.
     *
     * Tên / mã lấy từ bảng hàng hoá; dòng có mặt hàng đã xoá vẫn tính, mang
     * tên "(hàng đã xoá)" — bỏ đi là tổng doanh thu bảng này lệch với báo cáo.
     *
     * @return array [['part_id','ma','ten','loai','so_luong','doanh_thu','gia_von','lai_gop','so_hd'], ...]
     */
    public function hangBanChay($from = '', $to = '', $gioiHan = 20){
        list($dkGara, $bd) = $this->dkGara('s');
        $sql = 'SELECT i.`part_id`, p.`code` AS `ma`, p.`name` AS `ten`, p.`item_type` AS `loai`,
                       SUM(i.`quantity`) AS `so_luong`, SUM(i.`amount`) AS `doanh_thu`,
                       SUM(i.`cost_amount`) AS `gia_von`, COUNT(DISTINCT i.`invoice_id`) AS `so_hd`
                  FROM `sales_invoice_items` i
                  JOIN `sales_invoices` s ON s.`id` = i.`invoice_id`
                  LEFT JOIN `parts` p ON p.`id` = i.`part_id`
                 WHERE ' . $dkGara . ' AND s.`status` = 1';
        if ($from !== '') { $sql .= ' AND s.`invoice_date` >= ?'; $bd[] = $from; }
        if ($to !== '')   { $sql .= ' AND s.`invoice_date` <= ?'; $bd[] = $to; }
        $sql .= ' GROUP BY i.`part_id`, p.`code`, p.`name`, p.`item_type`
                  ORDER BY `so_luong` DESC, `doanh_thu` DESC
                  LIMIT ' . max(1, (int) $gioiHan);

        $ra = [];
        foreach ((array) $this->getRaw($sql, $bd) as $r){
            $dt = (float) $r['doanh_thu']; $gv = (float) $r['gia_von'];
            $ra[] = [
                'part_id'   => (int) $r['part_id'],
                'ma'        => (string) $r['ma'],
                'ten'       => $r['ten'] !== null && $r['ten'] !== '' ? $r['ten'] : '(hàng đã xoá)',
                'loai'      => (string) $r['loai'],
                'so_luong'  => (float) $r['so_luong'],
                'doanh_thu' => $dt,
                'gia_von'   => $gv,
                'lai_gop'   => $dt - $gv,
                'so_hd'     => (int) $r['so_hd'],
            ];
        }
        return $ra;
    }

    public function tienBanTheoKhach($den = null){
        list($dkGara, $bd) = $this->dkGara('sales_invoices');
        $sql = 'SELECT `sales_invoices`.`customer_id` AS `pid`,
                       `partners`.`name` AS `ten`, `partners`.`code` AS `ma`,
                       `partners`.`phone` AS `dt`,
                       COALESCE(SUM(`sales_invoices`.`total_amount`), 0) AS `tien`,
                       COUNT(*) AS `so_hd`
                  FROM `sales_invoices`
                  JOIN `partners` ON `partners`.`id` = `sales_invoices`.`customer_id`
                 WHERE ' . $dkGara . ' AND `sales_invoices`.`status` = 1';
        if ($den !== null){ $sql .= ' AND `sales_invoices`.`invoice_date` <= ?'; $bd[] = $den; }

        $ra = [];
        foreach ((array) $this->getRaw($sql . ' GROUP BY `sales_invoices`.`customer_id`', $bd) as $r){
            $ra[(int) $r['pid']] = ['ten' => $r['ten'], 'ma' => $r['ma'], 'dt' => $r['dt'],
                                    'tien' => (float) $r['tien'], 'so_hd' => (int) $r['so_hd']];
        }
        return $ra;
    }
}

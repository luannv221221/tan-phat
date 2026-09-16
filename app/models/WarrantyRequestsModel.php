<?php

use App\core\Model;

/**
 * CSKH — Phiếu bảo hành / bảo trì (chung một bảng, phân biệt bằng cột `loai`).
 * Luồng: received (tiếp nhận) -> processing (đang xử lý) -> done / cancelled.
 *
 *   Bảo hành  hàng hoặc xe HỎNG, còn trong hạn bảo hành — khách tới vì có lỗi.
 *   Bảo trì   bảo dưỡng ĐỊNH KỲ — không có gì hỏng, tới hạn thì làm.
 *
 * Chung bảng vì chung mọi thứ còn lại: khách, xe, thiết bị, trạng thái, KTV,
 * biên bản giao nhận. Chỉ khác số phiếu (BH- / BT-) và việc phiếu bảo trì
 * xong thì sinh ra lời nhắc lần kế tiếp (màn Nhắc bảo trì).
 */
class WarrantyRequestsModel extends Model {

    protected $_table   = 'warranty_requests';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $statuses = [
        'received'   => 'Tiếp nhận',
        'processing' => 'Đang xử lý',
        'done'       => 'Hoàn tất',
        'cancelled'  => 'Đã huỷ',
    ];

    public static $loais = [
        'bao_hanh' => 'Bảo hành',
        'bao_tri'  => 'Bảo trì',
    ];

    /** Tiền tố số phiếu — hai dãy số riêng */
    public static $tienTo = [
        'bao_hanh' => 'BH',
        'bao_tri'  => 'BT',
    ];

    /** Loại hợp lệ, hoặc $macDinh — giá trị lạ trên URL / form không lọt vào CSDL */
    public static function loaiHopLe($v, $macDinh = 'bao_hanh'){
        return (is_string($v) && isset(self::$loais[$v])) ? $v : $macDinh;
    }

    /**
     * "Cùng một đối tượng bảo trì" — để lần bảo trì MỚI thế chỗ lần cũ trong
     * danh sách nhắc. Xe thì theo biển số (đã chuẩn hoá); không có xe thì theo
     * serial; không có cả hai thì theo khách + tên hàng.
     */
    public static function khoaDoiTuong(array $r){
        if (!empty($r['bien_so_chuan'])) return 'xe:' . $r['bien_so_chuan'];
        if (!empty($r['serial_no']))     return 'sn:' . mb_strtoupper(trim($r['serial_no']));
        $kh = !empty($r['partner_id']) ? 'p' . (int) $r['partner_id']
            : mb_strtolower(trim((string) (!empty($r['phone']) ? $r['phone'] : ($r['customer_name'] ?? ''))));
        $hg = !empty($r['part_id']) ? 'h' . (int) $r['part_id']
            : mb_strtolower(trim((string) ($r['product_name'] ?? '')));
        return 'kh:' . $kh . '|' . $hg;
    }

    public function getLists($status = '', $from = '', $to = '', $keyword = '', $loai = ''){
        $q = $this->table($this->_table)
            ->select('`warranty_requests`.*, `partners`.`name` AS partner_full')
            ->leftJoinOn('partners', 'warranty_requests.partner_id', 'partners.id');

        if ($status !== '' && isset(self::$statuses[$status])) $q = $q->where('warranty_requests.status', '=', $status);
        if ($loai !== '' && isset(self::$loais[$loai]))       $q = $q->where('warranty_requests.loai', '=', $loai);
        if ($from !== '') $q = $q->where('warranty_requests.received_date', '>=', $from);
        if ($to !== '')   $q = $q->where('warranty_requests.received_date', '<=', $to);
        if ($keyword !== ''){
            /* Tra thêm được theo BIỂN SỐ — thứ khách quay lại hay đọc nhất.
               So trên cột đã chuẩn hoá và chuẩn hoá luôn từ khoá, nên gõ
               "30A-123.45", "30a12345" hay "30A 123 45" đều ra cùng một xe.
               So thẳng cột gốc thì đúng xe đó mà máy báo không tìm thấy.

               Từ khoá không có chữ/số nào (người dùng gõ "---") thì chuẩn hoá
               ra chuỗi rỗng, mà LIKE '%%' khớp MỌI dòng — phải chặn, nếu không
               tìm một dấu gạch ra cả bảng. */
            $chuan = chuan_hoa_bien_so($keyword);
            $q = $q->where(function($sub) use ($keyword, $chuan){
                $like = '%' . $keyword . '%';
                $sub->whereLike('warranty_requests.request_no', $like);
                $sub->whereOrLike('warranty_requests.customer_name', $like);
                $sub->whereOrLike('warranty_requests.phone', $like);
                $sub->whereOrLike('warranty_requests.serial_no', $like);
                $sub->whereOrLike('warranty_requests.bien_so_chuan',
                                  $chuan === '' ? "\x00" : '%' . $chuan . '%');
            });
        }
        return $q->orderBy('warranty_requests.received_date', 'DESC')
                 ->orderBy('warranty_requests.id', 'DESC')->get();
    }

    /**
     * Lịch: phiếu CHƯA hoàn tất / huỷ, xếp theo ngày hẹn (gần nhất trước).
     *
     * $khoang: '' tất cả | qua_han | hom_nay | 7_ngay (hôm nay tới 7 ngày sau) | chua_hen
     * Phiếu chưa hẹn ngày luôn nằm CUỐI: MySQL xếp NULL lên đầu khi ASC, để
     * nguyên thì mấy phiếu chưa có hẹn chiếm hết đầu danh sách.
     */
    public function getSchedule($loai = '', $khoang = '', $homNay = null){
        $homNay = $homNay ?: date('Y-m-d');
        $q = $this->table($this->_table)
            ->select('`warranty_requests`.*, `partners`.`name` AS partner_full')
            ->leftJoinOn('partners', 'warranty_requests.partner_id', 'partners.id')
            ->whereIn('warranty_requests.status', ['received', 'processing']);

        if ($loai !== '' && isset(self::$loais[$loai])) $q = $q->where('warranty_requests.loai', '=', $loai);

        if ($khoang === 'qua_han'){
            $q = $q->whereNotNull('warranty_requests.appointment_date')
                   ->where('warranty_requests.appointment_date', '<', $homNay);
        } elseif ($khoang === 'hom_nay'){
            $q = $q->where('warranty_requests.appointment_date', '=', $homNay);
        } elseif ($khoang === '7_ngay'){
            $q = $q->where('warranty_requests.appointment_date', '>=', $homNay)
                   ->where('warranty_requests.appointment_date', '<=', date('Y-m-d', strtotime($homNay . ' +7 days')));
        } elseif ($khoang === 'chua_hen'){
            $q = $q->whereNull('warranty_requests.appointment_date');
        }

        $rows = (array) $q->orderBy('warranty_requests.appointment_date', 'ASC')
                          ->orderBy('warranty_requests.received_date', 'ASC')->get();

        $coHen = []; $chuaHen = [];
        foreach ($rows as $r){
            if (empty($r['appointment_date'])) $chuaHen[] = $r; else $coHen[] = $r;
        }
        return array_merge($coHen, $chuaHen);
    }

    /** Đếm phiếu theo trạng thái trong kỳ — cho báo cáo CSKH */
    public function countByStatus($from = '', $to = ''){
        $q = $this->table($this->_table)->select('`status`, COUNT(*) AS total, SUM(`fee`) AS total_fee');
        if ($from !== '') $q = $q->where('received_date', '>=', $from);
        if ($to !== '')   $q = $q->where('received_date', '<=', $to);
        $rows = $q->groupBy('status')->get();
        $out = [];
        foreach ($rows ?: [] as $r){ $out[$r['status']] = ['total' => (int) $r['total'], 'fee' => (float) $r['total_fee']]; }
        return $out;
    }

    /**
     * Phiếu BẢO TRÌ đã hoàn tất — nguồn tính nhắc bảo trì. Mới nhất trước, để
     * bên gọi lấy dòng đầu tiên của mỗi xe là có ngay LẦN CUỐI.
     *
     * Phiếu bảo hành KHÔNG vào đây: sửa một cái đèn pha hỏng không phải là
     * bảo dưỡng xe.
     */
    public function baoTriDaXong(){
        return $this->table($this->_table)
            ->select('`warranty_requests`.*, `partners`.`name` AS partner_full, `partners`.`phone` AS partner_phone')
            ->leftJoinOn('partners', 'warranty_requests.partner_id', 'partners.id')
            ->where('warranty_requests.loai', '=', 'bao_tri')
            ->where('warranty_requests.status', '=', 'done')
            ->whereNotNull('warranty_requests.completed_date')
            ->orderBy('warranty_requests.completed_date', 'DESC')
            ->orderBy('warranty_requests.id', 'DESC')->get();
    }

    /** Phiếu bảo trì đang mở — xe đã có hẹn lần mới thì không cần gọi nhắc nữa */
    public function baoTriDangMo(){
        return $this->table($this->_table)
            ->select('`id`, `request_no`, `received_date`, `appointment_date`, `bien_so_chuan`, `serial_no`, '
                   . '`partner_id`, `phone`, `customer_name`, `part_id`, `product_name`')
            ->where('loai', '=', 'bao_tri')
            ->whereIn('status', ['received', 'processing'])->get();
    }

    /**
     * Mọi lần ghi số km của các xe có biển số (ĐÃ chuẩn hoá) trong $ds, gom từ
     * báo giá, hoá đơn bán, phiếu bảo hành / bảo trì và xe của khách.
     * Trả [bien_so_chuan => [['ngay' => 'Y-m-d', 'km' => int], ...]].
     *
     * Phần mềm không biết đồng hồ xe hôm nay chỉ bao nhiêu — chỉ biết những
     * lần ai đó ghi số km lên chứng từ. Chừng đó đủ để ước xe chạy nhanh cỡ nào.
     */
    public function docKmTheoBienSo(array $ds){
        $ds = array_values(array_unique(array_filter(array_map('strval', $ds))));
        $kq = [];
        if (empty($ds)) return $kq;

        $nguon = [
            'quotations'        => '`quote_date`',
            'sales_invoices'    => '`invoice_date`',
            'warranty_requests' => '`received_date`',
            'member_vehicles'   => 'DATE(COALESCE(`update_at`, `create_at`))',
        ];
        foreach ($nguon as $bang => $cotNgay){
            $rows = $this->table($bang)
                ->select('`bien_so_chuan`, ' . $cotNgay . ' AS ngay, `so_km` AS km')
                ->whereIn('bien_so_chuan', $ds)
                ->whereNotNull('so_km')->get();
            foreach ((array) $rows as $r){
                if (empty($r['ngay'])) continue;
                $kq[$r['bien_so_chuan']][] = ['ngay' => substr($r['ngay'], 0, 10), 'km' => (int) $r['km']];
            }
        }
        return $kq;
    }

    public function setReminded($id, $date){
        return $this->updateById(['reminded_at' => $date, 'update_at' => date('Y-m-d H:i:s')], (int) $id);
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /**
     * Số phiếu kế tiếp của MỘT loại: BH-000005, BT-000001...
     * Chỉ nhìn phiếu cùng loại và đúng tiền tố — phiếu bảo trì mới không được
     * nhảy tiếp số của dãy bảo hành.
     */
    public function nextNo($loai = 'bao_hanh'){
        $loai = self::loaiHopLe($loai);
        $tien = self::$tienTo[$loai] . '-';
        $row = $this->table($this->_table)->select('`request_no`')
            ->where('loai', '=', $loai)
            ->whereLike('request_no', $tien . '%')
            ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['request_no'], $m)){ $n = (int) $m[1]; }
        return $tien . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
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

    public function remove($id){ return $this->deleteById($id); }
}

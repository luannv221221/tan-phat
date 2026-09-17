<?php

use App\core\Model;

/**
 * PHIẾU TIẾP NHẬN — một lần xe vào xưởng.
 *
 * Đây là tầng còn thiếu của mô hình: một XE nhiều lần vào xưởng, mỗi lần vào
 * sinh ra báo giá / hoá đơn / phiếu bảo hành của riêng lần đó. Trước đây các
 * chứng từ đó rời nhau, không cái nào là cha, nên không trả lời được câu
 * "lần vào xưởng hôm ấy đã làm những gì, hết bao nhiêu".
 *
 * Luồng: tiep_nhan (xe vào) -> dang_sua -> hoan_tat -> da_giao (trả xe).
 */
class ReceptionsModel extends Model {

    protected $_table   = 'receptions';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $statuses = [
        'tiep_nhan' => 'Tiếp nhận',
        'dang_sua'  => 'Đang sửa',
        'hoan_tat'  => 'Hoàn tất',
        'da_giao'   => 'Đã giao xe',
        'huy'       => 'Đã huỷ',
    ];

    /** Trạng thái còn ở xưởng — dùng cho màn theo dõi xe đang trong xưởng */
    public static $dangMo = ['tiep_nhan', 'dang_sua', 'hoan_tat'];

    public static function statusHopLe($v, $macDinh = 'tiep_nhan'){
        return (is_string($v) && isset(self::$statuses[$v])) ? $v : $macDinh;
    }

    private function chonKemTen(){
        return '`receptions`.*, '
             . '`vehicles`.`bien_so` AS bien_so, `vehicles`.`bien_so_chuan` AS bien_so_chuan, '
             . '`vehicles`.`hang_xe` AS hang_xe, `vehicles`.`model_xe` AS model_xe, '
             . '`vehicles`.`nam_sx` AS nam_sx, `vehicles`.`phien_ban` AS phien_ban, '
             . '`partners`.`name` AS chu_ten, `partners`.`phone` AS chu_sdt, '
             . '`car_brands`.`name` AS hang_dm, `car_models`.`name` AS model_dm, `car_years`.`name` AS nam_dm, '
             . '`users`.`name` AS co_van_ten';
    }

    private function joinKemTen($q){
        return $q->leftJoinOn('vehicles', 'receptions.vehicle_id', 'vehicles.id')
                 ->leftJoinOn('partners', 'receptions.partner_id', 'partners.id')
                 ->leftJoinOn('car_brands', 'vehicles.brand_id', 'car_brands.id')
                 ->leftJoinOn('car_models', 'vehicles.model_id', 'car_models.id')
                 ->leftJoinOn('car_years', 'vehicles.car_year_id', 'car_years.id')
                 ->leftJoinOn('users', 'receptions.co_van_id', 'users.id');
    }

    /**
     * Danh sách phiếu, lọc được.
     * $loc: q (số phiếu / biển số / khách), status, from, to (theo ngày vào),
     *       vehicle_id, dang_mo ('1' = còn ở xưởng)
     */
    public function getLists(array $loc = []){
        $q = $this->table($this->_table)->select($this->chonKemTen());
        $q = $this->joinKemTen($q);

        if (!empty($loc['status']) && isset(self::$statuses[$loc['status']])){
            $q = $q->where('receptions.status', '=', $loc['status']);
        }
        if (!empty($loc['dang_mo']))    $q = $q->whereIn('receptions.status', self::$dangMo);
        if (!empty($loc['vehicle_id'])) $q = $q->where('receptions.vehicle_id', '=', (int) $loc['vehicle_id']);
        if (!empty($loc['from']))       $q = $q->where('receptions.ngay_vao', '>=', $loc['from']);
        if (!empty($loc['to']))         $q = $q->where('receptions.ngay_vao', '<=', $loc['to']);

        if (!empty($loc['q'])){
            $tu    = trim((string) $loc['q']);
            $chuan = chuan_hoa_bien_so($tu);
            $q = $q->where(function($sub) use ($tu, $chuan){
                $like = '%' . $tu . '%';
                $sub->whereLike('receptions.reception_no', $like);
                // Biển số: so trên cột chuẩn hoá, chặn từ khoá rỗng (xem VehiclesModel)
                $sub->whereOrLike('vehicles.bien_so_chuan', $chuan === '' ? "\x00" : '%' . $chuan . '%');
                $sub->whereOrLike('partners.name', $like);
                $sub->whereOrLike('receptions.co_van', $like);
            });
        }
        return $q->orderBy('receptions.ngay_vao', 'DESC')
                 ->orderBy('receptions.id', 'DESC')->get();
    }

    /** Lịch sử vào xưởng của một xe — mới nhất trước */
    public function theoXe($vehicleId){
        $vehicleId = (int) $vehicleId;
        if ($vehicleId <= 0) return [];
        $q = $this->table($this->_table)->select($this->chonKemTen());
        return $this->joinKemTen($q)
            ->where('receptions.vehicle_id', '=', $vehicleId)
            ->orderBy('receptions.ngay_vao', 'DESC')
            ->orderBy('receptions.id', 'DESC')->get();
    }

    public function getDetail($id){
        $q = $this->table($this->_table)->select($this->chonKemTen());
        return $this->joinKemTen($q)->where('receptions.id', '=', (int) $id)->first();
    }

    /** Số phiếu kế tiếp: TN-000001 */
    public function nextNo(){
        $row = $this->table($this->_table)->select('`reception_no`')
            ->whereLike('reception_no', 'TN-%')
            ->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['reception_no'], $m)) $n = (int) $m[1];
        return 'TN-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Chứng từ đã gắn vào phiếu — để biết lần vào xưởng đó làm những gì.
     * Trả ['quotations' => [...], 'sales_invoices' => [...], 'warranty' => [...]]
     */
    public function chungTu($id){
        $id = (int) $id;
        return [
            'quotations' => (array) $this->table('quotations')
                ->select('`id`, `quote_no` AS so, `quote_date` AS ngay, `total_amount` AS tien, `status`')
                ->where('reception_id', '=', $id)->orderBy('id', 'DESC')->get(),
            'sales_invoices' => (array) $this->table('sales_invoices')
                ->select('`id`, `invoice_no` AS so, `invoice_date` AS ngay, `total_amount` AS tien, `status`')
                ->where('reception_id', '=', $id)->orderBy('id', 'DESC')->get(),
            'warranty' => (array) $this->table('warranty_requests')
                ->select('`id`, `request_no` AS so, `received_date` AS ngay, `loai`, `status`')
                ->where('reception_id', '=', $id)->orderBy('id', 'DESC')->get(),
        ];
    }

    /** Có chứng từ nào gắn vào phiếu này chưa — chặn xoá phiếu đang có chứng từ */
    public function demChungTu($id){
        $ds = $this->chungTu($id);
        return count($ds['quotations']) + count($ds['sales_invoices']) + count($ds['warranty']);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, (int) $id);
    }

    public function remove($id){ return $this->deleteById((int) $id); }
}

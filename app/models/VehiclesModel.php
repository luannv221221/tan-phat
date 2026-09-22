<?php

use App\core\Model;

/**
 * XE CỦA KHÁCH — một khách nhiều xe, một xe nhiều phiếu tiếp nhận. RIÊNG từng gara.
 *
 * Biển số lưu hai bản: `bien_so` nguyên văn người gõ, `bien_so_chuan` đã bỏ
 * dấu và viết hoa (chuan_hoa_bien_so) và DUY NHẤT TRONG MỘT GARA — gõ
 * "30A-123.45", "30a12345" hay "30A 123 45" đều là một xe. Hai gara độc lập
 * cùng có khách mang chiếc xe đó thì là hai hồ sơ riêng.
 *
 * Hãng / model / năm có HAI đường: khoá tới danh mục xe (brand_id, model_id,
 * car_year_id) và cột chữ gõ tay (hang_xe, model_xe, nam_sx) cho xe lạ chưa
 * có trong danh mục. Hiển thị thì ưu tiên tên trong danh mục.
 */
class VehiclesModel extends Model {

    protected $_table   = 'vehicles';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_theoGara = true;

    /** Các cột join thêm — dùng chung cho danh sách và chi tiết */
    private function chonKemTen(){
        return '`vehicles`.*, '
             . '`partners`.`name` AS chu_ten, `partners`.`code` AS chu_ma, `partners`.`phone` AS chu_sdt, '
             . '`car_brands`.`name` AS hang_dm, `car_models`.`name` AS model_dm, `car_years`.`name` AS nam_dm';
    }

    private function joinKemTen($q){
        return $q->leftJoinOn('partners', 'vehicles.partner_id', 'partners.id')
                 ->leftJoinOn('car_brands', 'vehicles.brand_id', 'car_brands.id')
                 ->leftJoinOn('car_models', 'vehicles.model_id', 'car_models.id')
                 ->leftJoinOn('car_years', 'vehicles.car_year_id', 'car_years.id');
    }

    /**
     * Tên xe để hiện một dòng: "Toyota Vios 2019".
     * Ưu tiên tên trong danh mục, thiếu thì lấy chữ gõ tay.
     */
    public static function tenXe($r){
        $phan = [];
        $hang  = !empty($r['hang_dm']) ? $r['hang_dm'] : (!empty($r['hang_xe']) ? $r['hang_xe'] : '');
        $model = !empty($r['model_dm']) ? $r['model_dm'] : (!empty($r['model_xe']) ? $r['model_xe'] : '');
        $nam   = !empty($r['nam_dm']) ? $r['nam_dm'] : (!empty($r['nam_sx']) ? $r['nam_sx'] : '');
        foreach ([$hang, $model, $nam] as $x) if ($x !== '' && $x !== null) $phan[] = $x;
        if (!empty($r['phien_ban'])) $phan[] = $r['phien_ban'];
        return implode(' ', $phan);
    }

    /**
     * Danh sách xe, lọc được.
     *
     * $loc: q (biển số / số khung / số máy / tên–mã chủ), partner_id,
     *       brand_id, status ('1' | '0'), khong_chu ('1' = xe chưa gán chủ)
     */
    public function getLists(array $loc = []){
        $q = $this->bangGara()->select($this->chonKemTen());
        $q = $this->joinKemTen($q);

        if (!empty($loc['partner_id'])) $q = $q->where('vehicles.partner_id', '=', (int) $loc['partner_id']);
        if (!empty($loc['brand_id']))   $q = $q->where('vehicles.brand_id', '=', (int) $loc['brand_id']);
        if (isset($loc['status']) && ($loc['status'] === '1' || $loc['status'] === '0')){
            $q = $q->where('vehicles.status', '=', (int) $loc['status']);
        }
        if (!empty($loc['khong_chu'])) $q = $q->whereNull('vehicles.partner_id');

        if (!empty($loc['q'])){
            $tu    = trim((string) $loc['q']);
            $chuan = chuan_hoa_bien_so($tu);
            $q = $q->where(function($sub) use ($tu, $chuan){
                $like = '%' . $tu . '%';
                /* Biển số so trên cột CHUẨN HOÁ, và chuẩn hoá luôn từ khoá —
                   chỉ chuẩn hoá một bên thì gõ "30a12345" không ra "30A-123.45".
                   Từ khoá không còn chữ số nào ("---") thì chặn, nếu không
                   LIKE '%%' khớp cả bảng. */
                $sub->whereLike('vehicles.bien_so_chuan', $chuan === '' ? "\x00" : '%' . $chuan . '%');
                $sub->whereOrLike('vehicles.so_khung', $like);
                $sub->whereOrLike('vehicles.so_may', $like);
                $sub->whereOrLike('partners.name', $like);
                $sub->whereOrLike('partners.code', $like);
            });
        }
        return $q->orderBy('vehicles.id', 'DESC')->get();
    }

    /** Xe của một khách — dùng cho khối "Xe của khách" trên màn Đối tượng */
    public function theoChu($partnerId){
        $partnerId = (int) $partnerId;
        if ($partnerId <= 0) return [];
        $q = $this->bangGara()->select($this->chonKemTen());
        return $this->joinKemTen($q)
            ->where('vehicles.partner_id', '=', $partnerId)
            ->orderBy('vehicles.id', 'DESC')->get();
    }

    /**
     * Xe của NHIỀU khách một lần — [partner_id => [xe, ...]] cho danh sách khách.
     * Hỏi theo từng dòng thì 20 khách là 20 truy vấn thừa.
     */
    public function theoNhieuChu(array $ids){
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) return [];
        $rows = $this->bangGara()->select('`vehicles`.`id`, `vehicles`.`partner_id`, `vehicles`.`bien_so`, `vehicles`.`so_khung`')
            ->whereIn('vehicles.partner_id', $ids)->orderBy('vehicles.id', 'ASC')->get();
        $ra = [];
        foreach ((array) $rows as $r) $ra[(int) $r['partner_id']][] = $r;
        return $ra;
    }

    public function getDetail($id){
        $q = $this->bangGara()->select($this->chonKemTen());
        return $this->joinKemTen($q)->where('vehicles.id', '=', (int) $id)->first();
    }

    /** Tra xe theo biển số (nhận mọi cách gõ) */
    public function theoBienSo($bienSo){
        $chuan = chuan_hoa_bien_so($bienSo);
        if ($chuan === '') return [];
        return $this->bangGara()->where('bien_so_chuan', '=', $chuan)->first();
    }

    /** Tra xe theo số khung — trống thì KHÔNG tra, vì '' không phải một xe */
    public function theoSoKhung($soKhung){
        $vin = strtoupper(trim((string) $soKhung));
        if ($vin === '') return [];
        return $this->bangGara()->where('so_khung', '=', $vin)->first();
    }

    /** Gợi ý xe cho ô chọn nhanh trên chứng từ (tối đa $gioiHan dòng) */
    public function goiY($tu, $gioiHan = 20){
        $ds = $this->getLists(['q' => $tu, 'status' => '1']);
        $ra = [];
        foreach (array_slice((array) $ds, 0, (int) $gioiHan) as $r){
            $ra[] = [
                'id'      => (int) $r['id'],
                'bien_so' => $r['bien_so'],
                'ten_xe'  => self::tenXe($r),
                'chu'     => !empty($r['chu_ten']) ? $r['chu_ten'] : '',
                'so_km'   => $r['so_km'] !== null ? (int) $r['so_km'] : null,
            ];
        }
        return $ra;
    }

    /** Số phiếu tiếp nhận của một xe — để biết có được xoá xe không */
    public function demPhieu($id){
        $r = $this->locGara($this->table('receptions'), 'receptions.garage_id')->select('COUNT(*) AS n')
            ->where('vehicle_id', '=', (int) $id)->first();
        return !empty($r['n']) ? (int) $r['n'] : 0;
    }

    /**
     * Cập nhật số km — CHỈ TĂNG.
     * Đồng hồ xe không quay lui: nhận số nhỏ hơn thì coi là gõ nhầm và bỏ qua,
     * không thì một lần gõ thiếu chữ số làm hỏng mốc nhắc bảo trì của xe.
     */
    public function capNhatKm($id, $km){
        $km = (int) $km;
        if ($km <= 0) return false;
        $xe = $this->bangGara()->select('`so_km`')->where('id', '=', (int) $id)->first();
        if (empty($xe)) return false;
        if ($xe['so_km'] !== null && (int) $xe['so_km'] >= $km) return false;
        return $this->updateById(['so_km' => $km, 'update_at' => date('Y-m-d H:i:s')], (int) $id);
    }

    /* ===== Danh mục xe (hãng → model → năm) ===== */

    /** Hãng đang dùng, cho ô chọn */
    public function hangDanhMuc(){
        return $this->table('car_brands')->select('`id`, `name`')
            ->where('status', '=', 1)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    /** Model của một hãng — dạng [['c' => id, 'n' => tên], ...] cho ô chọn dây chuyền */
    public function modelTheoHang($brandId){
        $brandId = (int) $brandId;
        if ($brandId <= 0) return [];
        $rows = $this->table('car_models')->select('`id`, `name`')
            ->where('brand_id', '=', $brandId)->where('status', '=', 1)
            ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
        $ds = [];
        foreach ((array) $rows as $r) $ds[] = ['c' => (int) $r['id'], 'n' => $r['name']];
        return $ds;
    }

    /** Mốc năm của một model */
    public function namTheoModel($modelId){
        $modelId = (int) $modelId;
        if ($modelId <= 0) return [];
        $rows = $this->table('car_years')->select('`id`, `name`')
            ->where('model_id', '=', $modelId)->where('status', '=', 1)
            ->orderBy('name', 'DESC')->get();
        $ds = [];
        foreach ((array) $rows as $r) $ds[] = ['c' => (int) $r['id'], 'n' => $r['name']];
        return $ds;
    }

    /**
     * Model có đúng thuộc hãng đó không / năm có đúng thuộc model đó không.
     *
     * Kiểm ở SERVER: ô chọn dây chuyền chỉ lọc ở trình duyệt, ai cũng gửi lên
     * được model của hãng khác — y như chuyện phường phải thuộc tỉnh.
     */
    public function modelThuocHang($modelId, $brandId){
        $modelId = (int) $modelId; $brandId = (int) $brandId;
        if ($modelId <= 0) return true;                  // không chọn thì không cần kiểm
        if ($brandId <= 0) return false;                 // có model mà không có hãng: sai
        $r = $this->table('car_models')->select('`id`')
            ->where('id', '=', $modelId)->where('brand_id', '=', $brandId)->first();
        return !empty($r);
    }

    public function namThuocModel($yearId, $modelId){
        $yearId = (int) $yearId; $modelId = (int) $modelId;
        if ($yearId <= 0) return true;
        if ($modelId <= 0) return false;
        $r = $this->table('car_years')->select('`id`')
            ->where('id', '=', $yearId)->where('model_id', '=', $modelId)->first();
        return !empty($r);
    }

    /**
     * Chứng từ của một xe (mọi lần vào xưởng) — cho khối "Lịch sử xe".
     * Lấy theo `vehicle_id` nên xe đổi biển số vẫn ra đủ lịch sử cũ.
     */
    public function chungTuTheoXe($id){
        $id = (int) $id;
        return [
            'quotations' => (array) $this->locGara($this->table('quotations'), 'quotations.garage_id')
                ->select('`id`, `quote_no` AS so, `quote_date` AS ngay, `total_amount` AS tien, `status`, `reception_id`')
                ->where('vehicle_id', '=', $id)->orderBy('id', 'DESC')->get(),
            'sales_invoices' => (array) $this->locGara($this->table('sales_invoices'), 'sales_invoices.garage_id')
                ->select('`id`, `invoice_no` AS so, `invoice_date` AS ngay, `total_amount` AS tien, `status`, `reception_id`')
                ->where('vehicle_id', '=', $id)->orderBy('id', 'DESC')->get(),
            'warranty' => (array) $this->locGara($this->table('warranty_requests'), 'warranty_requests.garage_id')
                ->select('`id`, `request_no` AS so, `received_date` AS ngay, `loai`, `status`, `reception_id`')
                ->where('vehicle_id', '=', $id)->orderBy('id', 'DESC')->get(),
        ];
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

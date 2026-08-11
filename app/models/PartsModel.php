<?php

use App\core\Model;

/**
 * Phụ tùng — TASK_86, TASK_87, TASK_93.
 */
class PartsModel extends Model {

    protected $_table   = 'parts';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Phân loại hàng hoá (cột `item_type`) — chốt 05/08/2026.
     *
     * Quyết định NGHIỆP VỤ, tách khỏi danh mục (danh mục chỉ lo hiển thị):
     *   part / equipment : có tồn kho, bán ra phải đủ tồn — chạy y như trước
     *   service          : KHÔNG có tồn, bỏ qua mọi chốt kho
     *
     * Thiếu cái này thì "thay dầu" có tồn = 0 và mọi hoá đơn dịch vụ bị chặn
     * ngay ở bước kiểm tồn.
     */
    const LOAI_PHU_TUNG = 'part';
    const LOAI_THIET_BI = 'equipment';
    const LOAI_DICH_VU  = 'service';

    public static $loaiHang = [
        self::LOAI_PHU_TUNG => 'Phụ tùng',
        self::LOAI_THIET_BI => 'Thiết bị',
        self::LOAI_DICH_VU  => 'Dịch vụ',
    ];

    /** Mặt hàng này có đi qua kho không (dịch vụ thì không) */
    public static function coKho($itemType){
        return $itemType !== self::LOAI_DICH_VU;
    }

    /** Tra loại của nhiều mặt hàng cùng lúc: [part_id => item_type] */
    public function loaiTheoId(array $partIds){
        $ids = array_values(array_unique(array_map('intval', $partIds)));
        if (empty($ids)) return [];

        $rows = $this->table($this->_table)
                     ->select('`id`, `item_type`')
                     ->whereIn('id', $ids)->get();

        $map = [];
        foreach ($rows ?: [] as $r){ $map[(int) $r['id']] = $r['item_type']; }
        return $map;
    }

    /** Các cột hay lấy kèm tên danh mục/thương hiệu */
    protected function selectWithJoins(){
        return $this->table($this->_table)
            ->select('`parts`.*, `part_categories`.`name` AS category_name, '
                   . '`part_brands`.`name` AS brand_name, '
                   . '`part_origins`.`name` AS origin_name, '
                   . '`part_units`.`name` AS unit_name')
            ->leftJoinOn('part_categories', 'parts.category_id', 'part_categories.id')
            ->leftJoinOn('part_brands', 'parts.brand_id', 'part_brands.id')
            ->leftJoinOn('part_origins', 'parts.origin_id', 'part_origins.id')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id');
    }

    /**
     * Danh sách phụ tùng, lọc theo danh mục/thương hiệu + tìm theo từ khoá.
     * TASK_90 (lọc theo danh mục), TASK_91 (tìm kiếm).
     */
    public function getLists($filters = [], $keyword = '', $limit = 0, $offset = 0, $promoOnly = false, $attrId = 0, $attrVal = ''){
        $q = $this->selectWithJoins();
        $q = $this->applyFilters($q, $filters, $keyword, $promoOnly);
        $q = $this->applyAttr($q, $attrId, $attrVal);
        $q = $q->orderBy('parts.name', 'ASC');

        if ($limit > 0){
            $q = $q->limit((int) $limit, (int) $offset);
        }

        return $q->get();
    }

    /** Đếm tổng số phụ tùng khớp bộ lọc — cho phân trang */
    public function countLists($filters = [], $keyword = '', $promoOnly = false, $attrId = 0, $attrVal = ''){
        $q = $this->table($this->_table)->select('COUNT(*) AS total');
        $q = $this->applyFilters($q, $filters, $keyword, $promoOnly);
        $q = $this->applyAttr($q, $attrId, $attrVal);
        $r = $q->first();

        return (int) ($r['total'] ?? 0);
    }

    /** TASK_90 — lọc theo thông số kỹ thuật (join part_attribute_values) */
    private function applyAttr($q, $attrId, $attrVal){
        $attrId = (int) $attrId;
        if ($attrId > 0){
            // INNER JOIN: chỉ giữ phụ tùng CÓ thông số này. UNIQUE(part_id,attribute_id)
            // đảm bảo mỗi phụ tùng khớp tối đa 1 dòng -> không nhân bản.
            $q = $q->joinOn('part_attribute_values', 'parts.id', 'part_attribute_values.part_id')
                   ->where('part_attribute_values.attribute_id', '=', $attrId);
            if ($attrVal !== ''){
                $q = $q->whereLike('part_attribute_values.value', '%' . $attrVal . '%');
            }
        }
        return $q;
    }

    /** Áp bộ lọc + từ khoá (dùng chung cho getLists và countLists) */
    private function applyFilters($q, $filters, $keyword, $promoOnly = false){
        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        // TASK_80 — chỉ hàng khuyến mãi (có sale_price)
        if ($promoOnly){
            $q = $q->whereNotNull('parts.sale_price');
        }

        if ($keyword !== ''){
            // Tìm theo tên HOẶC mã HOẶC mã OEM — bọc nhóm để không phá điều kiện lọc phía trên.
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('parts.name', $like);
                $sub->whereOrLike('parts.code', $like);
                $sub->whereOrLike('parts.oem_code', $like);
            });
        }

        return $q;
    }

    public function findBySlug($slug){
        return $this->table($this->_table)->where('slug', '=', $slug)->first();
    }

    /**
     * Điều kiện để một mặt hàng ĐƯỢC LÊN WEBSITE.
     *
     * Hai cờ khác nhau, đừng gộp:
     *   status      — còn kinh doanh không (gác cả ô chọn hàng bên admin)
     *   show_on_web — có đăng lên web không
     * Hàng ngừng đăng web vẫn phải xuất hoá đơn và nhập/xuất kho được.
     */
    private function chiHangLenWeb($q){
        return $q->where('parts.status', '=', 1)
                 ->where('parts.show_on_web', '=', 1);
    }

    /** Chi tiết 1 phụ tùng kèm tên danh mục/thương hiệu/xuất xứ/đơn vị — cho storefront */
    public function getBySlugFull($slug){
        return $this->chiHangLenWeb(
            $this->selectWithJoins()->where('parts.slug', '=', $slug)
        )->first();
    }

    /**
     * ⭐ STOREFRONT — danh sách sản phẩm công khai + LỌC FACET (TASK_92).
     *
     * @param array $filters khoá hỗ trợ:
     *   categoryIds[], brandIds[], originIds[] : whereIn
     *   priceMin, priceMax : khoảng giá
     *   promo (bool)       : chỉ hàng có sale_price
     *   keyword            : tên/mã/oem
     *   carBrandId, carBodyTypeId, carModelId, carYearId
     *                      : chỉ phụ tùng lắp cho xe này (qua fitment) — xem hasCarFilter()
     *   sort               : 'new'|'price_asc'|'price_desc'|'name'
     */
    public function storefront($filters = [], $limit = 0, $offset = 0){
        $q = $this->applyStorefront($this->selectWithJoins(), $filters);

        // Một phụ tùng lắp cho nhiều đời xe sẽ ra nhiều dòng sau khi join
        // part_fitments — gom lại để thẻ sản phẩm không hiện trùng.
        // storefrontCount() KHÔNG gom: nó đã đếm COUNT(DISTINCT parts.id), thêm
        // GROUP BY vào đó thì mỗi nhóm đếm ra 1 và first() trả về tổng = 1.
        if ($this->hasCarFilter($filters)){
            $q = $q->groupBy('parts.id');
        }

        switch ($filters['sort'] ?? '') {
            case 'price_asc':  $q = $q->orderBy('parts.price', 'ASC'); break;
            case 'price_desc': $q = $q->orderBy('parts.price', 'DESC'); break;
            case 'new':        $q = $q->orderBy('parts.id', 'DESC'); break;
            default:           $q = $q->orderBy('parts.name', 'ASC');
        }
        if ($limit > 0) $q = $q->limit((int) $limit, (int) $offset);
        return $q->get();
    }

    public function storefrontCount($filters = []){
        $q = $this->applyStorefront($this->table($this->_table)->select('COUNT(DISTINCT `parts`.`id`) AS total'), $filters);
        $r = $q->first();
        return (int) ($r['total'] ?? 0);
    }

    private function applyStorefront($q, $filters){
        $q = $this->chiHangLenWeb($q);

        if (!empty($filters['categoryIds'])) $q = $q->whereIn('parts.category_id', $filters['categoryIds']);
        if (!empty($filters['brandIds']))    $q = $q->whereIn('parts.brand_id', $filters['brandIds']);
        if (!empty($filters['originIds']))    $q = $q->whereIn('parts.origin_id', $filters['originIds']);

        if (isset($filters['priceMin']) && $filters['priceMin'] !== '') $q = $q->where('parts.price', '>=', (float) $filters['priceMin']);
        if (isset($filters['priceMax']) && $filters['priceMax'] !== '') $q = $q->where('parts.price', '<=', (float) $filters['priceMax']);

        if (!empty($filters['promo'])) $q = $q->whereNotNull('parts.sale_price');

        if (!empty($filters['keyword'])){
            $q = $q->where(function($sub) use ($filters){
                $like = '%' . $filters['keyword'] . '%';
                $sub->whereLike('parts.name', $like);
                $sub->whereOrLike('parts.code', $like);
                $sub->whereOrLike('parts.oem_code', $like);
            });
        }

        return $this->applyCarFilter($q, $filters);
    }

    /** Bộ lọc theo xe có được dùng không (dù chỉ 1 trong 4 mức) */
    private function hasCarFilter($filters){
        return !empty($filters['carBrandId']) || !empty($filters['carBodyTypeId'])
            || !empty($filters['carModelId']) || !empty($filters['carYearId']);
    }

    /**
     * Lọc theo xe: hãng / dòng xe (kiểu dáng) / model / đời xe — TASK_87/93.
     *
     * Phụ tùng nối vào xe qua part_fitments -> car_years, nên cả 4 mức dùng
     * chung một cặp join. Chỉ áp điều kiện HẸP NHẤT khách đã chọn: đời xe đã
     * hàm ý model, model đã hàm ý hãng lẫn kiểu dáng — xếp chồng thêm chỉ tốn
     * join chứ không loại thêm được dòng nào.
     */
    private function applyCarFilter($q, $filters){
        if (!$this->hasCarFilter($filters)) return $q;

        $q = $q->joinOn('part_fitments', 'parts.id', 'part_fitments.part_id')
               ->joinOn('car_years', 'part_fitments.car_year_id', 'car_years.id');

        if (!empty($filters['carYearId'])){
            return $q->where('car_years.id', '=', (int) $filters['carYearId']);
        }

        if (!empty($filters['carModelId'])){
            return $q->where('car_years.model_id', '=', (int) $filters['carModelId']);
        }

        $q = $q->joinOn('car_models', 'car_years.model_id', 'car_models.id');

        if (!empty($filters['carBrandId'])){
            $q = $q->where('car_models.brand_id', '=', (int) $filters['carBrandId']);
        }
        if (!empty($filters['carBodyTypeId'])){
            $q = $q->where('car_models.body_type_id', '=', (int) $filters['carBodyTypeId']);
        }

        return $q;
    }

    /**
     * Phụ tùng đang bật cho dropdown dòng hàng (nhập/xuất kho, báo giá, hoá đơn).
     * Kèm đơn vị + giá bán (price/sale_price) để form tự điền đơn giá khi chọn.
     *
     * @param bool $chiHangCoKho true = BỎ dịch vụ. Dùng cho màn hình kho: nhập
     *   một "lần thay dầu" vào kho là vô nghĩa, và nó sẽ đẻ ra thẻ kho rác.
     *   Báo giá / hoá đơn thì để false vì gara CÓ bán dịch vụ.
     */
    public function getForSelect($chiHangCoKho = false){
        $q = $this->table($this->_table)
            ->select('`parts`.`id`, `parts`.`code`, `parts`.`name`, `parts`.`price`, '
                   . '`parts`.`sale_price`, `parts`.`item_type`, `part_units`.`name` AS unit_name')
            ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id')
            ->where('parts.status', '=', 1);

        if ($chiHangCoKho){
            $q = $q->where('parts.item_type', '!=', self::LOAI_DICH_VU);
        }

        $rows = $q->orderBy('parts.name', 'ASC')->get();

        $imgs = $this->primaryImageMap();
        foreach ($rows as $i => $r){
            $rows[$i]['image'] = isset($imgs[(int) $r['id']]) ? $imgs[(int) $r['id']] : '';
        }

        return $rows;
    }

    /**
     * Ảnh đại diện của mọi hàng hoá: [part_id => tên file].
     *
     * Ảnh nằm ở bảng `part_images` nên nếu hỏi từng dòng sẽ thành N+1 query —
     * lấy 1 lần rồi map theo part_id. Thứ tự sắp xếp giống primaryFor() nên
     * bản ghi đầu tiên của mỗi part chính là ảnh đại diện.
     */
    protected function primaryImageMap(){
        $rows = $this->table('part_images')
            ->select('`part_id`, `image`')
            ->orderBy('part_id', 'ASC')
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')->get();

        $map = [];
        foreach ($rows as $r){
            $pid = (int) $r['part_id'];
            if (!isset($map[$pid])){
                $map[$pid] = $r['image'];
            }
        }

        return $map;
    }

    /**
     * Tìm nhanh phụ tùng theo tên/mã — cho ô chọn "phụ kiện đi kèm" (TASK_81).
     * Trả về tối đa $limit dòng gồm id, code, name.
     */
    public function search($keyword, $excludeId = 0, $limit = 20){
        $q = $this->table($this->_table)->select('`id`, `code`, `name`');

        if ($excludeId > 0){
            $q = $q->where('id', '!=', (int) $excludeId);
        }

        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('name', $like);
                $sub->whereOrLike('code', $like);
            });
        }

        return $q->orderBy('name', 'ASC')->limit((int) $limit, 0)->get();
    }

    /**
     * ⭐ TASK_87 — "Chọn xe sẽ lọc ra các phụ tùng".
     *
     * Trả về phụ tùng lắp được cho một ĐỜI XE cụ thể.
     *
     * @param int   $carYearId id trong car_years
     * @param array $filters   lọc thêm, vd ['parts.category_id' => 3]
     */
    public function getByCarYear($carYearId, $filters = []){
        $q = $this->chiHangLenWeb(
            $this->selectWithJoins()
                 ->joinOn('part_fitments', 'parts.id', 'part_fitments.part_id')
                 ->where('part_fitments.car_year_id', '=', $carYearId)
        );

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        return $q->orderBy('parts.name', 'ASC')->get();
    }

    /**
     * ⭐ TASK_93 — Tìm phụ tùng theo model + năm.
     *
     * Khách chọn "Vios đời 2020" chứ không biết car_year_id là gì.
     * Hàm này tự tìm đời xe chứa năm đó rồi lấy phụ tùng.
     *
     * @return array Mảng rỗng nếu model/năm không có đời nào khớp
     */
    public function getByModelAndYear($modelId, $year, $filters = []){
        $year = (int) $year;

        // Tìm đời xe chứa năm này. year_to = NULL nghĩa là còn sản xuất.
        $carYear = $this->table('car_years')
            ->where('model_id', '=', $modelId)
            ->where('year_from', '<=', $year)
            ->where(function($q) use ($year){
                $q->whereNull('year_to');
                $q->orWhere('year_to', '>=', $year);
            })
            ->first();

        if (empty($carYear)) return [];

        return $this->getByCarYear($carYear['id'], $filters);
    }

    /** Phụ tùng lắp cho bất kỳ đời nào của một model */
    public function getByModel($modelId, $filters = []){
        $q = $this->chiHangLenWeb(
            $this->selectWithJoins()
                 ->joinOn('part_fitments', 'parts.id', 'part_fitments.part_id')
                 ->joinOn('car_years', 'part_fitments.car_year_id', 'car_years.id')
                 ->where('car_years.model_id', '=', $modelId)
        );

        foreach ($filters as $field => $value){
            $q = $q->where($field, '=', $value);
        }

        return $q->groupBy('parts.id')->orderBy('parts.name', 'ASC')->get();
    }

    public function getDetail($id){
        return $this->getFirst($id);
    }

    public function findByCode($code){
        return $this->table($this->_table)->where('code', '=', $code)->first();
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
        // part_fitments để ON DELETE CASCADE nên liên kết tự xoá theo.
        return $this->deleteById($id);
    }
}

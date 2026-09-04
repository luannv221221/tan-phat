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
        /* Hàng RIÊNG của một gara không lên website chung.
           Website là một trang cho cả hệ thống; đăng hàng riêng của gara Sài
           Gòn lên đó thì khách Hà Nội đặt mua một thứ chi nhánh mình không có.
           Chốt ở đây chứ không chỉ dựa vào cờ `show_on_web`: cờ đó người dùng
           bật tắt được, còn điều kiện này thì không được phép quên. */
        return $q->where('parts.status', '=', 1)
                 ->where('parts.show_on_web', '=', 1)
                 ->whereNull('parts.garage_id');
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

    /**
     * ⭐ STOREFRONT — "Sản phẩm liên quan" ở cuối trang chi tiết.
     *
     * Với phụ tùng ô tô, quan hệ mạnh nhất là LẮP VỪA CÙNG MỘT CHIẾC XE.
     * Khách đang xem lọc dầu cho Vios 2020 thì thứ đáng gợi ý là hàng cũng
     * lắp cho Vios 2020 — không phải một cái lọc dầu bất kỳ của xe khác.
     *
     * Nên xếp 3 mức, lấy dần cho đủ $limit rồi dừng:
     *   1. Cùng danh mục VÀ lắp chung ít nhất một đời xe   <- gần nhất
     *   2. Cùng danh mục
     *   3. Cùng thương hiệu
     *
     * Mức 3 là để cứu hai trường hợp có thật trong dữ liệu hiện tại: hàng
     * chưa khai fitment, và hàng nằm một mình trong danh mục của nó. Không
     * có mức này thì khối gợi ý trống trơn — trống còn tệ hơn là gợi ý hơi
     * xa. Vẫn có thể ra rỗng (shop mới, đúng một mặt hàng) nên phía view
     * BẮT BUỘC phải chịu được mảng rỗng.
     *
     * @param array $part    dòng phụ tùng đang xem (cần id, category_id, brand_id)
     * @param array $doiXe   id các đời xe mà phụ tùng này lắp vừa (car_year_id)
     * @param int   $limit   số thẻ tối đa
     * @param array $loaiTru id cần bỏ — thường là khối "phụ kiện đi kèm" ngay
     *                       phía trên, để hai khối không hiện trùng nhau
     */
    public function lienQuan(array $part, array $doiXe = [], $limit = 8, array $loaiTru = []){
        $limit = (int) $limit;
        $id    = (int) ($part['id'] ?? 0);
        if ($limit < 1 || $id < 1) return [];

        // Chính nó luôn bị loại — không thì trang chi tiết gợi ý ngược lại
        // đúng cái đang mở.
        $bo = [$id];
        foreach ($loaiTru as $x){ if ((int) $x > 0) $bo[] = (int) $x; }

        $catId = (int) ($part['category_id'] ?? 0);
        $brId  = (int) ($part['brand_id']    ?? 0);
        $doiXe = array_values(array_filter(array_map('intval', $doiXe)));

        $ids = [];
        if ($catId > 0 && !empty($doiXe)){
            $this->gomIdLienQuan($ids, $bo, $limit, $this->idLienQuan($bo, $limit, $catId, 0, $doiXe));
        }
        if (count($ids) < $limit && $catId > 0){
            $this->gomIdLienQuan($ids, $bo, $limit, $this->idLienQuan($bo, $limit, $catId, 0, []));
        }
        if (count($ids) < $limit && $brId > 0){
            $this->gomIdLienQuan($ids, $bo, $limit, $this->idLienQuan($bo, $limit, 0, $brId, []));
        }

        return $this->theoDanhSachId($ids);
    }

    /** Nối id mới vào $ids, cắt ở $limit, và ghi luôn vào $bo để mức sau không lấy lại */
    private function gomIdLienQuan(array &$ids, array &$bo, $limit, array $moi){
        foreach ($moi as $i){
            if (count($ids) >= $limit) return;
            $ids[] = $i;
            $bo[]  = $i;
        }
    }

    /**
     * Id phụ tùng cho MỘT mức liên quan. Chỉ lấy id, chưa lấy dữ liệu thẻ —
     * mức 1 phải join part_fitments nên một phụ tùng lắp cho nhiều đời xe sẽ
     * ra nhiều dòng; DISTINCT trên một cột id thì gọn, chứ DISTINCT trên cả
     * `parts`.* kèm 4 bảng join thì vừa nặng vừa không chắc gom đúng.
     */
    private function idLienQuan(array $bo, $limit, $catId, $brId, array $doiXe){
        $q = $this->chiHangLenWeb(
            $this->table($this->_table)->select('DISTINCT `parts`.`id`')
        );

        if (!empty($doiXe)){
            $q = $q->joinOn('part_fitments', 'parts.id', 'part_fitments.part_id')
                   ->whereIn('part_fitments.car_year_id', $doiXe);
        }
        if ((int) $catId > 0) $q = $q->where('parts.category_id', '=', (int) $catId);
        if ((int) $brId  > 0) $q = $q->where('parts.brand_id',    '=', (int) $brId);

        $rows = $q->whereNotIn('parts.id', $bo)
                  ->orderBy('parts.id', 'DESC')   // hàng mới lên trước
                  ->limit((int) $limit)
                  ->get();

        return array_map(function($r){ return (int) $r['id']; }, $rows ?: []);
    }

    /** Lấy đủ dữ liệu thẻ cho một danh sách id, GIỮ NGUYÊN thứ tự của $ids */
    private function theoDanhSachId(array $ids){
        if (empty($ids)) return [];

        $rows = $this->chiHangLenWeb($this->selectWithJoins())
                     ->whereIn('parts.id', $ids)->get();

        // whereIn trả về theo thứ tự MySQL thấy tiện, không theo thứ tự mình
        // truyền vào — mà thứ tự ở đây CHÍNH LÀ mức độ liên quan, đảo là hỏng
        // hết ý nghĩa xếp hạng ở trên.
        $theoId = [];
        foreach ($rows ?: [] as $r){ $theoId[(int) $r['id']] = $r; }

        $ket = [];
        foreach ($ids as $i){ if (isset($theoId[$i])) $ket[] = $theoId[$i]; }
        return $ket;
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
            /* CHỈ danh mục tổng. Hàng riêng của một gara không được lọt vào ô
               chọn của gara khác — muốn lấy hàng riêng thì gọi theoNguon(). */
            ->whereNull('parts.garage_id')
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

    /** Hai nguồn danh mục khi lập báo giá */
    const NGUON_TONG = 'tong';
    const NGUON_GARA = 'gara';

    /**
     * Danh sách hàng hoá theo NGUỒN, dạng dùng được ngay cho ô chọn ở báo giá.
     *
     *   NGUON_TONG  Danh mục tổng — mọi mặt hàng chung (`parts.garage_id IS NULL`),
     *               lấy giá gốc.
     *
     *   NGUON_GARA  Danh mục của gara — gồm HAI phần cộng lại:
     *                 a) hàng riêng của gara đó (`parts.garage_id = X`)
     *                 b) hàng của danh mục tổng mà gara đã chọn làm
     *                    (có dòng trong `garage_part_prices`)
     *               Giá lấy theo bảng giá riêng; bỏ trống ở đó thì rơi về giá gốc.
     *
     * VÌ SAO GARA CHƯA CHỌN GÌ THÌ PHẢI RA RỖNG
     * Cám dỗ ở đây là "gara chưa cấu hình thì cho xem tạm hàng tổng cho tiện".
     * Làm vậy thì hai nguồn giống hệt nhau, người dùng không hiểu nút mình vừa
     * bấm có tác dụng gì, và tệ hơn: họ tưởng gara đã có bảng giá riêng.
     *
     * Viết bằng SQL thô chứ không qua query builder: builder không dựng được
     * mệnh đề OR lồng với EXISTS, mà tách thành hai truy vấn rồi gộp trong PHP
     * thì mất thứ tự sắp xếp chung.
     *
     * @param string $nguon    NGUON_TONG | NGUON_GARA
     * @param int    $garageId Bắt buộc khi $nguon = NGUON_GARA
     */
    public function theoNguon($nguon, $garageId = 0, $chiHangCoKho = false){
        $garageId = (int) $garageId;
        $bind     = [];

        $loc = $chiHangCoKho
            ? " AND `parts`.`item_type` <> '" . self::LOAI_DICH_VU . "'"
            : '';

        if ($nguon === self::NGUON_GARA){
            if ($garageId <= 0) return [];

            /* `sale_price` KHÔNG được COALESCE thẳng về giá khuyến mãi của danh
               mục tổng.

               Cả hệ thống dùng quy ước "có sale_price thì lấy sale_price, không
               thì lấy price". Nên nếu gara đặt giá riêng 555.000 mà mặt hàng đó
               ở danh mục tổng đang khuyến mãi 1.380.000, COALESCE sẽ giữ lại
               1.380.000 và giá riêng của gara bị bỏ qua trong im lặng — form
               báo giá hiện đúng con số của kho tổng.

               Đặt giá riêng nghĩa là gara tự định giá mặt hàng đó: giá khuyến
               mãi của kho tổng không còn liên quan. Chỉ khi gara KHÔNG đặt giá
               thì mới rơi hết về danh mục tổng, cả giá lẫn khuyến mãi. */
            $sql = 'SELECT `parts`.`id`, `parts`.`code`, `parts`.`name`, `parts`.`item_type`,
                           COALESCE(`gpp`.`price`, `parts`.`price`) AS `price`,
                           CASE WHEN `gpp`.`price` IS NOT NULL
                                THEN `gpp`.`sale_price`
                                ELSE COALESCE(`gpp`.`sale_price`, `parts`.`sale_price`)
                           END AS `sale_price`,
                           `parts`.`price` AS `gia_tong`,
                           `gpp`.`price`   AS `gia_rieng`,
                           (`parts`.`garage_id` IS NOT NULL) AS `hang_rieng`,
                           `part_units`.`name` AS `unit_name`
                      FROM `parts`
                      LEFT JOIN `part_units` ON `part_units`.`id` = `parts`.`unit_id`
                      LEFT JOIN `garage_part_prices` `gpp`
                             ON `gpp`.`part_id` = `parts`.`id` AND `gpp`.`garage_id` = ?
                     WHERE `parts`.`status` = 1' . $loc . '
                       AND (
                             `parts`.`garage_id` = ?
                          OR (`parts`.`garage_id` IS NULL AND `gpp`.`id` IS NOT NULL AND `gpp`.`status` = 1)
                       )
                     ORDER BY `parts`.`name` ASC';
            $bind = [$garageId, $garageId];

        } else {
            $sql = 'SELECT `parts`.`id`, `parts`.`code`, `parts`.`name`, `parts`.`item_type`,
                           `parts`.`price`, `parts`.`sale_price`,
                           `parts`.`price` AS `gia_tong`,
                           NULL AS `gia_rieng`,
                           0 AS `hang_rieng`,
                           `part_units`.`name` AS `unit_name`
                      FROM `parts`
                      LEFT JOIN `part_units` ON `part_units`.`id` = `parts`.`unit_id`
                     WHERE `parts`.`status` = 1' . $loc . '
                       AND `parts`.`garage_id` IS NULL
                     ORDER BY `parts`.`name` ASC';
        }

        $rows = $this->getRaw($sql, $bind) ?: [];

        $imgs = $this->primaryImageMap();
        foreach ($rows as $i => $r){
            $rows[$i]['image'] = isset($imgs[(int) $r['id']]) ? $imgs[(int) $r['id']] : '';
        }
        return $rows;
    }

    /** Hàng riêng của một gara (không gồm hàng tổng gara đó chọn làm) */
    public function hangRiengCuaGara($garageId){
        return $this->table($this->_table)
                    ->select('`parts`.`id`, `parts`.`code`, `parts`.`name`, `parts`.`item_type`, '
                           . '`parts`.`price`, `parts`.`status`, `part_units`.`name` AS unit_name')
                    ->leftJoinOn('part_units', 'parts.unit_id', 'part_units.id')
                    ->where('parts.garage_id', '=', (int) $garageId)
                    ->orderBy('parts.name', 'ASC')
                    ->get();
    }

    /** Đếm hàng riêng — dùng để chặn xoá gara còn hàng */
    public function demHangRieng($garageId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')
                  ->where('garage_id', '=', (int) $garageId)->first();
        return !empty($r['c']) ? (int) $r['c'] : 0;
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

    /**
     * Mã tự sinh theo tiền tố, ví dụ nextCode('DV-') -> 'DV-0001'.
     *
     * Đọc số ĐUÔI của mọi mã cùng tiền tố rồi +1, chứ không đếm số dòng: xoá
     * một dịch vụ giữa chừng là số dòng tụt xuống và mã mới đè lên mã cũ.
     * Vẫn kiểm tra lại bằng findByCode() phòng khi có mã nhập tay chen vào.
     */
    public function nextCode($prefix){
        $rows = $this->table($this->_table)->select('`code`')
                     ->whereLike('code', $prefix . '%')->get();

        $max = 0;
        foreach ($rows ?: [] as $r){
            if (preg_match('/(\d+)$/', $r['code'], $m)) $max = max($max, (int) $m[1]);
        }

        for ($i = $max + 1; $i < $max + 1000; $i++){
            $ma = $prefix . str_pad($i, 4, '0', STR_PAD_LEFT);
            if (empty($this->findByCode($ma))) return $ma;
        }
        return $prefix . time();
    }

    /**
     * Slug chưa ai dùng, sinh từ $ten. $boQuaId là chính bản ghi đang sửa.
     *
     * Màn hình Dịch vụ không có ô slug (khách chỉ nhập tên + tiền) nên phải tự
     * né trùng ở đây; hai dịch vụ trùng tên là chuyện thường ("Thay dầu").
     */
    public function slugTrong($ten, $boQuaId = null){
        $goc = slugify($ten);
        if ($goc === '') $goc = 'dich-vu';

        $slug = $goc;
        for ($i = 2; $i < 1000; $i++){
            $co = $this->findBySlug($slug);
            if (empty($co) || ($boQuaId !== null && (int) $co['id'] === (int) $boQuaId)) return $slug;
            $slug = $goc . '-' . $i;
        }
        return $goc . '-' . time();
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

<?php

use App\core\Controller;
use App\core\Session;

/**
 * STOREFRONT — Danh sách sản phẩm (lọc facet, TASK_92) + chi tiết.
 * Tồn kho chỉ hiện với THÀNH VIÊN đã đăng nhập (TASK_79).
 */
class Shop extends Controller {

    const PER_PAGE = 12;
    /** Số gợi ý tối đa trả về cho ô tìm kiếm ở header */
    const SUGGEST_LIMIT = 8;

    private $__data = [];
    private $__part, $__cat, $__pbrand, $__origin, $__stock;
    private $__img, $__attr, $__related, $__fitment, $__review, $__member;

    function __construct(){
        $this->__part    = $this->model('PartsModel');
        $this->__cat     = $this->model('PartCategoriesModel');
        $this->__pbrand  = $this->model('ProductBrandsModel');
        $this->__origin  = $this->model('ProductOriginsModel');
        $this->__stock   = $this->model('StocksModel');
        $this->__img     = $this->model('PartImagesModel');
        $this->__attr    = $this->model('PartAttributeValuesModel');
        $this->__related = $this->model('PartRelatedModel');
        $this->__fitment = $this->model('PartFitmentsModel');
        $this->__review  = $this->model('ProductReviewsModel');
        $this->__member  = $this->model('MembersModel');
    }

    private function isMember(){ return !empty(Session::get('dataMember')); }

    /**
     * Trang Khuyến mãi (/khuyen-mai).
     *
     * Dùng lại đúng bộ máy của trang Sản phẩm, chỉ ép bộ lọc promo. Không tạo
     * bảng "chương trình khuyến mãi" riêng: hàng khuyến mãi ở đây được định
     * nghĩa là hàng có `parts.sale_price` — cùng nguồn với giá gạch ngang đang
     * hiển thị trên thẻ sản phẩm, nên không thể lệch nhau.
     */
    public function promo(){
        $this->index(true);
    }

    /**
     * @param bool $promoOnly Ép chỉ hiện hàng khuyến mãi (trang /khuyen-mai)
     */
    public function index($promoOnly = false){
        $g = $_GET;
        $filters = [
            'categoryIds' => $this->intArray($g['category'] ?? []),
            'brandIds'    => $this->intArray($g['brand'] ?? []),
            'originIds'   => $this->intArray($g['origin'] ?? []),
            'priceMin'    => isset($g['price_min']) ? preg_replace('/[^\d]/', '', $g['price_min']) : '',
            'priceMax'    => isset($g['price_max']) ? preg_replace('/[^\d]/', '', $g['price_max']) : '',
            'promo'       => $promoOnly || !empty($g['promo']),
            'keyword'     => isset($g['q']) ? trim($g['q']) : '',
            // Bộ lọc xe ở header. Tên tham số phải có tiền tố car_ vì `brand`
            // đã là thương hiệu phụ tùng (Bosch, Denso) ở dòng ngay trên.
            'carBrandId'    => !empty($g['car_brand']) ? (int) $g['car_brand'] : 0,
            'carBodyTypeId' => !empty($g['car_body'])  ? (int) $g['car_body']  : 0,
            'carModelId'    => !empty($g['car_model']) ? (int) $g['car_model'] : 0,
            'carYearId'     => !empty($g['car_year'])  ? (int) $g['car_year']  : 0,
            'sort'        => isset($g['sort']) ? $g['sort'] : '',
        ];

        // Chọn danh mục gốc phải ra cả hàng của danh mục con: phụ tùng gán vào
        // danh mục lá nên lọc đúng id gốc luôn ra rỗng.
        //
        // Chỉ mở rộng cho câu truy vấn. $filters giữ nguyên lựa chọn thật của
        // người dùng vì view dùng nó để tick lại ô — mở rộng luôn thì mọi danh
        // mục con cũng hiện đã tick, sai với thao tác của khách.
        $queryFilters = $filters;
        if (!empty($queryFilters['categoryIds'])){
            $queryFilters['categoryIds'] = $this->__cat->expandWithDescendants($queryFilters['categoryIds']);
        }

        $page  = !empty($g['page']) ? max(1, (int) $g['page']) : 1;
        $total = $this->__part->storefrontCount($queryFilters);
        $pages = (int) ceil($total / self::PER_PAGE);
        $list  = $this->__part->storefront($queryFilters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        // Tồn kho: chỉ tính cho thành viên (TASK_79)
        $stockMap = [];
        if ($this->isMember()){
            foreach ($list as $p){ $stockMap[(int) $p['id']] = $this->__stock->sellableByPart((int) $p['id']); }
        }

        // Ảnh đại diện cho thẻ sản phẩm
        $imgMap = [];
        foreach ($list as $p){ $imgMap[(int) $p['id']] = $this->__img->primaryFor((int) $p['id']); }

        $this->__data['sub_content'] = 'storefront/list';
        if ($promoOnly){
            $this->__data['page_title'] = 'Khuyến mãi';
        } else {
            $this->__data['page_title'] = !empty($filters['keyword']) ? ('Tìm: ' . $filters['keyword']) : 'Sản phẩm';
        }

        $c = &$this->__data['content'];
        // View dùng cờ này để đổi tiêu đề/breadcrumb, đổi action của form lọc
        // và ẩn ô "Chỉ hàng khuyến mãi" (đang bị ép nên bỏ tick cũng vô nghĩa).
        $c['promoPage'] = (bool) $promoOnly;
        $c['list']       = $list;
        $c['total']      = $total;
        $c['page']       = $page;
        $c['pages']      = $pages;
        $c['filters']    = $filters;
        $c['query']      = $g;
        $c['isMember']   = $this->isMember();
        $c['stockMap']   = $stockMap;
        $c['imgMap']     = $imgMap;
        // Facet options
        $c['catOptions']    = $this->__cat->getTree();
        $c['brandOptions']  = $this->__pbrand->getLists();
        $c['originOptions'] = $this->__origin->getLists();
        // Không truyền danh mục xe xuống nữa: bộ lọc xe đã chuyển hẳn lên thanh
        // lọc ở header, nó tự nạp danh mục trong partial car-filter.php.

        $this->render('layouts/storefront/master', $this->__data);
    }

    /**
     * GỢI Ý TÌM KIẾM (JSON) — cho ô tìm ở thanh lọc trên header.
     *
     * Dùng lại đúng PartsModel::storefront() của trang danh sách, nên gợi ý
     * và kết quả khi bấm Enter luôn khớp nhau: cùng bộ lọc xe đang chọn, cùng
     * cách tìm theo tên/mã/mã OEM, cùng điều kiện chỉ lấy hàng đang bật.
     *
     * KHÔNG trả tồn kho: tồn chỉ hiện cho thành viên (TASK_79), mà endpoint này
     * công khai — trả kèm là rò rỉ số liệu kho cho người chưa đăng nhập.
     */
    public function suggest(){
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $g  = $_GET;
        $kw = isset($g['q']) ? trim($g['q']) : '';

        // Dưới 2 ký tự thì mọi thứ đều khớp -> vừa vô nghĩa vừa nặng DB.
        if (mb_strlen($kw) < 2){
            echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $rows = $this->__part->storefront([
            'keyword'       => $kw,
            'carBrandId'    => !empty($g['car_brand']) ? (int) $g['car_brand'] : 0,
            'carBodyTypeId' => !empty($g['car_body'])  ? (int) $g['car_body']  : 0,
            'carModelId'    => !empty($g['car_model']) ? (int) $g['car_model'] : 0,
            'carYearId'     => !empty($g['car_year'])  ? (int) $g['car_year']  : 0,
            'sort'          => 'name',
        ], self::SUGGEST_LIMIT);

        $items = [];
        foreach ($rows as $p){
            $img = $this->__img->primaryFor((int) $p['id']);
            $items[] = [
                'name'  => $p['name'],
                'code'  => $p['code'],
                'url'   => _WEB_URL . '/san-pham/' . $p['slug'],
                'image' => !empty($img) ? (_WEB_URL . '/public/assets/uploads/parts/' . $img) : '',
                'price' => (float) (!empty($p['sale_price']) ? $p['sale_price'] : $p['price']),
            ];
        }

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function detail($slug = ''){
        $part = $this->__part->getBySlugFull($slug);
        if (empty($part)){
            $this->__data['sub_content'] = 'storefront/notfound';
            $this->__data['page_title']  = 'Không tìm thấy sản phẩm';
            $this->__data['content']     = [];
            $this->render('layouts/storefront/master', $this->__data);
            return;
        }

        $pid = (int) $part['id'];
        $this->__data['sub_content'] = 'storefront/detail';
        $this->__data['page_title']  = $part['name'];

        $c = &$this->__data['content'];
        $c['part']     = $part;
        $c['images']   = $this->__img->getByPart($pid);
        $c['attrs']    = $this->__attr->getByPart($pid);
        $c['related']  = $this->__related->getRelatedParts($pid);
        $c['fitments'] = $this->__fitment->getCarYearsByPart($pid);
        $c['isMember'] = $this->isMember();
        $c['stock']    = $this->isMember() ? $this->__stock->sellableByPart($pid) : null;
        $c['reviews']  = $this->__review->getApprovedByPart($pid);
        $c['reviewSummary'] = $this->__review->summary($pid);
        $c['reviewMsg']  = Session::flash('reviewMsg');

        // --- Sản phẩm liên quan ---------------------------------------------
        // Dùng lại đúng hai thứ vừa nạp ở trên, không truy vấn lại:
        //   fitments -> id các đời xe, để tìm hàng lắp chung xe
        //   related  -> "phụ kiện đi kèm" đã hiện ngay phía trên, phải loại ra
        //               kẻo hai khối cạnh nhau hiện y hệt nhau
        $doiXeIds = [];
        foreach ($c['fitments'] ?: [] as $ft){ $doiXeIds[] = (int) $ft['car_year_id']; }

        $diKemIds = [];
        foreach ($c['related'] ?: [] as $r){ $diKemIds[] = (int) $r['id']; }

        $c['lienQuan'] = $this->__part->lienQuan($part, $doiXeIds, 8, $diKemIds);

        // Ảnh + tồn cho thẻ sản phẩm. Vòng lặp này chỉ chạy tối đa 8 lần nên
        // để nguyên N+1 ở đây là chấp nhận được — giống trang chủ.
        $imgMap = $stockMap = [];
        foreach ($c['lienQuan'] as $lq){
            $rid = (int) $lq['id'];
            $imgMap[$rid] = $this->__img->primaryFor($rid);
            // Giữ quy tắc TASK_79: tồn kho chỉ hiện với thành viên đã đăng nhập.
            if ($c['isMember']) $stockMap[$rid] = $this->__stock->sellableByPart($rid);
        }
        $c['imgMap']   = $imgMap;
        $c['stockMap'] = $stockMap;
        $descParts = array_filter([$part['name'], $part['brand_name'] ?? '', $part['category_name'] ?? '']);
        $c['seo'] = [
            'description' => !empty($part['description']) ? mb_substr(strip_tags($part['description']), 0, 300) : ('Mua ' . implode(' - ', $descParts) . ' chính hãng tại Tân Phát.'),
            'type'        => 'product',
        ];

        $this->render('layouts/storefront/master', $this->__data);
    }

    /** Thành viên gửi đánh giá sản phẩm (TASK_84) — chờ admin duyệt */
    public function postReview(){
        $f = $_POST;
        $partId = !empty($f['part_id']) ? (int) $f['part_id'] : 0;
        $part = $partId > 0 ? $this->__part->getDetail($partId) : null;
        if (empty($part)){ $this->__responseRedirect('san-pham'); return; }

        $backUrl = 'san-pham/' . $part['slug'];
        $memberId = Session::get('dataMember');
        if (empty($memberId)){
            Session::flash('reviewMsg', 'Vui lòng đăng nhập thành viên để đánh giá.');
            $this->__responseRedirect($backUrl); return;
        }
        $member = $this->__member->getDetail($memberId);
        $rating = isset($f['rating']) ? (int) $f['rating'] : 5;
        if ($rating < 1) $rating = 1; if ($rating > 5) $rating = 5;
        $comment = isset($f['comment']) ? trim($f['comment']) : '';
        if ($comment === ''){
            Session::flash('reviewMsg', 'Vui lòng nhập nội dung đánh giá.');
            $this->__responseRedirect($backUrl); return;
        }

        $this->__review->submit([
            'part_id'     => $partId,
            'member_id'   => (int) $memberId,
            'author_name' => !empty($member['name']) ? $member['name'] : 'Thành viên',
            'rating'      => $rating,
            'comment'     => $comment,
        ]);
        Session::flash('reviewMsg', 'Cảm ơn! Đánh giá của bạn sẽ hiển thị sau khi được duyệt.');
        $this->__responseRedirect($backUrl);
    }

    private function __responseRedirect($path){
        (new \App\core\Response())->redirect($path);
    }

    private function intArray($v){
        if (!is_array($v)) $v = ($v === '' || $v === null) ? [] : [$v];
        $out = [];
        foreach ($v as $x){ $x = (int) $x; if ($x > 0) $out[] = $x; }
        return $out;
    }
}

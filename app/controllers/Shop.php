<?php

use App\core\Controller;
use App\core\Session;

/**
 * STOREFRONT — Danh sách sản phẩm (lọc facet, TASK_92) + chi tiết.
 * Tồn kho chỉ hiện với THÀNH VIÊN đã đăng nhập (TASK_79).
 */
class Shop extends Controller {

    const PER_PAGE = 12;

    private $__data = [];
    private $__part, $__cat, $__pbrand, $__origin, $__cbrand, $__cmodel, $__stock;
    private $__img, $__attr, $__related, $__fitment, $__review, $__member;

    function __construct(){
        $this->__part    = $this->model('PartsModel');
        $this->__cat     = $this->model('PartCategoriesModel');
        $this->__pbrand  = $this->model('ProductBrandsModel');
        $this->__origin  = $this->model('ProductOriginsModel');
        $this->__cbrand  = $this->model('CarBrandsModel');
        $this->__cmodel  = $this->model('CarModelsModel');
        $this->__stock   = $this->model('StocksModel');
        $this->__img     = $this->model('PartImagesModel');
        $this->__attr    = $this->model('PartAttributeValuesModel');
        $this->__related = $this->model('PartRelatedModel');
        $this->__fitment = $this->model('PartFitmentsModel');
        $this->__review  = $this->model('ProductReviewsModel');
        $this->__member  = $this->model('MembersModel');
    }

    private function isMember(){ return !empty(Session::get('dataMember')); }

    public function index(){
        $g = $_GET;
        $filters = [
            'categoryIds' => $this->intArray($g['category'] ?? []),
            'brandIds'    => $this->intArray($g['brand'] ?? []),
            'originIds'   => $this->intArray($g['origin'] ?? []),
            'priceMin'    => isset($g['price_min']) ? preg_replace('/[^\d]/', '', $g['price_min']) : '',
            'priceMax'    => isset($g['price_max']) ? preg_replace('/[^\d]/', '', $g['price_max']) : '',
            'promo'       => !empty($g['promo']),
            'keyword'     => isset($g['q']) ? trim($g['q']) : '',
            'carModelId'  => !empty($g['car_model']) ? (int) $g['car_model'] : 0,
            'sort'        => isset($g['sort']) ? $g['sort'] : '',
        ];

        $page  = !empty($g['page']) ? max(1, (int) $g['page']) : 1;
        $total = $this->__part->storefrontCount($filters);
        $pages = (int) ceil($total / self::PER_PAGE);
        $list  = $this->__part->storefront($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        // Tồn kho: chỉ tính cho thành viên (TASK_79)
        $stockMap = [];
        if ($this->isMember()){
            foreach ($list as $p){ $stockMap[(int) $p['id']] = $this->__stock->totalByPart((int) $p['id']); }
        }

        $this->__data['sub_content'] = 'storefront/list';
        $this->__data['page_title']  = !empty($filters['keyword']) ? ('Tìm: ' . $filters['keyword']) : 'Sản phẩm';

        $c = &$this->__data['content'];
        $c['list']       = $list;
        $c['total']      = $total;
        $c['page']       = $page;
        $c['pages']      = $pages;
        $c['filters']    = $filters;
        $c['query']      = $g;
        $c['isMember']   = $this->isMember();
        $c['stockMap']   = $stockMap;
        // Facet options
        $c['catOptions']    = $this->__cat->getTree();
        $c['brandOptions']  = $this->__pbrand->getLists();
        $c['originOptions'] = $this->__origin->getLists();
        $c['carBrands']     = $this->__cbrand->getLists();
        $c['carModels']     = $this->__cmodel->getLists();

        $this->render('layouts/storefront/master', $this->__data);
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
        $c['stock']    = $this->isMember() ? $this->__stock->totalByPart($pid) : null;
        $c['reviews']  = $this->__review->getApprovedByPart($pid);
        $c['reviewSummary'] = $this->__review->summary($pid);
        $c['reviewMsg']  = Session::flash('reviewMsg');

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

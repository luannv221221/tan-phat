<?php

use App\core\Controller;

/**
 * STOREFRONT — Trang chủ.
 */
class Home extends Controller {

    private $__data = [];
    private $__part, $__cat, $__brand, $__img;

    function __construct(){
        $this->__part  = $this->model('PartsModel');
        $this->__cat   = $this->model('PartCategoriesModel');
        $this->__brand = $this->model('CarBrandsModel');
        $this->__img   = $this->model('PartImagesModel');
    }

    public function index(){
        $this->__data['sub_content'] = 'storefront/home';
        $this->__data['page_title']  = 'Tân Phát — Phụ tùng & thiết bị gara ô tô';

        $promo  = $this->__part->storefront(['promo' => true, 'sort' => 'new'], 8);
        $newest = $this->__part->storefront(['sort' => 'new'], 10);

        // Ảnh đại diện cho thẻ sản phẩm (giống trang /san-pham)
        $imgMap = [];
        foreach (array_merge($promo, $newest) as $p){
            $pid = (int) $p['id'];
            if (!isset($imgMap[$pid])){ $imgMap[$pid] = $this->__img->primaryFor($pid); }
        }

        $this->__data['content']['promo']  = $promo;
        $this->__data['content']['newest'] = $newest;
        $this->__data['content']['imgMap'] = $imgMap;
        $this->__data['content']['cats']   = $this->__cat->getTree();
        $this->__data['content']['brands'] = $this->__brand->getLists();

        $this->render('layouts/storefront/master', $this->__data);
    }
}

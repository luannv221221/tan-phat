<?php

use App\core\Controller;

/**
 * STOREFRONT — Trang chủ.
 */
class Home extends Controller {

    private $__data = [];
    private $__part, $__cat, $__brand;

    function __construct(){
        $this->__part  = $this->model('PartsModel');
        $this->__cat   = $this->model('PartCategoriesModel');
        $this->__brand = $this->model('CarBrandsModel');
    }

    public function index(){
        $this->__data['sub_content'] = 'storefront/home';
        $this->__data['page_title']  = 'Tân Phát — Phụ tùng & thiết bị gara ô tô';

        $this->__data['content']['promo']  = $this->__part->storefront(['promo' => true, 'sort' => 'new'], 8);
        $this->__data['content']['newest'] = $this->__part->storefront(['sort' => 'new'], 10);
        $this->__data['content']['cats']   = $this->__cat->getTree();
        $this->__data['content']['brands'] = $this->__brand->getLists();

        $this->render('layouts/storefront/master', $this->__data);
    }
}

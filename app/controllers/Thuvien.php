<?php

use App\core\Controller;

/** STOREFRONT — Thư viện ảnh/video (public). */
class Thuvien extends Controller {

    private $__data = [];
    private $__model, $__item;

    function __construct(){
        $this->__model = $this->model('GalleriesModel');
        $this->__item  = $this->model('GalleryItemsModel');
    }

    public function index(){
        $this->__data['sub_content'] = 'storefront/gallery_list';
        $this->__data['page_title']  = 'Thư viện ảnh & video';
        $this->__data['content']['list'] = $this->__model->getPublished();
        $this->render('layouts/storefront/master', $this->__data);
    }

    public function detail($slug = ''){
        $g = $this->__model->getBySlugPublished($slug);
        if (empty($g)){
            $this->__data['sub_content'] = 'storefront/notfound';
            $this->__data['page_title']  = 'Không tìm thấy';
            $this->__data['content']     = [];
            $this->render('layouts/storefront/master', $this->__data);
            return;
        }
        $this->__data['sub_content'] = 'storefront/gallery_detail';
        $this->__data['page_title']  = $g['name'];
        $this->__data['content']['gallery'] = $g;
        $this->__data['content']['items']   = $this->__item->getByGallery($g['id']);
        $this->__data['content']['seo'] = ['description' => $g['description'], 'image' => $g['cover'], 'type' => 'website'];
        $this->render('layouts/storefront/master', $this->__data);
    }
}

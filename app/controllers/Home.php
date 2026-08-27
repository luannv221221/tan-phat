<?php

use App\core\Controller;
use App\core\Session;

/**
 * STOREFRONT — Trang chủ.
 */
class Home extends Controller {

    private $__data = [];
    private $__part, $__cat, $__brand, $__news, $__img, $__banner, $__stock, $__video;

    function __construct(){
        $this->__part   = $this->model('PartsModel');
        $this->__cat    = $this->model('PartCategoriesModel');
        $this->__brand  = $this->model('CarBrandsModel');
        $this->__news   = $this->model('NewsModel');
        $this->__img    = $this->model('PartImagesModel');
        $this->__banner = $this->model('BannersModel');
        $this->__stock  = $this->model('StocksModel');
        $this->__video  = $this->model('GalleryItemsModel');
    }

    public function index(){
        $this->__data['sub_content'] = 'storefront/home';
        $this->__data['page_title']  = 'Tân Phát — Phụ tùng & thiết bị gara ô tô';

        $promo  = $this->__part->storefront(['promo' => true, 'sort' => 'new'], 8);
        $newest = $this->__part->storefront(['sort' => 'new'], 8);

        // Ảnh đại diện cho thẻ sản phẩm (tránh N+1 lặp truy vấn trong view)
        $imgMap = [];
        foreach (array_merge($promo, $newest) as $p){
            $pid = (int) $p['id'];
            if (!isset($imgMap[$pid])) $imgMap[$pid] = $this->__img->primaryFor($pid);
        }

        // Tồn kho ngay trên thẻ sản phẩm, giống trang danh sách.
        // Vẫn giữ quy tắc TASK_79: chỉ THÀNH VIÊN đã đăng nhập mới thấy tồn kho.
        $isMember = !empty(Session::get('dataMember'));
        $stockMap = [];
        if ($isMember){
            foreach (array_keys($imgMap) as $pid){
                $stockMap[$pid] = $this->__stock->sellableByPart($pid);
            }
        }

        $this->__data['content']['promo']    = $promo;
        $this->__data['content']['newest']   = $newest;
        $this->__data['content']['imgMap']   = $imgMap;
        $this->__data['content']['isMember'] = $isMember;
        $this->__data['content']['stockMap'] = $stockMap;
        $this->__data['content']['cats']    = $this->__cat->getTree();
        $this->__data['content']['brands']  = $this->__brand->getLists();
        $this->__data['content']['news']    = $this->__news->getPublished(0, 4);
        $this->__data['content']['banners'] = $this->__banner->getActive();

        // Khoi "Video" — lay tu thu vien anh/video (album da dang).
        // 1 video lam khung lon + toi da 4 cai xep ben canh.
        $this->__data['content']['videos']  = $this->__video->getVideosPublished(5);

        $this->render('layouts/storefront/master', $this->__data);
    }
}

<?php

use App\core\Controller;

/**
 * STOREFRONT — Tin tức (public).
 */
class Tintuc extends Controller {

    const PER_PAGE = 9;

    private $__data = [];
    private $__model, $__cat;

    function __construct(){
        $this->__model = $this->model('NewsModel');
        $this->__cat   = $this->model('NewsCategoriesModel');
    }

    public function index(){
        $g = $_GET;
        $catSlug = isset($g['cat']) ? trim($g['cat']) : '';
        $catId = 0; $cat = null;
        if ($catSlug !== ''){
            $cat = $this->__cat->findBySlug($catSlug);
            if (!empty($cat)) $catId = (int) $cat['id'];
        }
        $page  = !empty($g['page']) ? max(1, (int) $g['page']) : 1;
        $total = $this->__model->countPublished($catId);
        $pages = (int) ceil($total / self::PER_PAGE);

        $this->__data['sub_content'] = 'storefront/news_list';
        $this->__data['page_title']  = !empty($cat) ? ('Tin tức: ' . $cat['name']) : 'Tin tức';
        $c = &$this->__data['content'];
        $c['list']       = $this->__model->getPublished($catId, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        $c['categories'] = $this->__cat->getActive();
        $c['cat']        = $cat;
        $c['page']       = $page;
        $c['pages']      = $pages;
        $c['total']      = $total;
        $this->render('layouts/storefront/master', $this->__data);
    }

    public function detail($slug = ''){
        $news = $this->__model->getBySlugPublished($slug);
        if (empty($news)){
            $this->__data['sub_content'] = 'storefront/notfound';
            $this->__data['page_title']  = 'Không tìm thấy';
            $this->__data['content']     = [];
            $this->render('layouts/storefront/master', $this->__data);
            return;
        }
        $this->__model->incrementView((int) $news['id']);

        $this->__data['sub_content'] = 'storefront/news_detail';
        $this->__data['page_title']  = $news['title'];
        $c = &$this->__data['content'];
        $c['news']       = $news;
        $c['latest']     = $this->__model->getPublished(0, 5);
        $c['categories'] = $this->__cat->getActive();
        $c['seo'] = [
            'description' => !empty($news['meta_description']) ? $news['meta_description'] : $news['summary'],
            'image'       => $news['thumbnail'],
            'type'        => 'article',
        ];
        if (!empty($news['meta_title'])) $this->__data['page_title'] = $news['meta_title'];
        $this->render('layouts/storefront/master', $this->__data);
    }
}

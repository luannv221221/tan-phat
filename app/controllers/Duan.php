<?php

use App\core\Controller;

/**
 * STOREFRONT — Dự án / công trình (public).
 */
class Duan extends Controller {

    const PER_PAGE = 9;

    private $__data = [];
    private $__model;

    function __construct(){
        $this->__model = $this->model('ProjectsModel');
    }

    public function index(){
        $g = $_GET;
        $page  = !empty($g['page']) ? max(1, (int) $g['page']) : 1;
        $total = $this->__model->countPublished();
        $pages = (int) ceil($total / self::PER_PAGE);

        $this->__data['sub_content'] = 'storefront/project_list';
        $this->__data['page_title']  = 'Dự án đã thực hiện';
        $c = &$this->__data['content'];
        $c['list']  = $this->__model->getPublished(self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        $c['page']  = $page;
        $c['pages'] = $pages;
        $c['total'] = $total;
        $this->render('layouts/storefront/master', $this->__data);
    }

    public function detail($slug = ''){
        $project = $this->__model->getBySlugPublished($slug);
        if (empty($project)){
            $this->__data['sub_content'] = 'storefront/notfound';
            $this->__data['page_title']  = 'Không tìm thấy';
            $this->__data['content']     = [];
            $this->render('layouts/storefront/master', $this->__data);
            return;
        }
        $this->__data['sub_content'] = 'storefront/project_detail';
        $this->__data['page_title']  = $project['name'];
        $c = &$this->__data['content'];
        $c['project'] = $project;
        $c['others']  = $this->__model->getPublished(4);
        $this->render('layouts/storefront/master', $this->__data);
    }
}

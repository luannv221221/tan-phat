<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CMS — Quản lý tin tức (CRUD). Dựng lại từ stub.
 */
class News extends Controller {

    private $__data = [];
    private $__model, $__cat, $__request, $__response;

    private $routeBase = 'news';
    private $labelOne  = 'tin';
    private $labelMany = 'Quản lý tin tức';
    private $viewDir   = 'admin/news';

    function __construct(){
        $this->__model    = $this->model('NewsModel');
        $this->__cat      = $this->model('NewsCategoriesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $f = $this->__request->getFields();
        $status  = isset($f['status']) ? trim($f['status']) : '';
        $keyword = isset($f['q']) ? trim($f['q']) : '';
        $this->__data['content']['page_name']    = $this->labelMany;
        $this->__data['content']['dataList']     = $this->__model->getLists($status, $keyword);
        $this->__data['content']['filterStatus'] = $status;
        $this->__data['content']['filterKeyword']= $keyword;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name']  = 'Thêm ' . $this->labelOne;
        $this->__data['content']['categories'] = $this->__cat->getActive();
        $this->__data['content']['item']       = null;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $data = $this->buildData();
        if (!empty($this->__model->findBySlug($data['slug']))){ $this->flash(['slug' => 'Đường dẫn đã tồn tại'], 'add'); return; }
        $up = upload_image('thumbnail_file', 'news', !empty($data['title']) ? $data['title'] : 'news');
        if ($up['status'] === 'error'){ $this->flash(['thumbnail_file' => $up['message']], 'add'); return; }
        if ($up['status'] === 'ok') $data['thumbnail'] = $up['path'];
        $data['created_by'] = Session::get('dataUser');
        $this->__model->add($data);
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name']  = 'Sửa ' . $this->labelOne;
        $this->__data['content']['categories'] = $this->__cat->getActive();
        $this->__data['content']['item']       = $item;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $data = $this->buildData();
        $ex = $this->__model->findBySlug($data['slug']);
        if (!empty($ex) && $ex['id'] != $id){ $this->flash(['slug' => 'Đường dẫn đã thuộc bài khác'], 'edit/' . $id); return; }
        $up = upload_image('thumbnail_file', 'news', !empty($data['title']) ? $data['title'] : 'news');
        if ($up['status'] === 'error'){ $this->flash(['thumbnail_file' => $up['message']], 'edit/' . $id); return; }
        if ($up['status'] === 'ok') $data['thumbnail'] = $up['path'];
        // giữ published_at cũ nếu đã đăng trước đó
        if (!empty($item['published_at']) && $data['is_published']) $data['published_at'] = $item['published_at'];
        $this->__model->edit($data, $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====
    private function validate(){
        $f = $this->__request->getFields();
        $errors = [];
        if (empty($f['title']) || trim($f['title']) === '') $errors['title'] = 'Nhập tiêu đề';
        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();
        $pub = !empty($f['is_published']) ? 1 : 0;
        $catId = !empty($f['category_id']) ? (int) $f['category_id'] : 0;
        return [
            'category_id'  => ($catId > 0 && !empty($this->__cat->getDetail($catId))) ? $catId : null,
            'title'        => trim($f['title']),
            'slug'         => slugify(!empty($f['slug']) ? $f['slug'] : $f['title']),
            'meta_title'   => !empty($f['meta_title']) ? trim($f['meta_title']) : null,
            'meta_description' => !empty($f['meta_description']) ? trim($f['meta_description']) : null,
            'summary'      => !empty($f['summary']) ? trim($f['summary']) : null,
            'content'      => isset($f['content']) ? $f['content'] : null,
            'thumbnail'    => !empty($f['thumbnail']) ? trim($f['thumbnail']) : null,
            'is_published' => $pub,
            'published_at' => $pub ? date('Y-m-d H:i:s') : null,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

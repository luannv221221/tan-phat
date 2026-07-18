<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CMS — Dự án / công trình (portfolio). URL admin/du-an.
 * Tên controller KHÁC `Projects` (đã là Mã vụ việc kế toán).
 */
class Projectportfolio extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'du-an';
    private $labelOne  = 'dự án';
    private $labelMany = 'Dự án / công trình';
    private $viewDir   = 'admin/du-an';

    function __construct(){
        $this->__model    = $this->model('ProjectsModel');
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
        $this->__data['content']['page_name']     = $this->labelMany;
        $this->__data['content']['dataList']      = $this->__model->getLists($status, $keyword);
        $this->__data['content']['filterStatus']  = $status;
        $this->__data['content']['filterKeyword'] = $keyword;
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['msgError']      = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['item']      = null;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->__request->rules(['name' => 'required|min:1']);
        $this->__request->message(['name.required' => 'Nhập tên dự án', 'name.min' => 'Nhập tên dự án']);
        if (!$this->__request->validate()){ $this->flashErr('add'); return; }
        $data = $this->buildData();
        if ($data['slug'] === ''){ $this->flashOne('slug', 'Không sinh được slug, nhập thủ công', 'add'); return; }
        if (!empty($this->__model->findBySlug($data['slug']))){ $this->flashOne('slug', 'Slug đã tồn tại', 'add'); return; }
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
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__request->rules(['name' => 'required|min:1']);
        $this->__request->message(['name.required' => 'Nhập tên dự án', 'name.min' => 'Nhập tên dự án']);
        if (!$this->__request->validate()){ $this->flashErr('edit/' . $id); return; }
        $data = $this->buildData();
        if ($data['slug'] === ''){ $this->flashOne('slug', 'Không sinh được slug, nhập thủ công', 'edit/' . $id); return; }
        $ex = $this->__model->findBySlug($data['slug']);
        if (!empty($ex) && $ex['id'] != $id){ $this->flashOne('slug', 'Slug đã thuộc dự án khác', 'edit/' . $id); return; }
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

    private function buildData(){
        $f = $this->__request->getFields();
        return [
            'name'         => trim($f['name']),
            'slug'         => slugify(!empty($f['slug']) ? $f['slug'] : $f['name']),
            'client'       => !empty($f['client']) ? trim($f['client']) : null,
            'location'     => !empty($f['location']) ? trim($f['location']) : null,
            'summary'      => !empty($f['summary']) ? trim($f['summary']) : null,
            'content'      => isset($f['content']) ? $f['content'] : null,
            'thumbnail'    => !empty($f['thumbnail']) ? trim($f['thumbnail']) : null,
            'completed_at' => !empty($f['completed_at']) ? $f['completed_at'] : null,
            'is_published' => !empty($f['is_published']) ? 1 : 0,
            'sort_order'   => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
        ];
    }
    private function flashErr($back){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
    private function flashOne($field, $msg, $back){
        Session::flash('errors', [$field => $msg]);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

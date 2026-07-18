<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KT-1 — Danh mục tài khoản (cây). Có mã (code) duy nhất, loại TK, cờ chi tiết.
 */
class Accounts extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'accounts';
    private $labelOne  = 'tài khoản';
    private $labelMany = 'Danh mục tài khoản';
    private $viewDir   = 'admin/accounts';

    function __construct(){
        $this->__model    = $this->model('AccAccountsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['types']     = AccAccountsModel::$types;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $this->__data['content']['page_name'] = $this->labelMany;
        $this->__data['content']['dataList']  = $this->__model->getTree();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name']  = 'Thêm ' . $this->labelOne;
        $this->__data['content']['tree']       = $this->__model->getTree();
        $this->__data['content']['excludeIds'] = [];
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput(null);
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $this->__model->add($this->buildData());
        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $exclude   = $this->__model->getDescendantIds($id);
        $exclude[] = (int) $id;

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name']  = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']       = $item;
        $this->__data['content']['tree']       = $this->__model->getTree();
        $this->__data['content']['excludeIds'] = $exclude;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $errors = $this->validateInput($id);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        // chống vòng lặp cây
        $data = $this->buildData();
        if ($data['parent_id'] !== null){
            if ($data['parent_id'] == $id
                || in_array((int) $data['parent_id'], $this->__model->getDescendantIds($id), true)){
                $this->flash(['parent_id' => 'Không thể đặt chính nó hoặc tài khoản con làm cha'], 'edit/' . $id);
                return;
            }
        }

        $this->__model->edit($data, $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $result = $this->__model->remove($id);
        if ($result === false){
            Session::flash('msgError', 'Không xoá được: tài khoản này còn tài khoản con.');
        } else {
            Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        }
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function validateInput($id){
        $f = $this->__request->getFields();
        $errors = [];

        $code = isset($f['code']) ? trim($f['code']) : '';
        if ($code === ''){
            $errors['code'] = 'Số hiệu tài khoản không được để trống';
        } else {
            $ex = $this->__model->findByCode($code);
            if (!empty($ex) && ($id === null || $ex['id'] != $id)){
                $errors['code'] = 'Số hiệu tài khoản này đã tồn tại';
            }
        }
        if (!isset($f['name']) || trim($f['name']) === ''){
            $errors['name'] = 'Tên tài khoản không được để trống';
        }
        if (!empty($f['parent_id']) && empty($this->__model->getDetail((int) $f['parent_id']))){
            $errors['parent_id'] = 'Tài khoản cha không hợp lệ';
        }
        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();
        $type = isset($f['type']) && isset(AccAccountsModel::$types[$f['type']]) ? $f['type'] : 'other';

        return [
            'code'       => trim($f['code']),
            'name'       => trim($f['name']),
            'parent_id'  => !empty($f['parent_id']) ? (int) $f['parent_id'] : null,
            'type'       => $type,
            'is_detail'  => !empty($f['is_detail']) ? 1 : 0,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Danh mục phụ tùng (part_categories) — cây phân cấp cha-con.
 *
 * parent_id tự tham chiếu (ON DELETE RESTRICT: còn con là không xoá được).
 * Khi sửa, KHÔNG cho chọn chính nó hoặc hậu duệ làm cha (tránh vòng lặp).
 */
class Partcategories extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'part-categories';
    private $labelOne  = 'danh mục';
    private $labelMany = 'Danh mục phụ tùng';
    private $viewDir   = 'admin/part-categories';

    function __construct(){
        $this->__model    = $this->model('PartCategoriesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    // ================= Danh sách =================

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

    // ================= Thêm =================

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['tree']      = $this->__model->getTree();
        $this->__data['content']['excludeIds']= [];
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $this->applyRules();

        if (!$this->__request->validate()){
            $this->flashErrors();
            $this->__response->redirect('admin/' . $this->routeBase . '/add');
            return;
        }

        $data = $this->buildData();

        if ($data['parent_id'] !== null && empty($this->__model->getDetail($data['parent_id']))){
            $this->flashOne(['parent_id' => 'Danh mục cha không hợp lệ'], 'add');
            return;
        }

        if ($data['slug'] === ''){
            $this->flashOne(['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.'], 'add');
            return;
        }

        if (!empty($this->__model->findBySlug($data['slug']))){
            $this->flashOne(['slug' => 'Đường dẫn (slug) này đã tồn tại'], 'add');
            return;
        }

        $this->__model->add($data);

        Session::flash('msg', 'Thêm ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Sửa =================

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        // Không cho chọn chính nó hoặc hậu duệ làm cha
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

        $this->applyRules();

        if (!$this->__request->validate()){
            $this->flashErrors();
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
            return;
        }

        $data = $this->buildData();

        // Không cho đặt cha là chính nó hoặc hậu duệ (tránh vòng lặp cây)
        if ($data['parent_id'] !== null){
            if ($data['parent_id'] == $id){
                $this->flashOne(['parent_id' => 'Không thể đặt chính nó làm danh mục cha'], 'edit/' . $id);
                return;
            }
            $descendants = $this->__model->getDescendantIds($id);
            if (in_array((int) $data['parent_id'], $descendants, true)){
                $this->flashOne(['parent_id' => 'Không thể đặt một danh mục con làm cha (tạo vòng lặp)'], 'edit/' . $id);
                return;
            }
            if (empty($this->__model->getDetail($data['parent_id']))){
                $this->flashOne(['parent_id' => 'Danh mục cha không hợp lệ'], 'edit/' . $id);
                return;
            }
        }

        if ($data['slug'] === ''){
            $this->flashOne(['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.'], 'edit/' . $id);
            return;
        }

        $existing = $this->__model->findBySlug($data['slug']);
        if (!empty($existing) && $existing['id'] != $id){
            $this->flashOne(['slug' => 'Đường dẫn (slug) này đã thuộc về bản ghi khác'], 'edit/' . $id);
            return;
        }

        $this->__model->edit($data, $id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Xoá =================

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $result = $this->__model->remove($id);

        if ($result === false){
            Session::flash('msgError',
                'Không xoá được: ' . $this->labelOne . ' này còn danh mục con. Xoá hoặc chuyển danh mục con trước.');
        } else {
            Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        }

        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Helper =================

    private function applyRules(){
        $this->__request->rules([
            'name' => 'required|min:1',
        ]);
        $this->__request->message([
            'name.required' => 'Tên không được để trống',
            'name.min'      => 'Tên không được để trống',
        ]);
    }

    private function buildData(){
        $f = $this->__request->getFields();

        return [
            'parent_id'   => !empty($f['parent_id']) ? (int) $f['parent_id'] : null,
            'name'        => trim($f['name']),
            'slug'        => slugify(!empty($f['slug']) ? $f['slug'] : $f['name']),
            'description' => !empty($f['description']) ? trim($f['description']) : null,
            'sort_order'  => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'      => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flashErrors(){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
    }

    private function flashOne($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

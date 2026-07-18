<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Model xe (car_models) — Vios, Morning... thuộc 1 hãng, có thể gắn 1 kiểu dáng.
 *
 * Quan hệ:
 *   - brand_id     -> car_brands     (bắt buộc, ON DELETE RESTRICT)
 *   - body_type_id -> car_body_types (tuỳ chọn, ON DELETE SET NULL)
 * slug duy nhất TRONG 1 hãng (UNIQUE brand_id, slug).
 */
class Carmodels extends Controller {

    private $__data = [];
    private $__model, $__brandModel, $__bodyModel, $__request, $__response;

    private $routeBase = 'car-models';
    private $labelOne  = 'model xe';
    private $labelMany = 'Model xe';
    private $viewDir   = 'admin/car-models';

    function __construct(){
        $this->__model      = $this->model('CarModelsModel');
        $this->__brandModel = $this->model('CarBrandsModel');
        $this->__bodyModel  = $this->model('CarBodyTypesModel');
        $this->__request    = new Request();
        $this->__response   = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    /** brands + bodyTypes cho dropdown của form */
    private function formData(){
        $this->__data['content']['brands']    = $this->__brandModel->getLists();
        $this->__data['content']['bodyTypes'] = $this->__bodyModel->getLists();
    }

    // ================= Danh sách =================

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();

        // Lọc theo hãng (tuỳ chọn) qua ?brand_id=
        $f       = $this->__request->getFields();
        $filters = [];
        $brandId = isset($f['brand_id']) && $f['brand_id'] !== '' ? (int) $f['brand_id'] : 0;
        if ($brandId > 0){
            $filters['car_models.brand_id'] = $brandId;
        }

        $this->__data['content']['page_name']    = $this->labelMany;
        $this->__data['content']['dataList']     = $this->__model->getLists($filters);
        $this->__data['content']['brands']       = $this->__brandModel->getLists();
        $this->__data['content']['filterBrand']  = $brandId;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    // ================= Thêm =================

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
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

        if (!$this->brandExists($data['brand_id'])){
            $this->flashOne(['brand_id' => 'Vui lòng chọn hãng xe hợp lệ'], 'add');
            return;
        }

        if ($data['slug'] === ''){
            $this->flashOne(['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.'], 'add');
            return;
        }

        if (!empty($this->__model->findBySlugInBrand($data['brand_id'], $data['slug']))){
            $this->flashOne(['slug' => 'Hãng này đã có model với đường dẫn (slug) đó'], 'add');
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

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
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

        if (!$this->brandExists($data['brand_id'])){
            $this->flashOne(['brand_id' => 'Vui lòng chọn hãng xe hợp lệ'], 'edit/' . $id);
            return;
        }

        if ($data['slug'] === ''){
            $this->flashOne(['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.'], 'edit/' . $id);
            return;
        }

        $existing = $this->__model->findBySlugInBrand($data['brand_id'], $data['slug']);
        if (!empty($existing) && $existing['id'] != $id){
            $this->flashOne(['slug' => 'Hãng này đã có model khác dùng đường dẫn (slug) đó'], 'edit/' . $id);
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

        // car_years ON DELETE CASCADE: đời xe con tự xoá theo (đúng chủ ý).
        $this->__model->remove($id);

        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công (kèm các đời xe của nó)');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Helper =================

    private function applyRules(){
        $this->__request->rules([
            'name'     => 'required|min:1',
            'brand_id' => 'required',
        ]);
        $this->__request->message([
            'name.required'     => 'Tên không được để trống',
            'name.min'          => 'Tên không được để trống',
            'brand_id.required' => 'Vui lòng chọn hãng xe',
        ]);
    }

    private function buildData(){
        $f = $this->__request->getFields();

        return [
            'brand_id'     => isset($f['brand_id']) ? (int) $f['brand_id'] : 0,
            'body_type_id' => !empty($f['body_type_id']) ? (int) $f['body_type_id'] : null,
            'name'         => trim($f['name']),
            'slug'         => slugify(!empty($f['slug']) ? $f['slug'] : $f['name']),
            'sort_order'   => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'       => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function brandExists($brandId){
        return $brandId > 0 && !empty($this->__brandModel->getDetail($brandId));
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

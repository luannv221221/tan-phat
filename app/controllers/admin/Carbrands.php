<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Hãng xe (car_brands) — gốc của cây danh mục xe.
 *
 * Khác danh mục đơn giản: có thêm cột `country` và UPLOAD LOGO.
 * CRUD độc lập, kế thừa thẳng App\core\Controller.
 */
class Carbrands extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'car-brands';
    private $labelOne  = 'hãng xe';
    private $labelMany = 'Hãng xe';
    private $viewDir   = 'admin/car-brands';

    // Upload logo
    private $uploadDir  = 'public/assets/uploads/brands/';
    private $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $maxBytes   = 2097152; // 2MB

    function __construct(){
        $this->__model    = $this->model('CarBrandsModel');
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
        $this->__data['content']['dataList']  = $this->__model->getLists();
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

        if ($data['slug'] === ''){
            $this->flashSlugEmpty('add');
            return;
        }

        if (!empty($this->__model->findBySlug($data['slug']))){
            $this->flashOne('errors', ['slug' => 'Đường dẫn (slug) này đã tồn tại'], 'add');
            return;
        }

        // Logo — xử lý sau khi dữ liệu text hợp lệ
        $logo = $this->handleUpload();
        if ($logo === false){
            return; // đã flash lỗi + redirect trong handleUpload()
        }
        if ($logo !== null){
            $data['logo'] = $logo;
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

        if ($data['slug'] === ''){
            $this->flashSlugEmpty('edit/' . $id);
            return;
        }

        $existing = $this->__model->findBySlug($data['slug']);
        if (!empty($existing) && $existing['id'] != $id){
            $this->flashOne('errors', ['slug' => 'Đường dẫn (slug) này đã thuộc về bản ghi khác'], 'edit/' . $id);
            return;
        }

        $logo = $this->handleUpload();
        if ($logo === false){
            return;
        }
        if ($logo !== null){
            $data['logo'] = $logo;
            // xoá logo cũ nếu có
            if (!empty($item['logo'])){
                $old = $this->uploadDir . $item['logo'];
                if (is_file($old)) @unlink($old);
            }
        }

        $this->__model->edit($data, $id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Xoá =================

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $result = $this->__model->remove($id);

        if ($result === false){
            Session::flash('msgError',
                'Không xoá được: ' . $this->labelOne . ' này đang có model xe. Xoá hết model trước.');
        } else {
            if (!empty($item['logo'])){
                $old = $this->uploadDir . $item['logo'];
                if (is_file($old)) @unlink($old);
            }
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
            'name'       => trim($f['name']),
            'slug'       => slugify(!empty($f['slug']) ? $f['slug'] : $f['name']),
            'country'    => !empty($f['country']) ? trim($f['country']) : null,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    /**
     * Xử lý upload logo.
     *
     * @return string|null|false
     *   - string : tên file đã lưu (có logo mới hợp lệ)
     *   - null   : không có file gửi lên (giữ nguyên logo cũ)
     *   - false  : có lỗi -> đã flash + redirect, caller phải return
     */
    private function handleUpload(){
        if (empty($_FILES['logo']) || !isset($_FILES['logo']['error'])
            || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE){
            return null;
        }

        $file = $_FILES['logo'];
        $back = $this->currentBack();

        if ($file['error'] !== UPLOAD_ERR_OK){
            $this->flashOne('errors', ['logo' => 'Tải logo lên thất bại (mã lỗi ' . (int) $file['error'] . ')'], $back);
            return false;
        }

        if ($file['size'] > $this->maxBytes){
            $this->flashOne('errors', ['logo' => 'Logo vượt quá 2MB'], $back);
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExt, true)){
            $this->flashOne('errors', ['logo' => 'Chỉ chấp nhận ảnh: ' . implode(', ', $this->allowedExt)], $back);
            return false;
        }

        // Chắc chắn là ảnh thật, không phải file đổi đuôi
        if (getimagesize($file['tmp_name']) === false){
            $this->flashOne('errors', ['logo' => 'File không phải ảnh hợp lệ'], $back);
            return false;
        }

        if (!is_dir($this->uploadDir)){
            @mkdir($this->uploadDir, 0755, true);
        }

        $f       = $this->__request->getFields();
        $slug    = slugify(!empty($f['slug']) ? $f['slug'] : (!empty($f['name']) ? $f['name'] : 'logo'));
        $newName = ($slug !== '' ? $slug : 'logo') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $newName)){
            $this->flashOne('errors', ['logo' => 'Không lưu được file logo'], $back);
            return false;
        }

        return $newName;
    }

    private function currentBack(){
        // add hay edit/{id} — suy từ URL hiện tại
        $url = isset($_GET['module']) ? trim($_GET['module'], '/') : '';
        if (strpos($url, '/edit/') !== false){
            $parts = explode('/', $url);
            $id = end($parts);
            return 'edit/' . $id;
        }
        return 'add';
    }

    private function flashErrors(){
        Session::flash('errors', $this->__request->error());
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
    }

    private function flashSlugEmpty($back){
        $this->flashOne('errors',
            ['slug' => 'Không tự sinh được đường dẫn từ tên này. Vui lòng nhập slug thủ công.'], $back);
    }

    private function flashOne($key, $val, $back){
        Session::flash($key, $val);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

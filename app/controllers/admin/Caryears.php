<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Đời xe (car_years) — khoảng năm sản xuất của 1 model. VD: Vios 2018–2023.
 *
 * Quan hệ: model_id -> car_models (ON DELETE CASCADE).
 * Form dùng dropdown PHỤ THUỘC: chọn hãng -> lọc model (JS, dữ liệu nhúng sẵn).
 */
class Caryears extends Controller {

    private $__data = [];
    private $__model, $__brandModel, $__carModel, $__request, $__response;

    private $routeBase = 'car-years';
    private $labelOne  = 'đời xe';
    private $labelMany = 'Đời xe';
    private $viewDir   = 'admin/car-years';

    private $YEAR_MIN = 1950;
    private $YEAR_MAX = 2100;

    function __construct(){
        $this->__model      = $this->model('CarYearsModel');
        $this->__brandModel = $this->model('CarBrandsModel');
        $this->__carModel   = $this->model('CarModelsModel');
        $this->__request    = new Request();
        $this->__response   = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    /** brands + toàn bộ models (cho dropdown phụ thuộc) */
    private function formData(){
        $this->__data['content']['brands'] = $this->__brandModel->getLists();
        // getLists() đã kèm brand_id + name -> đủ cho JS lọc theo hãng
        $this->__data['content']['models'] = $this->__carModel->getLists();
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
        $this->formData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['selBrand']  = 0;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $err = $this->validateInput();
        if (!empty($err)){
            $this->flashOne($err, 'add');
            return;
        }

        $this->__model->add($this->buildData());

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

        // Suy ra hãng từ model để preselect dropdown hãng
        $model    = $this->__carModel->getDetail($item['model_id']);
        $selBrand = !empty($model) ? (int) $model['brand_id'] : 0;

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['selBrand']  = $selBrand;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return;
        }

        $err = $this->validateInput();
        if (!empty($err)){
            $this->flashOne($err, 'edit/' . $id);
            return;
        }

        $this->__model->edit($this->buildData(), $id);

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

        $this->__model->remove($id);

        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ================= Helper =================

    /** Trả về mảng lỗi (rỗng nếu hợp lệ) */
    private function validateInput(){
        $f      = $this->__request->getFields();
        $errors = [];

        $modelId = !empty($f['model_id']) ? (int) $f['model_id'] : 0;
        if ($modelId <= 0 || empty($this->__carModel->getDetail($modelId))){
            $errors['model_id'] = 'Vui lòng chọn model xe hợp lệ';
        }

        if (!isset($f['year_from']) || $f['year_from'] === '' || !ctype_digit((string) $f['year_from'])){
            $errors['year_from'] = 'Năm bắt đầu phải là số';
        } else {
            $yf = (int) $f['year_from'];
            if ($yf < $this->YEAR_MIN || $yf > $this->YEAR_MAX){
                $errors['year_from'] = 'Năm bắt đầu phải trong khoảng ' . $this->YEAR_MIN . '–' . $this->YEAR_MAX;
            }
        }

        if (isset($f['year_to']) && $f['year_to'] !== ''){
            if (!ctype_digit((string) $f['year_to'])){
                $errors['year_to'] = 'Năm kết thúc phải là số';
            } else {
                $yt = (int) $f['year_to'];
                $yf = (int) ($f['year_from'] ?? 0);
                if ($yt < $this->YEAR_MIN || $yt > $this->YEAR_MAX){
                    $errors['year_to'] = 'Năm kết thúc phải trong khoảng ' . $this->YEAR_MIN . '–' . $this->YEAR_MAX;
                } elseif (empty($errors['year_from']) && $yt < $yf){
                    $errors['year_to'] = 'Năm kết thúc không được nhỏ hơn năm bắt đầu';
                }
            }
        }

        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();

        $modelId = (int) $f['model_id'];
        $yf      = (int) $f['year_from'];
        $yt      = (isset($f['year_to']) && $f['year_to'] !== '') ? (int) $f['year_to'] : null;

        // name tự sinh nếu bỏ trống: "Vios 2018–2023" / "Vios 2018+"
        $name = !empty($f['name']) ? trim($f['name']) : '';
        if ($name === ''){
            $model = $this->__carModel->getDetail($modelId);
            $mName = !empty($model) ? $model['name'] : 'Đời';
            $name  = $mName . ' ' . $yf . ($yt !== null ? '–' . $yt : '+');
        }

        return [
            'model_id'  => $modelId,
            'year_from' => $yf,
            'year_to'   => $yt,
            'name'      => $name,
            'status'    => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flashOne($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

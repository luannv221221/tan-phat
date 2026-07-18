<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH — Phiếu bảo hành / sửa chữa (CS-01).
 */
class Warranty extends Controller {

    private $__data = [];
    private $__model, $__partner, $__part, $__request, $__response;

    private $routeBase = 'warranty';
    private $labelOne  = 'phiếu bảo hành';
    private $labelMany = 'Phiếu bảo hành / sửa chữa';
    private $viewDir   = 'admin/warranty';

    function __construct(){
        $this->__model    = $this->model('WarrantyRequestsModel');
        $this->__partner  = $this->model('PartnersModel');
        $this->__part     = $this->model('PartsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['statuses']  = WarrantyRequestsModel::$statuses;
    }

    private function formData(){
        $this->__data['content']['partners'] = $this->__partner->getActive();
        $this->__data['content']['parts']    = $this->__part->getForSelect();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f       = $this->__request->getFields();
        $status  = isset($f['status']) && isset(WarrantyRequestsModel::$statuses[$f['status']]) ? $f['status'] : '';
        $from    = isset($f['from']) ? trim($f['from']) : '';
        $to      = isset($f['to'])   ? trim($f['to'])   : '';
        $keyword = isset($f['q'])    ? trim($f['q'])    : '';

        $this->__data['content']['page_name']    = $this->labelMany;
        $this->__data['content']['dataList']     = $this->__model->getLists($status, $from, $to, $keyword);
        $this->__data['content']['filterStatus'] = $status;
        $this->__data['content']['filterFrom']   = $from;
        $this->__data['content']['filterTo']     = $to;
        $this->__data['content']['filterKeyword']= $keyword;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Lập ' . $this->labelOne;
        $this->__data['content']['today']     = date('Y-m-d');
        $this->__data['content']['item']      = null;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $id = $this->__model->add(array_merge($this->buildData(), [
            'request_no' => $this->__model->nextNo(),
            'status'     => 'received',
            'created_by' => Session::get('dataUser'),
        ]));

        Session::flash('msg', 'Đã lập ' . $this->labelOne);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Phiếu ' . $item['request_no'];

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Phiếu ' . $item['request_no'];
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
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $this->__model->edit($this->buildData(), $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Đổi trạng thái + tự set ngày hoàn tất khi done */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f = $this->__request->getFields();
        $st = isset($f['status']) ? $f['status'] : '';
        if (!isset(WarrantyRequestsModel::$statuses[$st])){
            Session::flash('msgError', 'Trạng thái không hợp lệ');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $data = ['status' => $st];
        $data['completed_date'] = ($st === 'done') ? date('Y-m-d') : null;
        $this->__model->edit($data, $id);
        Session::flash('msg', 'Đã chuyển trạng thái: ' . WarrantyRequestsModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function validate(){
        $f = $this->__request->getFields();
        $errors = [];
        if (empty($f['received_date'])) $errors['received_date'] = 'Chọn ngày tiếp nhận';
        $name = !empty($f['customer_name']) ? trim($f['customer_name']) : '';
        $pid  = !empty($f['partner_id']) ? (int) $f['partner_id'] : 0;
        if ($name === '' && $pid <= 0) $errors['customer_name'] = 'Chọn đối tượng hoặc nhập tên khách';
        $prod = !empty($f['product_name']) ? trim($f['product_name']) : '';
        $partId = !empty($f['part_id']) ? (int) $f['part_id'] : 0;
        if ($prod === '' && $partId <= 0) $errors['product_name'] = 'Chọn sản phẩm hoặc nhập tên thiết bị';
        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();
        $pid  = !empty($f['partner_id']) ? (int) $f['partner_id'] : 0;
        $partId = !empty($f['part_id']) ? (int) $f['part_id'] : 0;
        return [
            'partner_id'       => ($pid > 0 && !empty($this->__partner->getDetail($pid))) ? $pid : null,
            'customer_name'    => !empty($f['customer_name']) ? trim($f['customer_name']) : null,
            'phone'            => !empty($f['phone']) ? trim($f['phone']) : null,
            'part_id'          => ($partId > 0 && !empty($this->__part->getDetail($partId))) ? $partId : null,
            'product_name'     => !empty($f['product_name']) ? trim($f['product_name']) : null,
            'serial_no'        => !empty($f['serial_no']) ? trim($f['serial_no']) : null,
            'received_date'    => $f['received_date'],
            'appointment_date' => !empty($f['appointment_date']) ? $f['appointment_date'] : null,
            'issue'            => !empty($f['issue']) ? trim($f['issue']) : null,
            'diagnosis'        => !empty($f['diagnosis']) ? trim($f['diagnosis']) : null,
            'technician'       => !empty($f['technician']) ? trim($f['technician']) : null,
            'fee'              => isset($f['fee']) ? (float) preg_replace('/[^\d]/', '', (string) $f['fee']) : 0,
            'note'             => !empty($f['note']) ? trim($f['note']) : null,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

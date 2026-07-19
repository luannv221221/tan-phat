<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** STOREFRONT (admin) — Quản lý đơn hàng. */
class Orders extends Controller {

    private $__data = [];
    private $__model, $__itemModel, $__request, $__response;
    private $routeBase = 'orders';
    private $labelOne  = 'đơn hàng';
    private $labelMany = 'Đơn hàng';
    private $viewDir   = 'admin/orders';

    function __construct(){
        $this->__model     = $this->model('OrdersModel');
        $this->__itemModel = $this->model('OrderItemsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['statuses']  = OrdersModel::$statuses;
        $this->__data['content']['payments']  = OrdersModel::$payments;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $f = $this->__request->getFields();
        $status  = isset($f['status']) && isset(OrdersModel::$statuses[$f['status']]) ? $f['status'] : '';
        $keyword = isset($f['q']) ? trim($f['q']) : '';
        $this->__data['content']['page_name']     = $this->labelMany;
        $this->__data['content']['dataList']      = $this->__model->getLists($status, $keyword);
        $this->__data['content']['newCount']      = $this->__model->countNew();
        $this->__data['content']['filterStatus']  = $status;
        $this->__data['content']['filterKeyword'] = $keyword;
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['msgError']      = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Đơn ' . $item['order_no'];
        $this->baseData();
        $this->__data['content']['page_name'] = 'Đơn ' . $item['order_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__itemModel->getByOrder($id);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /** Cập nhật trạng thái đơn */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $f = $this->__request->getFields();
        $st = isset($f['status']) ? $f['status'] : '';
        if (!isset(OrdersModel::$statuses[$st])){ Session::flash('msgError', 'Trạng thái không hợp lệ'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }
        $this->__model->edit(['status' => $st], $id);
        Session::flash('msg', 'Đã chuyển trạng thái: ' . OrdersModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

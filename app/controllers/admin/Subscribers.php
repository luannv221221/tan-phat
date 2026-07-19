<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH web — Quản lý người đăng ký nhận bản tin (chỉ xem + bật/tắt + xoá).
 */
class Subscribers extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'newsletter';
    private $labelMany = 'Đăng ký bản tin';
    private $viewDir   = 'admin/newsletter';

    function __construct(){
        $this->__model    = $this->model('NewsletterSubscribersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $f = $this->__request->getFields();
        $keyword = isset($f['q']) ? trim($f['q']) : '';

        $this->__data['content']['routeBase']    = $this->routeBase;
        $this->__data['content']['page_name']    = $this->labelMany;
        $this->__data['content']['dataList']     = $this->__model->getLists($keyword);
        $this->__data['content']['countActive']  = $this->__model->countActive();
        $this->__data['content']['filterKeyword']= $keyword;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function setStatus($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy người đăng ký');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f  = $this->__request->getFields();
        $st = (isset($f['status']) && (int) $f['status'] === 1) ? 1 : 0;
        $this->__model->setStatus($id, $st);
        Session::flash('msg', $st ? 'Đã bật nhận bản tin' : 'Đã tắt nhận bản tin');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy người đăng ký');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Đã xoá người đăng ký');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

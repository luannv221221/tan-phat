<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH — Kiểm duyệt đánh giá sản phẩm (TASK_84).
 * Duyệt (status=1) / ẩn (status=0) / xoá đánh giá từ thành viên web.
 */
class Reviews extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;
    private $routeBase = 'reviews';

    function __construct(){
        $this->__model    = $this->model('ProductReviewsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/reviews/lists';
        $this->__data['page_title']  = 'Kiểm duyệt đánh giá';

        $f = $this->__request->getFields();
        $status = isset($f['status']) ? trim($f['status']) : '';

        $this->__data['content']['routeBase']    = $this->routeBase;
        $this->__data['content']['page_name']    = 'Kiểm duyệt đánh giá sản phẩm';
        $this->__data['content']['dataList']     = $this->__model->getForModeration($status);
        $this->__data['content']['filterStatus'] = $status;
        $this->__data['content']['pending']      = $this->__model->countPending();
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function approve($id){ $this->change($id, 1, 'Đã duyệt đánh giá'); }
    public function hide($id){ $this->change($id, 0, 'Đã ẩn đánh giá'); }

    private function change($id, $status, $msg){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy đánh giá');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $this->__model->setStatus($id, $status);
        Session::flash('msg', $msg);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy đánh giá');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Đã xoá đánh giá');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

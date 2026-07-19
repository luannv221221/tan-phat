<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH web — Hộp thư liên hệ (đọc / đánh dấu đã xử lý / xoá).
 */
class Contactmessages extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    private $routeBase = 'contact-messages';
    private $labelMany = 'Hộp thư liên hệ';
    private $viewDir   = 'admin/contact-messages';

    function __construct(){
        $this->__model    = $this->model('ContactMessagesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $f = $this->__request->getFields();
        $status  = isset($f['status']) && isset(ContactMessagesModel::$statuses[$f['status']]) ? $f['status'] : '';
        $keyword = isset($f['q']) ? trim($f['q']) : '';

        $this->__data['content']['routeBase']     = $this->routeBase;
        $this->__data['content']['page_name']     = $this->labelMany;
        $this->__data['content']['dataList']      = $this->__model->getLists($status, $keyword);
        $this->__data['content']['statuses']      = ContactMessagesModel::$statuses;
        $this->__data['content']['countNew']      = $this->__model->countNew();
        $this->__data['content']['filterStatus']  = $status;
        $this->__data['content']['filterKeyword'] = $keyword;
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['msgError']      = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function view($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy liên hệ');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__data['sub_content'] = $this->viewDir . '/view';
        $this->__data['page_title']  = 'Liên hệ #' . $item['id'];
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['statuses']  = ContactMessagesModel::$statuses;
        $this->__data['content']['item']      = $item;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy liên hệ');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f  = $this->__request->getFields();
        $st = isset($f['status']) && isset(ContactMessagesModel::$statuses[$f['status']]) ? $f['status'] : 'new';
        $this->__model->setStatus($id, $st);
        Session::flash('msg', 'Đã cập nhật trạng thái: ' . ContactMessagesModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase . '/view/' . $id);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy liên hệ');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Đã xoá liên hệ');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

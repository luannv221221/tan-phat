<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** STOREFRONT (admin) — Inbox chat, trả lời khách. */
class Chatadmin extends Controller {

    private $__data = [];
    private $__conv, $__msg, $__request, $__response;
    private $routeBase = 'chat';

    function __construct(){
        $this->__conv     = $this->model('ChatConversationsModel');
        $this->__msg      = $this->model('ChatMessagesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/chat/lists';
        $this->__data['page_title']  = 'Hỗ trợ / Chat';
        $f = $this->__request->getFields();
        $status = isset($f['status']) ? trim($f['status']) : '';
        $convs = $this->__conv->getLists($status);

        // hội thoại đang mở (nếu chọn 1) — xem ở view riêng; đây chỉ list
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['page_name'] = 'Hỗ trợ / Chat';
        $this->__data['content']['convs']     = $convs;
        $this->__data['content']['unread']    = $this->__conv->countUnread();
        $this->__data['content']['filterStatus'] = $status;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function view($id){
        $conv = $this->__conv->getDetail($id);
        if (empty($conv)){ Session::flash('msg', 'Không tìm thấy hội thoại'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        // đánh dấu đã đọc
        if ((int) $conv['unread'] === 1) $this->__conv->edit(['unread' => 0], $id);

        $this->__data['sub_content'] = 'admin/chat/view';
        $this->__data['page_title']  = 'Hội thoại #' . $conv['id'];
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['page_name'] = 'Hội thoại #' . $conv['id'];
        $this->__data['content']['conv']      = $conv;
        $this->__data['content']['messages']  = $this->__msg->getByConversation($id, 0);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function reply($id){
        $conv = $this->__conv->getDetail($id);
        if (empty($conv)){ Session::flash('msg', 'Không tìm thấy hội thoại'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $f = $this->__request->getFields();
        $body = isset($f['body']) ? trim($f['body']) : '';
        if ($body !== ''){
            $this->__msg->add($id, 'staff', mb_substr($body, 0, 2000));
            $this->__conv->edit(['last_message_at' => date('Y-m-d H:i:s'), 'unread' => 0], $id);
        }
        $this->__response->redirect('admin/' . $this->routeBase . '/view/' . $id);
    }

    public function setStatus($id){
        $conv = $this->__conv->getDetail($id);
        if (empty($conv)){ $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $f = $this->__request->getFields();
        $st = (isset($f['status']) && in_array($f['status'], ['open', 'closed'], true)) ? $f['status'] : 'open';
        $this->__conv->edit(['status' => $st], $id);
        Session::flash('msg', $st === 'closed' ? 'Đã đóng hội thoại' : 'Đã mở lại hội thoại');
        $this->__response->redirect('admin/' . $this->routeBase . '/view/' . $id);
    }

    public function delete($id){
        if (!empty($this->__conv->getDetail($id))) $this->__conv->remove($id);
        Session::flash('msg', 'Đã xoá hội thoại');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

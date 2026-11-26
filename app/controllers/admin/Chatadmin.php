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

    /* TỰ NHẬN TIN, KHÔNG PHẢI F5 — khách báo 26/11/2026 "cái chat cứ phải f5 mới
       nhận tin". Màn Hội thoại trước đó là trang tĩnh: khách gửi gì nhân viên
       chỉ thấy khi tải lại trang. Nay màn đó hỏi đường này vài giây một lần
       (xem admin/chat/view.php); AuthMiddleware không tính lần hỏi này là thao
       tác, nên phiên vẫn tự thoát sau 15 phút không dùng. */
    public function poll($id){
        $conv = $this->__conv->getDetail($id);
        if (empty($conv)) $this->json(['ok' => false, 'messages' => []], 404);
        $f = $this->__request->getFields();
        $after = !empty($f['after']) ? (int) $f['after'] : 0;

        $out = [];
        $coKhach = false;
        foreach ($this->__msg->getByConversation((int) $id, $after) as $m){
            $out[] = ['id' => (int) $m['id'], 'sender' => $m['sender'], 'body' => $m['body'], 'at' => $m['create_at']];
            if ($m['sender'] !== 'staff') $coKhach = true;
        }
        /* Đang mở hội thoại trên màn hình thì tin khách vừa tới coi như đã đọc —
           y như lúc mở trang (view()). */
        if ((int) $conv['unread'] === 1) $this->__conv->edit(['unread' => 0], $id);

        $this->json(['ok' => true, 'messages' => $out,
                     'typing' => $this->__conv->benDangGo($conv, 'customer'),
                     'status' => $conv['status']]);
    }

    /** Nhân viên đang gõ -> khung chat của khách hiện "Nhân viên đang trả lời…". */
    public function dangGo($id){
        $conv = $this->__conv->getDetail($id);
        if (empty($conv)) $this->json(['ok' => false], 404);
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)) $this->json(['ok' => false], 403);
        $this->json(['ok' => $this->__conv->baoGo((int) $id, 'staff')]);
    }

    public function reply($id){
        /* Màn Hội thoại gửi bằng AJAX (không tải lại trang, không mất chỗ đang
           đọc) thì trả JSON; form thường (tắt JS) vẫn chuyển trang như cũ. */
        $ajax = la_ajax();
        $conv = $this->__conv->getDetail($id);
        if (empty($conv)){
            if ($ajax) $this->json(['ok' => false, 'message' => 'Không tìm thấy hội thoại'], 404);
            Session::flash('msg', 'Không tìm thấy hội thoại'); $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            if ($ajax) $this->json(['ok' => false, 'message' => 'Tài khoản không có quyền trả lời chat'], 403);
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f = $this->__request->getFields();
        $body = isset($f['body']) ? trim($f['body']) : '';
        $tin  = null;
        if ($body !== ''){
            $body = mb_substr($body, 0, 2000);
            $mid  = $this->__msg->add($id, 'staff', $body);
            $this->__conv->edit(['last_message_at' => date('Y-m-d H:i:s'), 'unread' => 0], $id);
            $this->__conv->tatGo((int) $id, 'staff');
            $tin = ['id' => (int) $mid, 'sender' => 'staff', 'body' => $body, 'at' => date('Y-m-d H:i:s')];
        }
        if ($ajax){
            $this->json($tin ? ['ok' => true] + $tin : ['ok' => false, 'message' => 'Chưa nhập nội dung']);
        }
        $this->__response->redirect('admin/' . $this->routeBase . '/view/' . $id);
    }

    private function json($data, $code = 200){
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
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

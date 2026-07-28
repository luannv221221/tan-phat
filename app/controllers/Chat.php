<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;
use App\core\Hash;

/** STOREFRONT — Webchat khách (JSON, polling). */
class Chat extends Controller {

    private $__conv, $__msg, $__request;

    function __construct(){
        $this->__conv    = $this->model('ChatConversationsModel');
        $this->__msg     = $this->model('ChatMessagesModel');
        $this->__request = new Request();
    }

    private function sessionKey(){
        $key = Session::get('chat_key');
        if (empty($key)){
            $key = method_exists('App\\core\\Hash', 'randomToken') ? Hash::randomToken() : bin2hex(random_bytes(16));
            $key = substr($key, 0, 64);
            Session::set('chat_key', $key);
        }
        return $key;
    }

    private function json($data){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Khách gửi tin nhắn */
    public function send(){
        $f = $this->__request->getFields();
        $body = isset($f['body']) ? trim($f['body']) : '';
        if ($body === '' || mb_strlen($body) > 2000){ $this->json(['ok' => false, 'error' => 'empty']); }

        $key = $this->sessionKey();
        $conv = $this->__conv->findBySession($key);
        $memberId = Session::get('dataMember');
        if (empty($conv)){
            $name  = !empty($f['name']) ? trim($f['name']) : null;
            $phone = !empty($f['phone']) ? trim($f['phone']) : null;

            // SĐT không bắt buộc, nhưng đã nhập thì phải đúng — đây là cách
            // duy nhất để gọi lại khách nên lưu số rác thì coi như mất liên hệ.
            if ($phone !== null && !is_phone($phone)){
                $this->json(['ok' => false, 'error' => 'phone',
                             'message' => 'Số điện thoại không hợp lệ (di động 10 số hoặc cố định 11 số)']);
            }

            $cid = $this->__conv->create($key, $memberId, $name, $phone);
        } else {
            $cid = (int) $conv['id'];
        }
        $mid = $this->__msg->add($cid, 'customer', $body);
        $this->__conv->edit(['unread' => 1, 'status' => 'open', 'last_message_at' => date('Y-m-d H:i:s')], $cid);

        $this->json(['ok' => true, 'id' => $mid, 'sender' => 'customer', 'body' => $body]);
    }

    /** Khách poll tin nhắn mới */
    public function poll(){
        $f = $this->__request->getFields();
        $after = !empty($f['after']) ? (int) $f['after'] : 0;
        $key = Session::get('chat_key');
        if (empty($key)){ $this->json(['messages' => [], 'hasInfo' => false]); }
        $conv = $this->__conv->findBySession($key);
        if (empty($conv)){ $this->json(['messages' => [], 'hasInfo' => false]); }

        $out = [];
        foreach ($this->__msg->getByConversation((int) $conv['id'], $after) as $m){
            $out[] = ['id' => (int) $m['id'], 'sender' => $m['sender'], 'body' => $m['body'], 'at' => $m['create_at']];
        }

        // Đã có hội thoại kèm tên/SĐT thì client ẩn luôn 2 ô nhập đó.
        // Trước đây chỉ ẩn khi nhân viên trả lời, nên tải lại trang là 2 ô
        // trắng hiện lại dù khách đã khai rồi.
        $hasInfo = !empty($conv['guest_name']) || !empty($conv['guest_phone'])
                   || !empty($conv['member_id']);

        $this->json(['messages' => $out, 'hasInfo' => (bool) $hasInfo]);
    }
}

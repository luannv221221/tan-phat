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
        if (empty($key)){ $this->json(['messages' => []]); }
        $conv = $this->__conv->findBySession($key);
        if (empty($conv)){ $this->json(['messages' => []]); }

        $out = [];
        foreach ($this->__msg->getByConversation((int) $conv['id'], $after) as $m){
            $out[] = ['id' => (int) $m['id'], 'sender' => $m['sender'], 'body' => $m['body'], 'at' => $m['create_at']];
        }
        $this->json(['messages' => $out]);
    }
}

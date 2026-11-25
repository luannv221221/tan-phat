<?php

use App\core\Model;

/** STOREFRONT — Hội thoại chat. */
class ChatConversationsModel extends Model {

    protected $_table   = 'chat_conversations';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /* Khách web và giao dịch web thuộc về MỘT gara — 07/10/2026, khi mỗi gara
       có website riêng. Thiếu cờ này là đơn đặt trên trang gara A rơi vào hộp
       đơn của gara B. */
    protected $_theoGara = true;

    public function findBySession($key){
        return $this->bangGara()->where('session_key', '=', $key)->first();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /* addNew(), KHÔNG insert() thô: addNew() điền `garage_id` của gara đang mở.
       Bản cũ ghi insert() thô nên từ 07/10 hội thoại mới mang gara NULL — đọc
       lại (lọc theo gara) không ra: admin không thấy, khách không nhận được trả
       lời, tin thứ hai đụng khoá duy nhất `session_key` và báo lỗi. Khách báo
       "chat không chạy" 25/11/2026 (tests/ChatTheoGaraTest.php). */
    public function create($key, $memberId = null, $name = null, $phone = null){
        $now = date('Y-m-d H:i:s');
        $this->addNew([
            'session_key' => $key, 'member_id' => !empty($memberId) ? (int) $memberId : null,
            'guest_name' => $name, 'guest_phone' => $phone, 'status' => 'open',
            'unread' => 0, 'last_message_at' => $now, 'create_at' => $now,
        ]);
        return $this->lastId();
    }

    /** Danh sách hội thoại cho inbox admin (mới nhất trước) */
    public function getLists($status = ''){
        $q = $this->bangGara()
            ->select('`chat_conversations`.*, `members`.`name` AS member_name')
            ->leftJoinOn('members', 'chat_conversations.member_id', 'members.id');
        if ($status === 'open' || $status === 'closed') $q = $q->where('chat_conversations.status', '=', $status);
        return $q->orderBy('chat_conversations.last_message_at', 'DESC')->orderBy('chat_conversations.id', 'DESC')->get();
    }

    public function countUnread(){
        $r = $this->bangGara()->select('COUNT(*) AS c')->where('unread', '=', 1)->where('status', '=', 'open')->first();
        return (int) ($r['c'] ?? 0);
    }

    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}

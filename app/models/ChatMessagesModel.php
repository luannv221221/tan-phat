<?php

use App\core\Model;

/** STOREFRONT — Tin nhắn chat. */
class ChatMessagesModel extends Model {

    protected $_table   = 'chat_messages';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Tin nhắn của 1 hội thoại, tuỳ chọn chỉ lấy sau $afterId (polling) */
    public function getByConversation($convId, $afterId = 0){
        $q = $this->table($this->_table)->where('conversation_id', '=', (int) $convId);
        if ($afterId > 0) $q = $q->where('id', '>', (int) $afterId);
        return $q->orderBy('id', 'ASC')->get();
    }

    public function add($convId, $sender, $body){
        $this->insert('chat_messages', [
            'conversation_id' => (int) $convId,
            'sender' => ($sender === 'staff') ? 'staff' : 'customer',
            'body' => $body,
            'create_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->lastId();
    }
}

<?php

use App\core\Model;

/** STOREFRONT — Log lượt truy cập + báo cáo. */
class VisitsModel extends Model {

    protected $_table   = 'visits';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /** Ghi 1 lượt xem (gọi từ layout storefront). Tự lấy từ $_SERVER/$_GET. */
    public function log($memberId = null){
        $url = isset($_GET['module']) ? trim($_GET['module'], '/') : '';
        if ($url === '') $url = '/';
        $ref = isset($_SERVER['HTTP_REFERER']) ? mb_substr($_SERVER['HTTP_REFERER'], 0, 255) : null;
        $kw  = isset($_GET['q']) && trim($_GET['q']) !== '' ? mb_substr(trim($_GET['q']), 0, 150) : null;
        $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $ip  = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? null);

        $this->insert('visits', [
            'url' => mb_substr($url, 0, 255), 'referrer' => $ref, 'keyword' => $kw,
            'ip' => $ip, 'user_agent' => $ua,
            'member_id' => !empty($memberId) ? (int) $memberId : null,
            'create_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Tổng lượt xem (tuỳ chọn từ ngày) */
    public function totalViews($from = ''){
        $q = $this->table($this->_table)->select('COUNT(*) AS c');
        if ($from !== '') $q = $q->where('create_at', '>=', $from);
        $r = $q->first();
        return (int) ($r['c'] ?? 0);
    }

    /** Số khách (IP phân biệt) từ ngày */
    public function uniqueVisitors($from = ''){
        $q = $this->table($this->_table)->select('COUNT(DISTINCT `ip`) AS c');
        if ($from !== '') $q = $q->where('create_at', '>=', $from);
        $r = $q->first();
        return (int) ($r['c'] ?? 0);
    }

    /** Các lượt trong khoảng (cho gộp PHP), giới hạn an toàn */
    public function fetchSince($from, $limit = 20000){
        return $this->table($this->_table)
            ->select('`url`, `referrer`, `keyword`, `create_at`')
            ->where('create_at', '>=', $from)
            ->orderBy('id', 'DESC')->limit((int) $limit, 0)->get();
    }

    public function purgeAll(){ return $this->delete('visits', '1=1', []); }
}

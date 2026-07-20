<?php

use App\core\Model;

/** SEO — Cấu hình website (key-value). */
class SettingsModel extends Model {

    protected $_table   = 'site_settings';
    protected $_fields  = '*';
    protected $_primary = 'id';

    private static $cache = null;

    /** Toàn bộ cấu hình dạng [key => value] (cache trong request) */
    public function map(){
        if (self::$cache !== null) return self::$cache;
        $rows = $this->table($this->_table)->select('`skey`, `svalue`')->get();
        $map = [];
        foreach ($rows ?: [] as $r){ $map[$r['skey']] = $r['svalue']; }
        self::$cache = $map;
        return $map;
    }

    public function val($key, $default = ''){
        $m = $this->map();
        return isset($m[$key]) && $m[$key] !== null ? $m[$key] : $default;
    }

    /** Lưu nhiều cặp key-value (upsert) */
    public function saveMany(array $kv){
        $now = date('Y-m-d H:i:s');
        foreach ($kv as $k => $v){
            $ex = $this->table($this->_table)->where('skey', '=', $k)->first();
            if (empty($ex)){
                $this->insert('site_settings', ['skey' => $k, 'svalue' => $v, 'update_at' => $now]);
            } else {
                $this->update('site_settings', ['svalue' => $v, 'update_at' => $now], '`skey` = ?', [$k]);
            }
        }
        self::$cache = null; // reset cache
    }
}

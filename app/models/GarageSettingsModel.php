<?php

use App\core\Model;

/**
 * Cấu hình RIÊNG của từng gara (chu kỳ nhắc bảo trì...) — bảng `garage_settings`.
 *
 * Gara độc lập (22/09/2026): trước đây các cấu hình này nằm ở `site_settings`
 * dùng chung, nên gara B bấm Lưu là đổi luôn chu kỳ bảo trì của Tân Phát.
 *
 * Gara chưa đặt thì đọc `site_settings` (Cấu hình chung) — dữ liệu cũ vẫn chạy
 * y như trước, và Tân Phát đặt một lần làm mặc định cho gara mới.
 */
class GarageSettingsModel extends Model {

    protected $_table    = 'garage_settings';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    public function val($key, $default = ''){
        $r = $this->bangGara()->select('`svalue`')->where('skey', '=', $key)->first();
        if (!empty($r) && $r['svalue'] !== null) return (string) $r['svalue'];
        return \App\core\Load::model('SettingsModel')->val($key, $default);
    }

    /** Lưu nhiều cặp key-value cho GARA LÀM VIỆC (upsert) */
    public function saveMany(array $kv){
        $gara = gara_hien_tai_id();
        if (empty($gara)) return false;
        $now = date('Y-m-d H:i:s');
        foreach ($kv as $k => $v){
            $ex = $this->bangGara()->select('`id`')->where('skey', '=', $k)->first();
            if (empty($ex)){
                $this->addNew(['garage_id' => $gara, 'skey' => $k, 'svalue' => $v, 'update_at' => $now]);
            } else {
                $this->updateById(['svalue' => $v, 'update_at' => $now], (int) $ex['id']);
            }
        }
        return true;
    }
}

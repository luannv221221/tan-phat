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

    /**
     * TÀI KHOẢN NHẬN TIỀN — KHÔNG THỪA HƯỞNG từ Cấu hình chung (16/11/2026).
     *
     * Mọi ô khác ở đây gara chưa khai thì dùng mặc định của Tân Phát (tên,
     * hotline, chu kỳ bảo trì...). Riêng bốn ô này thì KHÔNG: thừa hưởng tài
     * khoản ngân hàng nghĩa là gara chưa khai mà màn Cấu hình của họ hiện sẵn
     * số tài khoản của Tân Phát — bấm Lưu một lần là chép nó thành của mình, và
     * từ khi hoá đơn in mã QR thì khách quét là chuyển tiền sang Tân Phát.
     * Gara chưa khai = trống = hoá đơn không in mã QR.
     *
     * Cũng vì vậy Settings::chiKhacMacDinh() không lọc bốn ô này: gara chọn
     * CÙNG ngân hàng với Tân Phát (Vietcombank cả hai) là chuyện thường, bỏ đi
     * vì "giống mặc định" là gara đó mất ngân hàng.
     */
    const KHOA_NGAN_HANG = ['bank_bin', 'bank_name', 'bank_account', 'bank_holder'];

    /**
     * CHỈ những ô GARA NÀY TỰ KHAI — không trộn Cấu hình chung làm nền.
     *
     * map() trộn `site_settings` xuống dưới, tiện cho trang bán hàng (ô nào
     * chưa khai thì thừa hưởng mặc định). Nhưng với GIẤY TỜ thì cái nền đó là
     * sai: gara chưa điền tên mà thừa hưởng `site_name` của Tân Phát là tờ báo
     * giá của gara mang tên Tân Phát — sai người bán. cau_hinh_in_an() vì vậy
     * phải hỏi riêng cái này.
     *
     * Rỗng bị bỏ, cùng lý do với map(): rỗng = "chưa đặt", không phải "xoá".
     */
    public function rieng(){
        $ra = [];
        foreach ((array) $this->bangGara()->select('`skey`, `svalue`')->get() as $r){
            if (!isset($r['skey'])) continue;
            if ($r['svalue'] === null || $r['svalue'] === '') continue;
            $ra[$r['skey']] = $r['svalue'];
        }
        return $ra;
    }

    public function val($key, $default = ''){
        $r = $this->bangGara()->select('`svalue`')->where('skey', '=', $key)->first();
        if (!empty($r) && $r['svalue'] !== null) return (string) $r['svalue'];
        return \App\core\Load::model('SettingsModel')->val($key, $default);
    }

    /**
     * TOÀN BỘ cấu hình của gara đang phục vụ: lấy cấu hình chung làm nền, rồi
     * đè bằng giá trị riêng của gara.
     *
     * Đây là thứ trang người dùng đọc để biết tên, logo, hotline, địa chỉ,
     * Zalo, Facebook… của gara. Chưa có bản đồ này thì mọi gara xài chung một
     * bộ nhận diện — đúng cái phải bỏ khi mỗi gara có website riêng.
     *
     * GIÁ TRỊ RỖNG KHÔNG ĐÈ. Gara mới chưa khai gì thì hàng loạt ô là chuỗi
     * rỗng; đè xuống là web trắng trơn, không tên không hotline. Rỗng = "chưa
     * đặt" = dùng mặc định chung. Đổi lại, gara muốn XOÁ TRẮNG một ô (ví dụ
     * không có Facebook) thì chưa làm được bằng cách để trống — sẽ cần một
     * cách riêng khi có người thật sự cần.
     */
    public function map(){
        $chung = (array) \App\core\Load::model('SettingsModel')->map();

        /* Tài khoản ngân hàng không thừa hưởng — xem KHOA_NGAN_HANG. Gara tổng
           thì Cấu hình chung CHÍNH LÀ của nó, giữ nguyên. laGaraTongLoc() chứ
           không la_gara_tong(): phải tôn trọng Model::trongGara(). */
        if (!$this->laGaraTongLoc()){
            foreach (self::KHOA_NGAN_HANG as $k) unset($chung[$k]);
        }

        $rieng = [];
        foreach ((array) $this->bangGara()->select('`skey`, `svalue`')->get() as $r){
            if (!isset($r['skey'])) continue;
            $v = $r['svalue'];
            if ($v === null || $v === '') continue;
            $rieng[$r['skey']] = $v;
        }

        return array_merge((array) $chung, $rieng);
    }

    /**
     * Xoá hẳn dòng riêng của mấy khoá này — gara "thôi không khai riêng nữa",
     * từ đó về sau tự theo Cấu hình chung.
     *
     * Dùng khi người dùng gõ một ô về đúng bằng mặc định chung; xem
     * Settings::chiKhacMacDinh() về việc vì sao phải xoá chứ không ghi đè.
     */
    public function xoaKhoa(array $khoa){
        $gara = gara_hien_tai_id();
        if (empty($gara) || empty($khoa)) return false;
        foreach ($khoa as $k){
            $r = $this->bangGara()->select('`id`')->where('skey', '=', $k)->first();
            if (!empty($r['id'])) $this->deleteById((int) $r['id']);
        }
        return true;
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

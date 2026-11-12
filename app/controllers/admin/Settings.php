<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * Cấu hình (1 form key-value): liên hệ, mã số thuế, ngân hàng, giao diện, SEO.
 *
 * MÀN NÀY LÀM HAI VIỆC KHÁC NHAU TUỲ AI MỞ — 07/10/2026, khi mỗi gara có
 * website riêng:
 *
 *   Gara tổng mở  -> sửa CẤU HÌNH CHUNG (`site_settings`). Đây là mặc định
 *                    cho mọi gara chưa tự khai.
 *   Gara khác mở  -> sửa CẤU HÌNH RIÊNG của gara đó (`garage_settings`).
 *                    Không đụng được vào mặc định chung.
 *
 * Không tách thành hai màn vì việc người dùng làm y hệt nhau: điền tên, logo,
 * hotline, địa chỉ của mình. Tách ra chỉ thêm một mục menu để phải chọn đúng.
 *
 * Đọc thì LUÔN qua bản đồ đã trộn (riêng đè lên chung), nên ô nào gara chưa
 * khai sẽ hiện sẵn giá trị mặc định — nhìn là biết mình đang thừa hưởng gì.
 */
class Settings extends Controller {

    private $__data = [];
    private $__model, $__gara, $__request, $__response;

    /* Các khoá cho phép chỉnh (whitelist).
       `logo` và `tax_code` trước đây KHÔNG có trong danh sách này:
         - logo: layout trang bán hàng vẫn đọc $settings['logo'], nhưng không
           màn hình nào đặt được nên nó luôn rỗng và rơi về ảnh mặc định của
           giao diện. Nay biểu mẫu in cũng dùng logo -> phải đặt được.
         - tax_code: đã có sẵn trong CSDL và in trên đầu hoá đơn, nhưng muốn
           sửa thì phải vào thẳng CSDL. */
    private $keys = ['site_name', 'site_short_name', 'site_slogan', 'meta_description', 'meta_keywords',
                     'og_image', 'logo', 'hotline', 'email', 'address', 'tax_code',
                     /* `messenger` tách riêng khỏi `facebook` — link trang
                        (facebook.com/<ten>) và link nhắn tin (m.me/<ten>) không
                        phải lúc nào cũng suy ra được nhau. Dùng cho ba nút nổi
                        "Hỗ trợ trực tuyến" ở trang khách (tài liệu 05/11/2026). */
                     'facebook', 'zalo', 'messenger',
                     'bank_name', 'bank_account', 'bank_holder',
                     'show_car_filter', 'show_topbar',
                     /* Linh vuc (gara / phu_tung) — quyet dinh man Lap bao gia
                        co hien khoi bao hiem + xe hay khong. Yeu cau 29/10/2026. */
                     'linh_vuc',
                     /* Tai lieu khach 05/11/2026, Frontend muc 13: "Xay dung
                        cau hinh gara theo (linh vuc, dien tich xuong...)".
                        Dau "..." trong tai lieu van chua ro la nhung truong gi
                        — dang hoi khach. Hai truong duoc ghi ten thi lam truoc. */
                     'dien_tich_xuong',
                     /* BẢNG TÍNH GIÁ — khách chốt 11/11/2026: "cấu hình giá
                        theo 2 kiểu: bình quân hoặc gần nhất" và "đặt lịch
                        ngày, tuần, tháng". Giá trị lạ bị PartPricesModel kéo
                        về mặc định, nên dù form có bị sửa tay cũng không sinh
                        ra kiểu tính giá vốn thứ ba. */
                     'cost_method', 'price_schedule',
                     /* Frontend muc 7: "Phan footer: cung la 1 bai viet co
                        ckeditor, nguoi dung tu them". Luu HTML da loc; xem
                        xu ly rieng trong save(). */
                     'footer_html'];

    /* ====================================================================
     *  Ô NÀO VỀ MÀN "CẤU HÌNH WEBSITE" — khách yêu cầu 12/11/2026:
     *  "phần cấu hình chung, những thứ liên quan đến website đẩy hết lên
     *  Quản lý website".
     * ====================================================================
     *
     * RANH GIỚI LÀ "CHỈ ẢNH HƯỞNG TRANG KHÁCH", không phải "có chữ website".
     * Sáu ô dưới đây tắt đi thì chỉ trang khách đổi, không đụng gì tới quản
     * trị hay giấy tờ in ra.
     *
     * Ở LẠI Cấu hình chung những ô DÙNG CHUNG CHO CẢ HAI NƠI, dù chúng cũng
     * hiện trên website:
     *   site_name / logo / site_short_name  -> menu trái trang quản trị VÀ
     *                                          đầu biểu mẫu báo giá, hoá đơn
     *   hotline / email / address / tax_code-> in trên báo giá, hoá đơn
     *   bank_*                              -> hướng dẫn chuyển khoản (in)
     *   linh_vuc / dien_tich_xuong          -> quyết định màn Lập báo giá
     *   cost_method / price_schedule        -> Bảng tính giá
     * Đẩy mấy ô đó sang màn website là lúc cần sửa số điện thoại in trên hoá
     * đơn thì phải đi tìm trong mục Quản lý website.
     *
     * DANH SÁCH NÀY CÒN LÀ CHỐT AN TOÀN, không phải chỉ để xếp màn. luu()
     * ghi ĐÚNG những khoá của nhóm đang gửi lên: ô nào không nằm trong nhóm
     * thì không bị đụng tới. Trước đây save() duyệt cả $keys và gán '' cho ô
     * vắng mặt — cách đó mà giữ nguyên khi tách làm hai form thì bấm Lưu ở
     * màn Cấu hình website là XOÁ TRẮNG hotline, số tài khoản, mã số thuế. */
    private $khoaWebsite = ['meta_description', 'meta_keywords', 'og_image',
                            'show_car_filter', 'show_topbar', 'footer_html'];

    function __construct(){
        $this->__model    = $this->model('SettingsModel');
        $this->__gara     = $this->model('GarageSettingsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $chung = la_gara_tong();
        $this->man('admin/settings/form', $chung ? 'Cấu hình chung' : 'Cấu hình gara', $chung);
    }

    /**
     * MÀN CẤU HÌNH WEBSITE — nằm trong nhóm Quản lý website của menu.
     *
     * Vẫn là controller này, vẫn hai bảng `site_settings` / `garage_settings`
     * ấy: tách ra chỉ là tách CHỖ ĐỨNG trên menu và tách danh sách ô, không
     * phải một kho cấu hình thứ hai. Nhờ thế quy tắc "gara tổng sửa mặc định
     * chung, gara khác sửa riêng của mình" và chiKhacMacDinh() áp dụng y
     * nguyên cho cả hai màn — viết lại ở nơi khác là sớm muộn cũng lệch.
     *
     * Module riêng (`cau-hinh-website`) thì BẮT BUỘC phải có: menu và phân
     * quyền đều đi theo `modules`.`link`, không có dòng module thì mục này
     * không hiện ra cho ai cả. Xem migration 2026_11_12_000109.
     */
    public function website(){
        $this->man('admin/cau-hinh-website/form', 'Cấu hình website', la_gara_tong());
    }

    private function man($view, $ten, $chung){
        $this->__data['sub_content'] = $view;
        $this->__data['page_title']  = $ten;
        $this->__data['content']['page_name'] = $ten;
        $this->__data['content']['laChung']   = $chung;
        $this->__data['content']['garaTen']   = !$chung && ($g = gara_hien_tai()) ? $g['name'] : '';
        /* Đọc bản đã trộn: ô gara chưa khai hiện sẵn mặc định chung. */
        $this->__data['content']['settings']  = $this->__gara->map();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function save(){
        if (!route('admin/settings')){ $this->__response->redirect('admin/khong-co-quyen'); return; }

        /* Phần còn lại sau khi đã nhấc các ô website đi — xem $khoaWebsite. */
        $this->luu(array_values(array_diff($this->keys, $this->khoaWebsite)), 'admin/settings');
    }

    public function saveWebsite(){
        /* Hỏi ĐÚNG màn của mình, không hỏi 'admin/settings'. Hai màn nay là
           hai module riêng, nên gara được sửa cấu hình website chưa chắc đã
           được sửa cấu hình chung — và ngược lại. */
        if (!route('admin/cau-hinh-website')){ $this->__response->redirect('admin/khong-co-quyen'); return; }

        $this->luu($this->khoaWebsite, 'admin/cau-hinh-website');
    }

    /**
     * Ghi ĐÚNG những ô của một nhóm. $veDau là màn để quay lại sau khi lưu.
     */
    private function luu(array $khoa, $veDau){

        $coOg   = in_array('og_image', $khoa, true);
        $coLogo = in_array('logo', $khoa, true);

        // Upload ảnh (tuỳ chọn) — OG image và logo
        $up = ['status' => 'none'];
        if ($coOg){
            $up = upload_image('og_image_file', 'settings', 'og');
            if ($up['status'] === 'error'){
                Session::flash('msg', 'Ảnh OG lỗi: ' . $up['message']);
                $this->__response->redirect($veDau); return;
            }
        }

        $upLogo = ['status' => 'none'];
        if ($coLogo){
            $upLogo = upload_image('logo_file', 'settings', 'logo');
            if ($upLogo['status'] === 'error'){
                Session::flash('msg', 'Ảnh logo lỗi: ' . $upLogo['message']);
                $this->__response->redirect($veDau); return;
            }
        }

        $f = $this->__request->getFields();
        $kv = [];
        foreach ($khoa as $k){
            if ($k === 'og_image' || $k === 'logo') continue; // hai khoá ảnh xử lý riêng
            /* CHÂN TRANG là HTML do người dùng soạn, và nó in ra trang khách
               bằng {!! !!} (không escape — phải thế thì <strong> mới in đậm).
               Nên đây là chỗ duy nhất trong màn này nhận thẻ HTML, và bắt buộc
               đi qua bộ lọc. trim() suông là mở cửa cho <script> vào thẳng mọi
               trang của gara. */
            if ($k === 'footer_html'){
                $kv[$k] = isset($f[$k]) ? loc_html_an_toan($f[$k]) : '';
                continue;
            }
            $kv[$k] = isset($f[$k]) ? trim($f[$k]) : '';
        }
        if ($coOg){
            if ($up['status'] === 'ok') $kv['og_image'] = $up['path'];
            elseif (isset($f['og_image'])) $kv['og_image'] = trim($f['og_image']);
        }

        /* Chỉ ghi đè logo khi THỰC SỰ có ảnh mới hoặc người dùng gõ đường dẫn.
           Ghi đè vô điều kiện là mỗi lần bấm Lưu (dù chỉ sửa số điện thoại)
           lại xoá trắng logo đã tải lên. */
        if ($coLogo){
            if ($upLogo['status'] === 'ok') $kv['logo'] = $upLogo['path'];
            elseif (isset($f['logo'])) $kv['logo'] = trim($f['logo']);
        }

        /* GARA TỔNG ghi vào cấu hình CHUNG (mặc định cho mọi gara).
           GARA KHÁC ghi vào cấu hình RIÊNG của chính nó — không đụng được vào
           mặc định chung. Ghi nhầm chỗ là một gara bấm Lưu làm đổi nhận diện
           của tất cả gara còn lại. */
        if (la_gara_tong()){
            $this->__model->saveMany($kv);
            Session::flash('msg', 'Đã lưu cấu hình chung');
        } else {
            /* GARA CHỈ LƯU NHỮNG Ô KHÁC MẶC ĐỊNH CHUNG — xem chiKhacMacDinh().
               Ghi tất là chép nhận diện của Tân Phát thành của gara. */
            $this->__gara->saveMany($this->chiKhacMacDinh($kv));
            Session::flash('msg', 'Đã lưu cấu hình của gara');
        }
        $this->__response->redirect($veDau);
    }

    /**
     * Lọc ra những ô gara THỰC SỰ KHAI KHÁC với Cấu hình chung.
     *
     * ======================================================================
     *  LỖI NÀY TỪNG XẢY RA THẬT VÀ NÓ LÀ LỖI MỨC TIỀN BẠC (29/10/2026).
     * ======================================================================
     *
     * Form này CỐ Ý điền sẵn giá trị đã trộn (riêng đè lên chung) để gara nhìn
     * là biết mình đang thừa hưởng gì — xem ghi chú đầu lớp. Nhưng lúc Lưu thì
     * mọi ô đều được gửi lên, kể cả những ô gara chưa hề đụng tới. Ghi tất là
     * CHÉP nguyên nhận diện của Tân Phát thành giá trị RIÊNG của gara: số tài
     * khoản ngân hàng, chủ tài khoản, mã số thuế, email.
     *
     * Trước 26/10/2026 mấy dòng đó nằm im vì biểu mẫu in không đọc
     * `garage_settings`. Từ khi cau_hinh_in_an() đọc nó, hậu quả là: GARA NÀO
     * BẤM LƯU MỘT LẦN LÀ HOÁ ĐƠN CỦA HỌ BẮT ĐẦU IN SỐ TÀI KHOẢN CỦA TÂN PHÁT.
     * Khách chuyển tiền theo tờ hoá đơn đó là chuyển nhầm người.
     *
     * Quy tắc: `garage_settings` CHỈ GIỮ phần khác mặc định chung. Đúng bằng
     * ngữ nghĩa mà GarageSettingsModel::map() đang dùng để đọc (lấy chung làm
     * nền rồi đè phần riêng), nên hai chiều đọc/ghi khớp nhau.
     *
     * Ô nào gõ về ĐÚNG BẰNG mặc định chung thì coi như "thôi không khai riêng
     * nữa" — xoá dòng riêng đi, để từ đó về sau nó tự theo mặc định chung.
     */
    private function chiKhacMacDinh(array $kv){
        $chung = $this->__model->map();
        $luu   = [];
        $boDi  = [];

        foreach ($kv as $k => $v){
            $mac = isset($chung[$k]) ? (string) $chung[$k] : '';
            if ((string) $v === $mac) $boDi[] = $k;   // giống mặc định -> không khai riêng
            else                      $luu[$k] = $v;
        }

        /* Xoá dòng riêng cũ của những khoá nay đã bằng mặc định. Không xoá thì
           giá trị chép nhầm từ lần Lưu trước vẫn nằm đó mãi. */
        if (!empty($boDi)) $this->__gara->xoaKhoa($boDi);

        return $luu;
    }
}

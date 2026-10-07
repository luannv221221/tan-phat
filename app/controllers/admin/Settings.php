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
    private $keys = ['site_name', 'site_slogan', 'meta_description', 'meta_keywords',
                     'og_image', 'logo', 'hotline', 'email', 'address', 'tax_code',
                     'facebook', 'zalo',
                     'bank_name', 'bank_account', 'bank_holder',
                     'show_car_filter', 'show_topbar'];

    function __construct(){
        $this->__model    = $this->model('SettingsModel');
        $this->__gara     = $this->model('GarageSettingsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $chung = la_gara_tong();
        $ten   = $chung ? 'Cấu hình chung' : 'Cấu hình gara';

        $this->__data['sub_content'] = 'admin/settings/form';
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

        // Upload ảnh (tuỳ chọn) — OG image và logo
        $up = upload_image('og_image_file', 'settings', 'og');
        if ($up['status'] === 'error'){
            Session::flash('msg', 'Ảnh OG lỗi: ' . $up['message']);
            $this->__response->redirect('admin/settings'); return;
        }

        $upLogo = upload_image('logo_file', 'settings', 'logo');
        if ($upLogo['status'] === 'error'){
            Session::flash('msg', 'Ảnh logo lỗi: ' . $upLogo['message']);
            $this->__response->redirect('admin/settings'); return;
        }

        $f = $this->__request->getFields();
        $kv = [];
        foreach ($this->keys as $k){
            if ($k === 'og_image' || $k === 'logo') continue; // hai khoá ảnh xử lý riêng
            $kv[$k] = isset($f[$k]) ? trim($f[$k]) : '';
        }
        if ($up['status'] === 'ok') $kv['og_image'] = $up['path'];
        elseif (isset($f['og_image'])) $kv['og_image'] = trim($f['og_image']);

        /* Chỉ ghi đè logo khi THỰC SỰ có ảnh mới hoặc người dùng gõ đường dẫn.
           Ghi đè vô điều kiện là mỗi lần bấm Lưu (dù chỉ sửa số điện thoại)
           lại xoá trắng logo đã tải lên. */
        if ($upLogo['status'] === 'ok') $kv['logo'] = $upLogo['path'];
        elseif (isset($f['logo'])) $kv['logo'] = trim($f['logo']);

        /* GARA TỔNG ghi vào cấu hình CHUNG (mặc định cho mọi gara).
           GARA KHÁC ghi vào cấu hình RIÊNG của chính nó — không đụng được vào
           mặc định chung. Ghi nhầm chỗ là một gara bấm Lưu làm đổi nhận diện
           của tất cả gara còn lại. */
        if (la_gara_tong()){
            $this->__model->saveMany($kv);
            Session::flash('msg', 'Đã lưu cấu hình chung');
        } else {
            $this->__gara->saveMany($kv);
            Session::flash('msg', 'Đã lưu cấu hình của gara');
        }
        $this->__response->redirect('admin/settings');
    }
}

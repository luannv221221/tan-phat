<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** SEO — Cấu hình website (1 form key-value). */
class Settings extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

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
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/settings/form';
        $this->__data['page_title']  = 'Cấu hình website';
        $this->__data['content']['page_name'] = 'Cấu hình website';
        $this->__data['content']['settings']  = $this->__model->map();
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

        $this->__model->saveMany($kv);
        Session::flash('msg', 'Đã lưu cấu hình website');
        $this->__response->redirect('admin/settings');
    }
}

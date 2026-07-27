<?php

use App\core\Controller;

/**
 * STOREFRONT — Trang giới thiệu (public).
 *
 * Nội dung lấy từ cấu hình site (about_content nếu có); nếu trống thì
 * dùng bản giới thiệu mặc định. Không tác động dữ liệu.
 */
class Gioithieu extends Controller {

    private $__data = [];

    public function index(){
        $settings = $this->model('SettingsModel')->map();
        $this->__data['sub_content'] = 'storefront/about';
        $this->__data['page_title']  = 'Giới thiệu';
        $this->__data['content']['settings'] = $settings;
        $this->__data['content']['about'] = !empty($settings['about_content']) ? $settings['about_content'] : '';
        $this->render('layouts/storefront/master', $this->__data);
    }
}

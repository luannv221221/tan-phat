<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** SEO — Cấu hình website (1 form key-value). */
class Settings extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    // Các khoá cho phép chỉnh (whitelist)
    private $keys = ['site_name', 'site_slogan', 'meta_description', 'meta_keywords',
                     'og_image', 'hotline', 'email', 'address', 'facebook', 'zalo'];

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

        // upload OG image (tuỳ chọn)
        $up = upload_image('og_image_file', 'settings', 'og');
        if ($up['status'] === 'error'){
            Session::flash('msg', 'Ảnh OG lỗi: ' . $up['message']);
            $this->__response->redirect('admin/settings'); return;
        }

        $f = $this->__request->getFields();
        $kv = [];
        foreach ($this->keys as $k){
            if ($k === 'og_image') continue; // xử lý riêng
            $kv[$k] = isset($f[$k]) ? trim($f[$k]) : '';
        }
        if ($up['status'] === 'ok') $kv['og_image'] = $up['path'];
        elseif (isset($f['og_image'])) $kv['og_image'] = trim($f['og_image']);

        $this->__model->saveMany($kv);
        Session::flash('msg', 'Đã lưu cấu hình website');
        $this->__response->redirect('admin/settings');
    }
}

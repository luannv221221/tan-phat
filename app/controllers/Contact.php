<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * STOREFRONT — Trang Liên hệ + gửi tin nhắn liên hệ (public).
 */
class Contact extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('ContactMessagesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $flash = Session::flash('store_flash');
        $this->__data['sub_content'] = 'storefront/contact';
        $this->__data['page_title']  = 'Liên hệ';
        $c = &$this->__data['content'];
        $c['flash'] = $flash;
        $c['old']   = Session::flash('contact_old');
        $c['seo']   = ['description' => 'Liên hệ Tân Phát — phụ tùng và thiết bị gara ô tô. Gửi yêu cầu tư vấn, báo giá.'];
        $this->render('layouts/storefront/master', $this->__data);
    }

    public function send(){
        $f    = $this->__request->getFields();
        $name = isset($f['name']) ? trim($f['name']) : '';
        $msg  = isset($f['message']) ? trim($f['message']) : '';
        $email= isset($f['email']) ? trim($f['email']) : '';
        $phone= isset($f['phone']) ? trim($f['phone']) : '';

        // Cần tên + (điện thoại hoặc email) + nội dung
        if ($name === '' || $msg === '' || ($phone === '' && $email === '')){
            Session::flash('store_flash', 'err|Vui lòng nhập họ tên, nội dung và ít nhất một cách liên hệ (điện thoại hoặc email).');
            Session::flash('contact_old', $f);
            $this->__response->redirect('lien-he'); return;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
            Session::flash('store_flash', 'err|Email không hợp lệ.');
            Session::flash('contact_old', $f);
            $this->__response->redirect('lien-he'); return;
        }

        $this->__model->add([
            'name'    => $name,
            'email'   => $email !== '' ? $email : null,
            'phone'   => $phone !== '' ? $phone : null,
            'subject' => !empty($f['subject']) ? trim($f['subject']) : null,
            'message' => $msg,
            'status'  => 'new',
            'ip'      => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
        ]);

        Session::flash('store_flash', 'ok|Đã gửi liên hệ. Tân Phát sẽ phản hồi bạn sớm nhất. Cảm ơn bạn!');
        $this->__response->redirect('lien-he');
    }
}

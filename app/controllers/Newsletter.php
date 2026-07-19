<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * STOREFRONT — Đăng ký nhận bản tin (form ở footer). Chỉ nhận POST.
 */
class Newsletter extends Controller {

    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('NewsletterSubscribersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function subscribe(){
        $f     = $this->__request->getFields();
        $email = isset($f['email']) ? trim($f['email']) : '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            Session::flash('store_flash', 'err|Email không hợp lệ, vui lòng kiểm tra lại.');
            $this->__response->redirect('lien-he'); return;
        }

        $res = $this->__model->subscribe($email, 'storefront');
        if ($res === 'exists'){
            Session::flash('store_flash', 'ok|Email của bạn đã đăng ký nhận bản tin rồi. Cảm ơn bạn!');
        } else {
            Session::flash('store_flash', 'ok|Đăng ký nhận bản tin thành công. Cảm ơn bạn đã quan tâm!');
        }
        $this->__response->redirect('lien-he');
    }
}

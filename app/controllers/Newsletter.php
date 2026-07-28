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
            Session::flash('newsletter_flash', 'err|Email không hợp lệ, vui lòng kiểm tra lại.');
            $this->backToForm(); return;
        }

        $res = $this->__model->subscribe($email, 'storefront');
        if ($res === 'exists'){
            Session::flash('newsletter_flash', 'ok|Email của bạn đã đăng ký nhận bản tin rồi. Cảm ơn bạn!');
        } else {
            Session::flash('newsletter_flash', 'ok|Đăng ký nhận bản tin thành công. Cảm ơn bạn đã quan tâm!');
        }
        $this->backToForm();
    }

    /**
     * Quay lại đúng trang vừa bấm đăng ký.
     *
     * Form nằm ở footer của MỌI trang, mà bản cũ luôn đá về /lien-he — khách
     * đang đọc trang chủ bấm đăng ký thì bị nhảy sang trang khác, thông báo
     * hiện ở nơi họ không ngờ tới nên coi như không có phản hồi.
     *
     * Chỉ chấp nhận referer cùng tên miền để không thành open redirect.
     */
    private function backToForm(){
        $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        if ($ref !== '' && strpos($ref, _WEB_URL) === 0){
            header('Location: ' . $ref);
            exit;
        }

        $this->__response->redirect('');
    }
}

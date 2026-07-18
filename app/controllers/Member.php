<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * STOREFRONT — Thành viên: đăng ký / đăng nhập / đăng xuất / tài khoản.
 */
class Member extends Controller {

    private $__data = [];
    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('MembersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function view($sub, $title){
        $this->__data['sub_content'] = $sub;
        $this->__data['page_title']  = $title;
        $this->__data['content']['msg']    = Session::flash('msg');
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old']    = Session::flash('old');
        $this->render('layouts/storefront/master', $this->__data);
    }

    // ---------- Đăng nhập ----------
    public function login(){
        if (!empty(Session::get('dataMember'))){ $this->__response->redirect('thanh-vien'); return; }
        $this->view('storefront/member_login', 'Đăng nhập thành viên');
    }

    public function postLogin(){
        $f = $this->__request->getFields();
        $email = isset($f['email']) ? trim($f['email']) : '';
        $pass  = isset($f['password']) ? $f['password'] : '';

        $m = $this->__model->checkLogin($email, $pass);
        if (empty($m)){
            Session::flash('errors', ['login' => 'Email hoặc mật khẩu không đúng']);
            Session::flash('old', ['email' => $email]);
            $this->__response->redirect('thanh-vien/dang-nhap'); return;
        }
        Session::regenerate();
        Session::set('dataMember', (int) $m['id']);
        Session::flash('msg', 'Đăng nhập thành công. Xin chào ' . $m['name'] . '!');
        $this->__response->redirect('thanh-vien');
    }

    // ---------- Đăng ký ----------
    public function register(){
        if (!empty(Session::get('dataMember'))){ $this->__response->redirect('thanh-vien'); return; }
        $this->view('storefront/member_register', 'Đăng ký thành viên');
    }

    public function postRegister(){
        $f = $this->__request->getFields();
        $name  = isset($f['name']) ? trim($f['name']) : '';
        $email = isset($f['email']) ? trim($f['email']) : '';
        $phone = isset($f['phone']) ? trim($f['phone']) : '';
        $pass  = isset($f['password']) ? $f['password'] : '';
        $pass2 = isset($f['password2']) ? $f['password2'] : '';

        $errors = [];
        if ($name === '')  $errors['name'] = 'Nhập họ tên';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email không hợp lệ';
        elseif (!empty($this->__model->findByEmail($email))) $errors['email'] = 'Email này đã được đăng ký';
        if (strlen($pass) < 6) $errors['password'] = 'Mật khẩu tối thiểu 6 ký tự';
        elseif ($pass !== $pass2) $errors['password2'] = 'Mật khẩu nhập lại không khớp';

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('old', ['name' => $name, 'email' => $email, 'phone' => $phone]);
            $this->__response->redirect('thanh-vien/dang-ky'); return;
        }

        $id = $this->__model->register(['name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $pass]);
        Session::regenerate();
        Session::set('dataMember', (int) $id);
        Session::flash('msg', 'Đăng ký thành công! Bạn đã đăng nhập.');
        $this->__response->redirect('thanh-vien');
    }

    // ---------- Đăng xuất ----------
    public function logout(){
        Session::remove('dataMember');
        Session::flash('msg', 'Đã đăng xuất.');
        $this->__response->redirect('/');
    }

    // ---------- Tài khoản ----------
    public function account(){
        $id = Session::get('dataMember');
        if (empty($id)){ $this->__response->redirect('thanh-vien/dang-nhap'); return; }

        $this->__data['sub_content'] = 'storefront/member_account';
        $this->__data['page_title']  = 'Tài khoản thành viên';
        $this->__data['content']['member'] = $this->__model->getDetail($id);
        $this->__data['content']['msg']    = Session::flash('msg');
        $this->render('layouts/storefront/master', $this->__data);
    }
}

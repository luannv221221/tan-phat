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
        if ($phone !== '' && !is_phone($phone)) $errors['phone'] = 'Số điện thoại không hợp lệ (di động 10 số hoặc cố định 11 số)';
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
        $this->__data['content']['errors'] = Session::flash('errors');
        $this->__data['content']['old']    = Session::flash('old');
        $this->render('layouts/storefront/master', $this->__data);
    }

    /**
     * Cập nhật thông tin cá nhân của thành viên.
     *
     * Trang tài khoản trước đây chỉ là bảng hiển thị — không có form, không có
     * route, nên không ai sửa được gì.
     *
     * KHÔNG cho sửa email: đó là định danh đăng nhập và có ràng buộc duy nhất;
     * đổi email cần luồng xác minh riêng, không gộp vào đây.
     */
    public function postAccount(){
        $id = Session::get('dataMember');
        if (empty($id)){ $this->__response->redirect('thanh-vien/dang-nhap'); return; }

        $f       = $this->__request->getFields();
        $name    = isset($f['name']) ? trim($f['name']) : '';
        $phone   = isset($f['phone']) ? trim($f['phone']) : '';
        $address = isset($f['address']) ? trim($f['address']) : '';

        $errors = [];
        if ($name === '') $errors['name'] = 'Nhập họ tên';
        if ($phone !== '' && !is_phone($phone)){
            $errors['phone'] = 'Số điện thoại không hợp lệ (di động 10 số hoặc cố định 11 số)';
        }

        // Đổi mật khẩu là tuỳ chọn — chỉ xử lý khi có nhập mật khẩu mới
        $newPass = isset($f['new_password']) ? $f['new_password'] : '';
        if ($newPass !== ''){
            $member  = $this->__model->getDetail($id);
            $current = isset($f['current_password']) ? $f['current_password'] : '';

            if ($current === '' || !password_verify($current, $member['password'])){
                $errors['current_password'] = 'Mật khẩu hiện tại không đúng';
            } elseif (strlen($newPass) < 6){
                $errors['new_password'] = 'Mật khẩu mới tối thiểu 6 ký tự';
            } elseif ($newPass !== (isset($f['new_password2']) ? $f['new_password2'] : '')){
                $errors['new_password2'] = 'Mật khẩu nhập lại không khớp';
            }
        }

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('old', ['name' => $name, 'phone' => $phone, 'address' => $address]);
            $this->__response->redirect('thanh-vien'); return;
        }

        $this->__model->updateProfile([
            'name'    => $name,
            'phone'   => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
        ], $id);

        if ($newPass !== ''){
            $this->__model->updatePassword($newPass, $id);
            Session::flash('msg', 'Đã cập nhật thông tin và đổi mật khẩu.');
        } else {
            Session::flash('msg', 'Đã cập nhật thông tin.');
        }

        $this->__response->redirect('thanh-vien');
    }
}

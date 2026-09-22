<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * ADMIN — Tài khoản website (bảng `members`). CHỈ TÂN PHÁT (cờ chi_tan_phat).
 *
 * Tài khoản khách tự đăng ký trên website của Tân Phát để đặt hàng. Trước
 * 22/09/2026 chúng nằm ở màn CSKH › Khách hàng; từ khi các gara độc lập, màn
 * Khách hàng là khách CỦA TỪNG GARA (bảng `partners`, có xe, có phiếu), còn
 * tài khoản website tách ra đây.
 *
 * KHÔNG có XOÁ, KHÔNG có THÊM: khách tự đăng ký; đã có đơn / đánh giá thì xoá
 * đi là đơn mất dấu người đặt. Cần chặn ai đó thì KHOÁ (status = 0).
 */
class Taikhoanweb extends Controller {

    private $routeBase = 'tai-khoan-web';
    private $viewDir   = 'admin/tai-khoan-web';
    private $labelMany = 'Tài khoản website';
    private $perPage   = 20;

    private $__data = [];
    private $__model, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('MembersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelMany'] = $this->labelMany;
    }

    public function index(){
        $f       = $this->__request->getFields();
        $keyword = isset($f['keyword']) ? trim($f['keyword']) : '';
        $status  = isset($f['status']) ? (string) $f['status'] : '';
        $page    = (isset($f['page']) && (int) $f['page'] > 0) ? (int) $f['page'] : 1;

        $perPage    = phan_trang_so_dong($this->perPage);
        $total      = $this->__model->adminCount($keyword, $status);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages) $page = $totalPages;

        /* LIMIT 0 trong MySQL là KHÔNG dòng nào chứ không phải "hết" — "Tất cả"
           phải quy ra một số thật lớn. */
        $limit = $perPage > 0 ? $perPage : PHP_INT_MAX;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name']  = $this->labelMany;
        $c['dataList']   = $this->__model->adminList($keyword, $status, $limit, ($page - 1) * $perPage);
        $c['keyword']    = $keyword;
        $c['filterSt']   = $status;
        $c['page']       = $page;
        $c['perPage']    = $perPage;
        $c['total']      = $total;
        $c['totalPages'] = $totalPages;
        $c['msg']        = Session::flash('msg');
        $c['msgError']   = Session::flash('msgError');

        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /* ===== Tỉnh / phường — để trống cả hai thì thôi; chọn rồi thì phường phải
       thuộc tỉnh, chỉ server kiểm được ===== */

    private function diaGioi(&$errors){
        $f    = $this->__request->getFields();
        $tinh = !empty($f['province_code']) ? (int) $f['province_code'] : 0;
        $xa   = !empty($f['ward_code']) ? (int) $f['ward_code'] : 0;
        if ($tinh <= 0 && $xa <= 0) return null;
        $dg = dia_gioi_tra($tinh, $xa);
        if ($dg === null){
            $errors['province_code'] = 'Chọn lại tỉnh và phường/xã — phường phải thuộc tỉnh đã chọn';
        }
        return $dg;
    }

    private function diaGioiLuu($dg){
        $f = $this->__request->getFields();
        return [
            'province_code' => $dg !== null ? (int) $f['province_code'] : null,
            'province_name' => $dg !== null ? $dg['province'] : null,
            'ward_code'     => $dg !== null ? (int) $f['ward_code'] : null,
            'ward_name'     => $dg !== null ? $dg['ward'] : null,
        ];
    }

    private function diaGioiOld(){
        $f = $this->__request->getFields();
        return [
            'province_code' => !empty($f['province_code']) ? (int) $f['province_code'] : '',
            'ward_code'     => !empty($f['ward_code']) ? (int) $f['ward_code'] : '',
        ];
    }

    public function edit($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy tài khoản.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Sửa tài khoản website';
        $c['item']      = $item;
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
        $c['msg']       = Session::flash('msg');

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa tài khoản website';
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy tài khoản.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $f       = $this->__request->getFields();
        $name    = isset($f['name']) ? trim($f['name']) : '';
        $phone   = isset($f['phone']) ? trim($f['phone']) : '';
        $address = isset($f['address']) ? trim($f['address']) : '';
        $status  = !empty($f['status']) ? 1 : 0;
        $newPass = isset($f['new_password']) ? $f['new_password'] : '';

        $errors = [];
        if ($name === '') $errors['name'] = 'Nhập họ tên';
        $dg = $this->diaGioi($errors);
        if ($phone !== '' && !is_phone($phone)){
            $errors['phone'] = 'Số điện thoại không hợp lệ (di động 10 số hoặc cố định 11 số)';
        }
        // Đặt lại mật khẩu hộ khách thì không cần mật khẩu cũ, nhưng phải đủ dài
        if ($newPass !== '' && strlen($newPass) < 6){
            $errors['new_password'] = 'Mật khẩu tối thiểu 6 ký tự';
        }

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('old', array_merge(['name' => $name, 'phone' => $phone,
                                   'address' => $address, 'status' => $status], $this->diaGioiOld()));
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $this->__model->updateProfile(array_merge([
            'name'    => $name,
            'phone'   => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'status'  => $status,
        ], $this->diaGioiLuu($dg)), (int) $id);

        if ($newPass !== ''){
            $this->__model->updatePassword($newPass, (int) $id);
            Session::flash('msg', 'Đã cập nhật tài khoản và đặt lại mật khẩu.');
        } else {
            Session::flash('msg', 'Đã cập nhật tài khoản.');
        }
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /** Khoá / mở khoá nhanh từ danh sách */
    public function toggle($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy tài khoản.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $new = ((int) $item['status'] === 1) ? 0 : 1;
        $this->__model->updateProfile(['status' => $new], (int) $id);
        Session::flash('msg', $new === 1
            ? 'Đã mở khoá tài khoản ' . $item['email']
            : 'Đã khoá tài khoản ' . $item['email']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

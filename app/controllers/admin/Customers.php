<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * ADMIN — Khách hàng (bảng `members`).
 *
 * VÌ SAO CÓ MÀN HÌNH NÀY: "Quản lý người dùng" đọc bảng `users` — tài khoản
 * nhân viên có group_id và phân quyền. Khách đăng ký ngoài website lại nằm ở
 * bảng `members`, hoàn toàn khác, nên trước đây không hiện ở bất cứ đâu trong
 * admin.
 *
 * KHÔNG có chức năng XOÁ: khách có thể đã phát sinh đơn hàng / đánh giá
 * (khoá ngoại ON DELETE SET NULL nên xoá đi thì đơn mất luôn dấu vết người
 * đặt). Cần chặn ai đó thì KHOÁ tài khoản (status = 0) — đủ để họ không đăng
 * nhập được mà lịch sử vẫn nguyên.
 */
class Customers extends Controller {

    private $routeBase = 'customers';
    private $viewDir   = 'admin/customers';
    private $labelMany = 'Khách hàng';
    private $perPage   = 20;

    private $__data = [];
    private $__model, $__xe, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('MembersModel');
        $this->__xe       = $this->model('MemberVehiclesModel');
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

        // Số dòng/trang chọn ở chân bảng; 0 = "Tất cả".
        $perPage    = phan_trang_so_dong($this->perPage);
        $total      = $this->__model->adminCount($keyword, $status);
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages) $page = $totalPages;

        /* adminList() ghép thẳng vào "LIMIT n" — mà LIMIT 0 trong MySQL là
           KHÔNG dòng nào chứ không phải "hết". Nên "Tất cả" phải quy ra một
           số thật lớn, không truyền 0 xuống. */
        $limit = $perPage > 0 ? $perPage : PHP_INT_MAX;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name']  = $this->labelMany;
        $c['dataList']   = $this->__model->adminList($keyword, $status, $limit, ($page - 1) * $perPage);

        /* Xe cua tung khach — lay MOT lan cho ca trang thay vi hoi lai theo
           tung dong. Danh sach 20 khach ma hoi 20 lan la 20 truy van thua. */
        $c['xeTheoKhach'] = $this->__xe->theoNhieuKhach(
            array_map(function($x){ return $x['id']; }, $c['dataList'] ?: [])
        );
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

    public function edit($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Sửa khách hàng';
        $c['item']      = $item;
        $c['dsXe']      = $this->__xe->getByMember((int) $id);
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
        $c['msg']       = Session::flash('msg');

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa khách hàng';
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
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
        if ($phone !== '' && !is_phone($phone)){
            $errors['phone'] = 'Số điện thoại không hợp lệ (di động 10 số hoặc cố định 11 số)';
        }
        // Admin đặt lại mật khẩu hộ khách thì không cần biết mật khẩu cũ,
        // nhưng vẫn phải đủ dài. Bỏ trống = không đổi.
        if ($newPass !== '' && strlen($newPass) < 6){
            $errors['new_password'] = 'Mật khẩu tối thiểu 6 ký tự';
        }

        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('old', ['name' => $name, 'phone' => $phone, 'address' => $address, 'status' => $status]);
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $this->__model->updateProfile([
            'name'    => $name,
            'phone'   => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'status'  => $status,
        ], (int) $id);

        if ($newPass !== ''){
            $this->__model->updatePassword($newPass, (int) $id);
            Session::flash('msg', 'Đã cập nhật khách hàng và đặt lại mật khẩu.');
        } else {
            Session::flash('msg', 'Đã cập nhật khách hàng.');
        }

        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /* ==================================================================
     * XE CỦA KHÁCH
     *
     * Một khách nhiều xe, nên quản lý ngay trong màn Sửa khách hàng thay vì
     * làm một màn hình riêng: xe không tồn tại độc lập với chủ của nó.
     * Dùng chung quyền `edit` của module customers.
     * ================================================================== */

    /** Thêm một xe cho khách */
    public function xeThem($memberId = 0){
        $kh = $this->__model->getDetail((int) $memberId);
        if (empty($kh)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $memberId)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $f  = $this->__request->getFields();
        $bs = isset($f['bien_so']) ? trim($f['bien_so']) : '';

        if (MemberVehiclesModel::chuanHoaBienSo($bs) === ''){
            Session::flash('msgError', 'Biển số xe không được để trống.');
            $this->quayLaiSua($memberId); return;
        }

        $this->__xe->add([
            'member_id' => (int) $memberId,
            'bien_so'   => $bs,
            'hang_xe'   => $this->hoacNull($f, 'hang_xe'),
            'model_xe'  => $this->hoacNull($f, 'model_xe'),
            'nam_sx'    => !empty($f['nam_sx']) ? (int) $f['nam_sx'] : null,
            'mau_xe'    => $this->hoacNull($f, 'mau_xe'),
            'so_km'     => $this->soKm($f),
            'ghi_chu'   => $this->hoacNull($f, 'ghi_chu'),
        ]);

        Session::flash('msg', 'Đã thêm xe ' . $bs);
        $this->quayLaiSua($memberId);
    }

    /** Sửa một xe (chủ yếu để cập nhật số km mỗi lần xe vào gara) */
    public function xeSua($xeId = 0){
        $xe = $this->__xe->getDetail((int) $xeId);
        if (empty($xe)){
            Session::flash('msgError', 'Không tìm thấy xe.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $xe['member_id'])){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $f  = $this->__request->getFields();
        $bs = isset($f['bien_so']) ? trim($f['bien_so']) : '';

        if (MemberVehiclesModel::chuanHoaBienSo($bs) === ''){
            Session::flash('msgError', 'Biển số xe không được để trống.');
            $this->quayLaiSua($xe['member_id']); return;
        }

        $this->__xe->edit([
            'bien_so'  => $bs,
            'hang_xe'  => $this->hoacNull($f, 'hang_xe'),
            'model_xe' => $this->hoacNull($f, 'model_xe'),
            'nam_sx'   => !empty($f['nam_sx']) ? (int) $f['nam_sx'] : null,
            'mau_xe'   => $this->hoacNull($f, 'mau_xe'),
            'so_km'    => $this->soKm($f),
            'ghi_chu'  => $this->hoacNull($f, 'ghi_chu'),
        ], (int) $xeId);

        Session::flash('msg', 'Đã cập nhật xe ' . $bs);
        $this->quayLaiSua($xe['member_id']);
    }

    public function xeXoa($xeId = 0){
        $xe = $this->__xe->getDetail((int) $xeId);
        if (empty($xe)){
            Session::flash('msgError', 'Không tìm thấy xe.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $xe['member_id'])){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $this->__xe->remove((int) $xeId);
        Session::flash('msg', 'Đã xoá xe ' . $xe['bien_so']);
        $this->quayLaiSua($xe['member_id']);
    }

    private function quayLaiSua($memberId){
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $memberId);
    }

    private function hoacNull(array $f, $ten){
        return (isset($f[$ten]) && trim($f[$ten]) !== '') ? trim($f[$ten]) : null;
    }

    /** Số km: bỏ dấu phân cách nghìn người dùng hay gõ ("120.000", "120,000") */
    private function soKm(array $f){
        if (!isset($f['so_km'])) return null;
        $v = preg_replace('/[^\d]/', '', (string) $f['so_km']);
        return $v === '' ? null : (int) $v;
    }

    /** Khoá / mở khoá nhanh từ danh sách */
    public function toggle($id = 0){
        $item = $this->__model->getDetail((int) $id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $new = ((int) $item['status'] === 1) ? 0 : 1;
        $this->__model->updateProfile(['status' => $new], (int) $id);

        Session::flash('msg', $new === 1
            ? 'Đã mở khoá tài khoản ' . $item['email']
            : 'Đã khoá tài khoản ' . $item['email']);

        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

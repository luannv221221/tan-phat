<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH — Khách hàng CỦA GARA (bảng `partners`, loại khách).
 *
 * Quan điểm xuyên suốt: kho tổng -> nhiều gara -> mỗi gara nhiều KHÁCH -> mỗi
 * khách nhiều XE -> mỗi xe nhiều PHIẾU.
 *
 * VÌ SAO KHÔNG CÒN ĐỌC `members`: trước 22/09/2026 màn này đọc bảng tài khoản
 * website, xe lưu ở một bảng riêng chỉ có biển số / hãng / model gõ tay — không
 * có VIN, số máy, và KHÔNG lập được phiếu tiếp nhận (phiếu gắn với xe của Đối
 * tượng). Chuỗi khách -> xe -> phiếu đứt ngay tại màn này. Giờ màn Khách hàng
 * và màn Bán hàng › Đối tượng cùng đọc / sửa MỘT bản ghi; xe là bảng `vehicles`
 * đầy đủ. Tài khoản website tách sang màn Tài khoản website (chỉ Tân Phát).
 *
 * KHÔNG có XOÁ ở đây: khách đã có xe, phiếu, báo giá. Ngừng giao dịch thì tắt
 * trạng thái. Cần xoá hẳn thì làm ở màn Đối tượng.
 */
class Customers extends Controller {

    private $routeBase = 'customers';
    private $viewDir   = 'admin/customers';
    private $labelMany = 'Khách hàng';
    private $perPage   = 20;

    private $__data = [];
    private $__model, $__xe, $__nhom, $__request, $__response;

    function __construct(){
        $this->__model    = $this->model('PartnersModel');
        $this->__xe       = $this->model('VehiclesModel');
        $this->__nhom     = $this->model('CustomerGroupsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelMany'] = $this->labelMany;
        $c['dsNhom']    = $this->__nhom->getActive();
    }

    /** Khách của gara làm việc — NCC thuần (type = supplier) không phải khách */
    private function khach($id){
        $p = $this->__model->getDetail((int) $id);
        return (!empty($p) && in_array($p['type'], ['customer', 'both'], true)) ? $p : null;
    }

    public function index(){
        $f   = $this->__request->getFields();
        $loc = [
            'q'      => isset($f['q']) ? trim((string) $f['q']) : '',
            'status' => (isset($f['status']) && ($f['status'] === '1' || $f['status'] === '0')) ? $f['status'] : '',
            'group'  => !empty($f['group']) ? (int) $f['group'] : '',
        ];

        // Số dòng/trang chọn ở chân bảng; 0 = "Tất cả"
        $perPage    = phan_trang_so_dong($this->perPage);
        $page       = (isset($f['page']) && (int) $f['page'] > 0) ? (int) $f['page'] : 1;
        $total      = $this->__model->demKhachHang($loc);
        $totalPages = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
        if ($page > $totalPages) $page = $totalPages;
        /* LIMIT 0 trong MySQL là KHÔNG dòng nào chứ không phải "hết" — "Tất cả"
           phải quy ra một số thật lớn. */
        $limit = $perPage > 0 ? $perPage : PHP_INT_MAX;

        $this->baseData();
        $c = &$this->__data['content'];
        $ds = (array) $this->__model->khachHang($loc, $limit, ($page - 1) * $perPage);
        $c['page_name']   = $this->labelMany;
        $c['dataList']    = $ds;
        $c['xeTheoKhach'] = $this->__xe->theoNhieuChu(array_column($ds, 'id'));
        $c['loc']         = $loc;
        $c['dangLoc']     = ($loc['q'] !== '' || $loc['status'] !== '' || $loc['group'] !== '');
        $c['tongTatCa']   = $c['dangLoc'] ? $this->__model->demKhachHang([]) : $total;
        $c['page']        = $page;
        $c['perPage']     = $perPage;
        $c['total']       = $total;
        $c['totalPages']  = $totalPages;
        $c['msg']         = Session::flash('msg');
        $c['msgError']    = Session::flash('msgError');

        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Thêm khách hàng';
        $c['maMoi']     = $this->__model->nextCode('KH-');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
        $c['msg']       = Session::flash('msg');

        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm khách hàng';
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        list($errors, $data) = $this->docForm(null);
        if (!empty($errors)){ $this->quayLai($errors, 'add'); return; }

        $id = $this->__model->add(array_merge($data, [
            'code'   => $this->__model->nextCode('KH-'),
            'type'   => 'customer',
            'status' => 1,
        ]));

        /* Sang thẳng màn Sửa: gần như lần nào thêm khách ở gara cũng là để khai
           luôn chiếc xe họ vừa mang tới, mà khối "Xe của khách" nằm ở đó. */
        Session::flash('msg', 'Đã thêm khách hàng. Khai xe của khách ngay bên dưới.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function edit($id = 0){
        $item = $this->khach($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $xe = (array) $this->__xe->theoChu((int) $id);
        $soPhieu = [];
        foreach ($xe as $x) $soPhieu[(int) $x['id']] = $this->__xe->demPhieu((int) $x['id']);

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Khách hàng ' . $item['name'];
        $c['item']      = $item;
        $c['dsXe']      = $xe;
        $c['soPhieu']   = $soPhieu;
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Khách hàng ' . $item['name'];
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id = 0){
        $item = $this->khach($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        list($errors, $data) = $this->docForm((int) $id);
        if (!empty($errors)){ $this->quayLai($errors, 'edit/' . (int) $id); return; }

        $f = $this->__request->getFields();
        $data['status'] = !empty($f['status']) ? 1 : 0;
        $this->__model->edit($data, (int) $id);
        Session::flash('msg', 'Đã cập nhật khách hàng.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    /** Tắt / bật nhanh từ danh sách */
    public function toggle($id = 0){
        $item = $this->khach($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy khách hàng.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . (int) $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $new = ((int) $item['status'] === 1) ? 0 : 1;
        $this->__model->edit(['status' => $new], (int) $id);
        Session::flash('msg', ($new === 1 ? 'Đã bật lại khách ' : 'Đã tắt khách ') . $item['name']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    /**
     * Đọc + kiểm form thêm / sửa. Trả [lỗi, dữ liệu để lưu].
     * $id = null khi thêm (lúc đó mới cảnh báo trùng số điện thoại).
     */
    private function docForm($id){
        $f       = $this->__request->getFields();
        $name    = isset($f['name']) ? trim((string) $f['name']) : '';
        $phone   = isset($f['phone']) ? trim((string) $f['phone']) : '';
        $email   = isset($f['email']) ? trim((string) $f['email']) : '';
        $address = isset($f['address']) ? trim((string) $f['address']) : '';
        $nhomId  = !empty($f['group_id']) ? (int) $f['group_id'] : 0;

        $errors = [];
        if ($name === '') $errors['name'] = 'Nhập họ tên khách';

        /* Phải có ÍT NHẤT một cách liên lạc: không có thì hồ sơ này về sau không
           ai tra ra được là của ai — mà ở gara, số điện thoại mới là thứ nhận ra
           người. */
        if ($phone === '' && $email === ''){
            $errors['phone'] = 'Nhập số điện thoại hoặc email — cần ít nhất một cách liên lạc';
        }
        if ($phone !== '' && !is_phone($phone)){
            $errors['phone'] = 'Số điện thoại không hợp lệ (di động 10 số hoặc cố định 11 số)';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $errors['email'] = 'Email không đúng định dạng';
        }

        /* Trùng số điện thoại chỉ CẢNH BÁO chứ không chặn: hai vợ chồng dùng
           chung một số là chuyện thường. Nhưng phải nói ra, vì tạo trùng người
           thì lịch sử xe của khách bị chia đôi. Chỉ so trong gara này. */
        if ($id === null && $phone !== '' && empty($errors['phone'])){
            $trung = $this->__model->findByPhone($phone);
            if (!empty($trung) && empty($f['xac_nhan_trung'])){
                $errors['phone'] = 'Số này đã thuộc về khách "' . $trung['name']
                                 . '". Tích vào ô xác nhận bên dưới nếu vẫn muốn tạo hồ sơ mới.';
            }
        }

        // Nhóm khách phải là nhóm CỦA GARA NÀY
        if ($nhomId > 0 && empty($this->__nhom->getDetail($nhomId))){
            $errors['group_id'] = 'Nhóm khách không hợp lệ';
        }

        /* Tỉnh / phường: để trống cả hai thì thôi; chọn rồi thì phường phải
           thuộc tỉnh — chỉ server kiểm được. Tên lấy từ nguồn dữ liệu, không
           nhận tên client gửi lên. */
        $tinh = !empty($f['province_code']) ? (int) $f['province_code'] : 0;
        $xa   = !empty($f['ward_code']) ? (int) $f['ward_code'] : 0;
        $dg   = null;
        if ($tinh > 0 || $xa > 0){
            $dg = dia_gioi_tra($tinh, $xa);
            if ($dg === null) $errors['province_code'] = 'Chọn lại tỉnh và phường/xã — phường phải thuộc tỉnh đã chọn';
        }

        return [$errors, [
            'name'          => $name,
            'phone'         => $phone !== '' ? $phone : null,
            'email'         => $email !== '' ? $email : null,
            'address'       => $address !== '' ? $address : null,
            'group_id'      => $nhomId > 0 ? $nhomId : null,
            'province_code' => $dg !== null ? $tinh : null,
            'province_name' => $dg !== null ? $dg['province'] : null,
            'ward_code'     => $dg !== null ? $xa : null,
            'ward_name'     => $dg !== null ? $dg['ward'] : null,
        ]];
    }

    private function quayLai($errors, $back){
        $f = $this->__request->getFields();
        Session::flash('errors', $errors);
        Session::flash('old', [
            'name'          => isset($f['name']) ? $f['name'] : '',
            'phone'         => isset($f['phone']) ? $f['phone'] : '',
            'email'         => isset($f['email']) ? $f['email'] : '',
            'address'       => isset($f['address']) ? $f['address'] : '',
            'group_id'      => isset($f['group_id']) ? $f['group_id'] : '',
            'status'        => !empty($f['status']) ? 1 : 0,
            'province_code' => !empty($f['province_code']) ? (int) $f['province_code'] : '',
            'ward_code'     => !empty($f['ward_code']) ? (int) $f['ward_code'] : '',
        ]);
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

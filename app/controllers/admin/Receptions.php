<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * PHIẾU TIẾP NHẬN (CSKH) — một lần xe vào xưởng.
 *
 * Tầng còn thiếu của mô hình: một XE nhiều lần vào xưởng, mỗi lần vào sinh ra
 * báo giá / hoá đơn / phiếu bảo hành của riêng lần đó. Trước đây các chứng từ
 * ấy rời nhau nên không trả lời được "lần vào xưởng hôm ấy làm những gì".
 *
 * Xe KHÔNG đổi được sau khi lập phiếu: chứng từ đã gắn vào phiếu, đổi xe là
 * lịch sử của hai xe lẫn vào nhau. Lập nhầm thì huỷ phiếu rồi lập phiếu khác.
 */
class Receptions extends Controller {

    private $__data = [];
    private $__model, $__vehicle, $__user, $__request, $__response;

    private $routeBase = 'receptions';
    private $labelOne  = 'phiếu tiếp nhận';
    private $labelMany = 'Phiếu tiếp nhận';
    private $viewDir   = 'admin/receptions';

    function __construct(){
        $this->__model    = $this->model('ReceptionsModel');
        $this->__vehicle  = $this->model('VehiclesModel');
        $this->__user     = $this->model('UsersModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelOne']  = $this->labelOne;
        $c['statuses']  = ReceptionsModel::$statuses;
    }

    private function formData(){
        $c = &$this->__data['content'];
        $c['xeDs']    = $this->__vehicle->getLists(['status' => '1']);
        // Cố vấn là nhân viên CỦA GARA NÀY — không liệt kê người của gara khác
        $c['coVanDs'] = $this->__user->getLists(['users.status' => 1, 'users.garage_id' => gara_hien_tai_id()]);
    }

    /** Nhân viên thuộc gara làm việc — cố vấn gửi lên phải là người của gara mình */
    private function laNhanVienGara($userId){
        $u = $this->__user->getDetail((int) $userId);
        return !empty($u) && (int) $u['garage_id'] === (int) gara_hien_tai_id();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();

        $f   = $this->__request->getFields();
        $loc = [
            'q'       => isset($f['q']) ? trim((string) $f['q']) : '',
            'status'  => (isset($f['status']) && isset(ReceptionsModel::$statuses[$f['status']])) ? $f['status'] : '',
            'from'    => isset($f['from']) ? trim((string) $f['from']) : '',
            'to'      => isset($f['to']) ? trim((string) $f['to']) : '',
            'dang_mo' => !empty($f['dang_mo']) ? '1' : '',
        ];

        $c = &$this->__data['content'];
        $c['page_name'] = $this->labelMany;
        $c['dataList']  = $this->__model->getLists($loc);
        $c['loc']       = $loc;
        $c['dangLoc']   = ($loc['q'] !== '' || $loc['status'] !== '' || $loc['from'] !== '' || $loc['to'] !== '' || $loc['dang_mo'] !== '');
        $c['tongTatCa'] = count((array) $this->__model->getLists());
        $c['demDangMo'] = count((array) $this->__model->getLists(['dang_mo' => '1']));
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $f   = $this->__request->getFields();
        $old = Session::flash('old');
        // Mở từ màn Xe ("Tiếp nhận xe này"): điền sẵn xe và số km đang ghi nhận
        if (empty($old) && !empty($f['vehicle_id'])) $old = ['vehicle_id' => (int) $f['vehicle_id']];

        $xe = !empty($old['vehicle_id']) ? $this->__vehicle->getDetail((int) $old['vehicle_id']) : null;
        if (!empty($xe) && !isset($old['km_vao']) && $xe['so_km'] !== null) $old['km_vao'] = (int) $xe['so_km'];

        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Tiếp nhận xe';
        $this->baseData();
        $this->formData();

        $c = &$this->__data['content'];
        $c['page_name'] = 'Tiếp nhận xe';
        $c['item']      = null;
        $c['xe']        = $xe;
        $c['old']       = $old;
        $c['today']     = date('Y-m-d');
        $c['soPhieu']   = $this->__model->nextNo();
        $c['errors']    = Session::flash('errors');
        $c['msg']       = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate(null);
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $f  = $this->__request->getFields();
        $xe = $this->__vehicle->getDetail((int) $f['vehicle_id']);
        $no = $this->__model->nextNo();

        $id = $this->__model->add(array_merge($this->buildData(), [
            'reception_no' => $no,
            'vehicle_id'   => (int) $f['vehicle_id'],
            /* Chủ xe chụp lại lúc tiếp nhận: xe sang tay thì phiếu cũ vẫn ghi
               đúng người mang xe tới hôm ấy. */
            'partner_id'   => !empty($xe['partner_id']) ? (int) $xe['partner_id'] : null,
            'created_by'   => Session::get('dataUser'),
        ]));

        // Km vào là số đo mới nhất của xe -> cập nhật cho xe (chỉ tăng)
        $kmVao = $this->soNguyen($f, 'km_vao');
        if ($kmVao > 0) $this->__vehicle->capNhatKm((int) $f['vehicle_id'], $kmVao);

        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' ' . $no . ' cho xe ' . $xe['bien_so']);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Phiếu ' . $item['reception_no'];
        $this->baseData();
        $this->formData();

        $c = &$this->__data['content'];
        $c['page_name'] = 'Phiếu ' . $item['reception_no'];
        $c['item']      = $item;
        $c['tenXe']     = VehiclesModel::tenXe($item);
        $c['chungTu']   = $this->__model->chungTu($id);
        $c['old']       = Session::flash('old');
        $c['errors']    = Session::flash('errors');
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $errors = $this->validate((int) $id, $item);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . (int) $id); return; }

        // Xe không đổi được -> buildData() không chứa vehicle_id
        $this->__model->edit($this->buildData(), (int) $id);

        foreach (['km_vao', 'km_ra'] as $k){
            $km = $this->soNguyen($this->__request->getFields(), $k);
            if ($km > 0) $this->__vehicle->capNhatKm((int) $item['vehicle_id'], $km);
        }

        Session::flash('msg', 'Đã cập nhật ' . $this->labelOne);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    /** Đổi trạng thái; giao xe thì tự điền ngày ra */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f  = $this->__request->getFields();
        $st = isset($f['status']) ? (string) $f['status'] : '';
        if (!isset(ReceptionsModel::$statuses[$st])){
            Session::flash('msgError', 'Trạng thái không hợp lệ');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $data = ['status' => $st];
        if ($st === 'da_giao' && empty($item['ngay_ra'])) $data['ngay_ra'] = date('Y-m-d');
        if ($st !== 'da_giao' && $st !== 'hoan_tat')      $data['ngay_ra'] = null;

        $this->__model->edit($data, (int) $id);
        Session::flash('msg', 'Đã chuyển trạng thái: ' . ReceptionsModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        /* Phiếu đã có chứng từ thì KHÔNG xoá: báo giá / hoá đơn / phiếu bảo hành
           đang trỏ vào đây, xoá đi là chúng mất chỗ dựa. Lập nhầm thì chuyển
           trạng thái sang Đã huỷ. */
        $so = $this->__model->demChungTu($id);
        if ($so > 0){
            Session::flash('msgError', 'Không xoá được: phiếu này đã có ' . $so
                . ' chứng từ gắn vào. Chuyển trạng thái sang "Đã huỷ" nếu lập nhầm.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Đã xoá ' . $this->labelOne . ' ' . $item['reception_no']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function soNguyen($f, $k){
        if (!isset($f[$k])) return 0;
        $v = preg_replace('/[^\d]/', '', (string) $f[$k]);
        return $v === '' ? 0 : (int) $v;
    }

    private function validate($id, $item = null){
        $f      = $this->__request->getFields();
        $errors = [];

        // Xe: bắt buộc khi LẬP; khi sửa thì lấy xe của phiếu (không đổi được)
        if ($id === null){
            $xeId = !empty($f['vehicle_id']) ? (int) $f['vehicle_id'] : 0;
            $xe   = $xeId > 0 ? $this->__vehicle->getDetail($xeId) : null;
            if (empty($xe)) $errors['vehicle_id'] = 'Chọn xe — chưa có thì khai xe mới ở màn Xe của khách';
        } else {
            $xe = $this->__vehicle->getDetail((int) $item['vehicle_id']);
        }

        if (empty($f['ngay_vao'])) $errors['ngay_vao'] = 'Chọn ngày xe vào';

        $kmVao = $this->soNguyen($f, 'km_vao');
        $kmRa  = $this->soNguyen($f, 'km_ra');

        /* Đồng hồ xe không quay lui: km vào nhỏ hơn số km đã ghi nhận của xe
           gần như chắc chắn là gõ thiếu chữ số. Không chặn thì mốc nhắc bảo trì
           của xe bị kéo tụt. */
        if ($kmVao > 0 && !empty($xe) && $xe['so_km'] !== null && $kmVao < (int) $xe['so_km']){
            $errors['km_vao'] = 'Km vào (' . number_format($kmVao, 0, ',', '.') . ') nhỏ hơn số km đã ghi nhận của xe ('
                              . number_format((int) $xe['so_km'], 0, ',', '.') . ') — kiểm tra lại';
        }
        if ($kmVao > 0 && $kmRa > 0 && $kmRa < $kmVao){
            $errors['km_ra'] = 'Km ra không thể nhỏ hơn km vào';
        }
        if (!empty($f['ngay_ra']) && !empty($f['ngay_vao']) && $f['ngay_ra'] < $f['ngay_vao']){
            $errors['ngay_ra'] = 'Ngày ra không thể trước ngày vào';
        }
        if (!empty($f['co_van_id']) && !$this->laNhanVienGara((int) $f['co_van_id'])){
            $errors['co_van_id'] = 'Cố vấn dịch vụ không hợp lệ';
        }
        return $errors;
    }

    private function buildData(){
        $f     = $this->__request->getFields();
        $kmVao = $this->soNguyen($f, 'km_vao');
        $kmRa  = $this->soNguyen($f, 'km_ra');

        return [
            'ngay_vao'      => $f['ngay_vao'],
            'ngay_ra'       => !empty($f['ngay_ra']) ? $f['ngay_ra'] : null,
            'km_vao'        => $kmVao > 0 ? $kmVao : null,
            'km_ra'         => $kmRa > 0 ? $kmRa : null,
            'tinh_trang_xe' => !empty($f['tinh_trang_xe']) ? trim($f['tinh_trang_xe']) : null,
            'yeu_cau_khach' => !empty($f['yeu_cau_khach']) ? trim($f['yeu_cau_khach']) : null,
            'co_van_id'     => !empty($f['co_van_id']) ? (int) $f['co_van_id'] : null,
            // Tên gõ tay chỉ dùng khi KHÔNG chọn được trong danh sách nhân viên
            'co_van'        => empty($f['co_van_id']) && !empty($f['co_van']) ? trim($f['co_van']) : null,
            'status'        => ReceptionsModel::statusHopLe($f['status'] ?? ''),
            'note'          => !empty($f['note']) ? trim($f['note']) : null,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

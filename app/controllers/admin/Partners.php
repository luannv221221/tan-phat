<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** KT-4 — Đối tượng (khách hàng / nhà cung cấp). */
class Partners extends Controller {

    private $__data = [];
    private $__model, $__vehicle, $__request, $__response;

    private $routeBase = 'partners';
    private $labelOne  = 'đối tượng';
    private $labelMany = 'Đối tượng (khách / NCC)';
    private $viewDir   = 'admin/partners';

    function __construct(){
        $this->__model    = $this->model('PartnersModel');
        $this->__vehicle  = $this->model('VehiclesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['types']     = PartnersModel::$types;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();

        /* Bộ lọc đọc từ URL (GET) nên dán link cho người khác là họ thấy đúng
           danh sách đang xem, và bấm sang trang 2 không mất bộ lọc —
           phan_trang_qs() giữ mọi tham số trừ page/per_page.
           Giá trị lạ trên URL rơi về "tất cả" chứ không đưa thẳng vào truy vấn. */
        $f = $this->__request->getFields();
        $loc = [
            'q'      => isset($f['q']) ? trim((string) $f['q']) : '',
            'type'   => (isset($f['type']) && isset(PartnersModel::$types[$f['type']])) ? $f['type'] : '',
            'group'  => (isset($f['group']) && ($f['group'] === 'none' || (int) $f['group'] > 0))
                        ? (string) $f['group'] : '',
            'status' => (isset($f['status']) && ($f['status'] === '1' || $f['status'] === '0'))
                        ? $f['status'] : '',
        ];

        $c = &$this->__data['content'];
        $c['page_name']    = $this->labelMany;
        $c['dataList']     = $this->__model->getLists($loc);
        $c['loc']          = $loc;
        $c['dangLoc']      = ($loc['q'] !== '' || $loc['type'] !== '' || $loc['group'] !== '' || $loc['status'] !== '');
        $c['tongTatCa']    = $this->__model->demTatCa();
        $c['dsNhomKhach']  = $this->model('CustomerGroupsModel')->getActive();
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Thêm ' . $this->labelOne;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput(null);
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $id = $this->__model->add($this->buildData());
        /* Sang thẳng màn Sửa: gần như lần nào thêm khách ở gara cũng là để
           khai luôn chiếc xe họ vừa mang tới, mà khối "Xe của khách" nằm ở đó. */
        Session::flash('msg', 'Đã thêm ' . $this->labelOne . '. Khai xe của khách ngay bên dưới.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Sửa ' . $this->labelOne;
        $this->baseData();
        $this->__data['content']['page_name'] = 'Sửa ' . $this->labelOne;
        $this->__data['content']['item']      = $item;
        // Một khách nhiều xe — khối "Xe của khách" nằm ngay trên màn này
        $this->__data['content']['xeDs']      = $this->__vehicle->theoChu($id);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $errors = $this->validateInput($id);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $this->__model->edit($this->buildData(), $id);
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function validateInput($id){
        $f = $this->__request->getFields();
        $errors = [];
        $code = isset($f['code']) ? trim($f['code']) : '';
        if ($code === ''){
            $errors['code'] = 'Mã đối tượng không được để trống';
        } else {
            $ex = $this->__model->findByCode($code);
            if (!empty($ex) && ($id === null || $ex['id'] != $id)){
                $errors['code'] = 'Mã này đã tồn tại';
            }
        }
        if (!isset($f['name']) || trim($f['name']) === ''){
            $errors['name'] = 'Tên đối tượng không được để trống';
        }
        /* Tỉnh / phường: để trống cả hai thì thôi (NCC nước ngoài, dữ liệu cũ).
           Chọn rồi thì PHẢI khớp nhau — trình duyệt gửi lên mã gì cũng được,
           chỉ server mới kiểm được phường có thuộc tỉnh đó không. */
        $tinh = !empty($f['province_code']) ? (int) $f['province_code'] : 0;
        $xa   = !empty($f['ward_code']) ? (int) $f['ward_code'] : 0;
        if (($tinh > 0 || $xa > 0) && dia_gioi_tra($tinh, $xa) === null){
            $errors['province_code'] = 'Chọn lại tỉnh và phường/xã — phường phải thuộc tỉnh đã chọn';
        }
        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();
        $type = isset($f['type']) && isset(PartnersModel::$types[$f['type']]) ? $f['type'] : 'both';
        // Tên tỉnh/phường lấy từ nguồn dữ liệu, KHÔNG nhận tên do client gửi
        $dg = dia_gioi_tra(isset($f['province_code']) ? $f['province_code'] : 0,
                           isset($f['ward_code']) ? $f['ward_code'] : 0);
        return [
            'code'       => trim($f['code']),
            'name'       => trim($f['name']),
            'type'       => $type,
            'tax_code'   => !empty($f['tax_code']) ? trim($f['tax_code']) : null,
            'phone'      => !empty($f['phone']) ? trim($f['phone']) : null,
            'address'    => !empty($f['address']) ? trim($f['address']) : null,
            /* Lưu cả MÃ và TÊN: đơn vị hành chính còn sáp nhập / đổi tên nữa,
               và API ngoài có thể chết — địa chỉ đã lưu vẫn phải đọc được. */
            'province_code' => $dg !== null ? (int) $f['province_code'] : null,
            'province_name' => $dg !== null ? $dg['province'] : null,
            'ward_code'     => $dg !== null ? (int) $f['ward_code'] : null,
            'ward_name'     => $dg !== null ? $dg['ward'] : null,
            'sort_order' => isset($f['sort_order']) ? (int) $f['sort_order'] : 0,
            'status'     => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

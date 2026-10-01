<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * XE CỦA KHÁCH (CSKH) — một khách nhiều xe, một xe nhiều phiếu tiếp nhận.
 *
 * Trước đây biển số là chữ gõ tay rời rạc trên từng chứng từ, không có bản ghi
 * xe nào nối lại. Màn này là gốc của mô hình mới: mọi phiếu tiếp nhận, báo giá,
 * hoá đơn, phiếu bảo hành đều trỏ về một dòng ở đây.
 *
 * Biển số DUY NHẤT (so trên cột chuẩn hoá) — chống tạo hai lần cùng một xe.
 * Số khung để trống được, nhưng đã ghi thì không được trùng.
 */
class Vehicles extends Controller {

    private $__data = [];
    private $__model, $__partner, $__reception, $__request, $__response;

    private $routeBase = 'vehicles';
    private $labelOne  = 'xe';
    private $labelMany = 'Xe của khách';
    private $viewDir   = 'admin/vehicles';

    function __construct(){
        $this->__model     = $this->model('VehiclesModel');
        $this->__partner   = $this->model('PartnersModel');
        $this->__reception = $this->model('ReceptionsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelOne']  = $this->labelOne;
    }

    private function formData(){
        $c = &$this->__data['content'];
        $c['partners'] = $this->__partner->getLists(['type' => 'customer', 'status' => '1']);
        $c['hangDs']   = $this->__model->hangDanhMuc();
        $c['mauDs']    = $this->__model->mauDanhMuc();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();

        $f   = $this->__request->getFields();
        $loc = [
            'q'         => isset($f['q']) ? trim((string) $f['q']) : '',
            'brand_id'  => !empty($f['brand_id']) ? (int) $f['brand_id'] : 0,
            'status'    => (isset($f['status']) && ($f['status'] === '1' || $f['status'] === '0')) ? $f['status'] : '',
            'khong_chu' => !empty($f['khong_chu']) ? '1' : '',
        ];

        $ds = (array) $this->__model->getLists($loc);
        /* Đếm lần vào xưởng của từng xe: cột này là lý do người ta mở màn hình
           này — biết xe nào quay lại nhiều. */
        $demPhieu = [];
        foreach ($ds as $r) $demPhieu[(int) $r['id']] = $this->__model->demPhieu((int) $r['id']);

        $c = &$this->__data['content'];
        $c['page_name'] = $this->labelMany;
        $c['dataList']  = $ds;
        $c['demPhieu']  = $demPhieu;
        $c['loc']       = $loc;
        $c['dangLoc']   = ($loc['q'] !== '' || $loc['brand_id'] > 0 || $loc['status'] !== '' || $loc['khong_chu'] !== '');
        $c['tongTatCa'] = count((array) $this->__model->getLists());
        $c['hangDs']    = $this->__model->hangDanhMuc();
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Thêm xe';
        $this->baseData();
        $this->formData();

        $f   = $this->__request->getFields();
        $old = Session::flash('old');
        // Mở từ màn Đối tượng: điền sẵn chủ xe, lưu xong quay về đúng chỗ đó
        if (empty($old) && !empty($f['partner_id'])) $old = ['partner_id' => (int) $f['partner_id']];

        $c = &$this->__data['content'];
        $c['page_name'] = 'Thêm xe';
        $c['item']      = null;
        $c['old']       = $old;
        // Lưu lỗi quay lại form thì tham số trên URL mất — đọc lại từ `old`
        $ve = !empty($f['ve']) ? (string) $f['ve'] : (!empty($old['ve']) ? (string) $old['ve'] : '');
        $c['ve']        = in_array($ve, ['partner', 'customer'], true) ? $ve : '';
        $c['errors']    = Session::flash('errors');
        $c['msg']       = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate(null);
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $id = $this->__model->add($this->buildData());
        Session::flash('msg', 'Đã thêm xe ' . $this->__request->getFields()['bien_so']);

        // Quay về màn Đối tượng / Khách hàng nếu xe được khai từ đó
        $f = $this->__request->getFields();
        if (!empty($f['ve']) && !empty($f['partner_id'])){
            if ($f['ve'] === 'partner'){ $this->__response->redirect('admin/partners/edit/' . (int) $f['partner_id']); return; }
            if ($f['ve'] === 'customer'){ $this->__response->redirect('admin/customers/edit/' . (int) $f['partner_id']); return; }
        }
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy xe');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Xe ' . $item['bien_so'];
        $this->baseData();
        $this->formData();

        $c = &$this->__data['content'];
        $c['page_name'] = 'Xe ' . $item['bien_so'];
        $c['item']      = $item;
        $c['tenXe']     = VehiclesModel::tenXe($item);
        $c['old']       = Session::flash('old');
        $c['errors']    = Session::flash('errors');
        // Lịch sử xe: các lần vào xưởng + chứng từ của xe này
        $c['phieuDs']   = $this->__reception->theoXe($id);
        $c['chungTu']   = $this->__model->chungTuTheoXe($id);
        $c['statusTN']  = ReceptionsModel::$statuses;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        if (empty($this->__model->getDetail($id))){
            Session::flash('msgError', 'Không tìm thấy xe');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $errors = $this->validate((int) $id);
        if (!empty($errors)){ $this->flash($errors, 'edit/' . (int) $id); return; }

        $this->__model->edit($this->buildData(), (int) $id);
        Session::flash('msg', 'Đã cập nhật xe');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy xe');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        /* Xe còn phiếu tiếp nhận thì KHÔNG xoá: xoá đi là mất lịch sử sửa chữa
           của xe đó, mà chứng từ cũ vẫn còn nằm đấy. Muốn ẩn thì tắt trạng thái. */
        $soPhieu = $this->__model->demPhieu($id);
        if ($soPhieu > 0){
            Session::flash('msgError', 'Không xoá được: xe này đã có ' . $soPhieu
                . ' phiếu tiếp nhận. Tắt trạng thái nếu muốn ẩn xe khỏi danh sách chọn.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Đã xoá xe ' . $item['bien_so']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    /* ===== JSON cho ô chọn dây chuyền hãng → model → năm ===== */

    private function ra($data){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function models($brandId = 0){ $this->ra($this->__model->modelTheoHang($brandId)); }
    public function years($modelId = 0){  $this->ra($this->__model->namTheoModel($modelId)); }

    /** Xe của một khách — cho ô chọn xe trên báo giá / hoá đơn / phiếu bảo hành */
    public function xeTheoKhach($partnerId = 0){ $this->ra($this->__model->chonTheoChu($partnerId)); }

    /** Hãng xe trong danh mục (JSON) — cho ô chọn ở hộp thêm xe nhanh */
    public function hang(){
        $ds = [];
        foreach ((array) $this->__model->hangDanhMuc() as $h) $ds[] = ['c' => (int) $h['id'], 'n' => $h['name']];
        $this->ra($ds);
    }

    /** Màu xe trong danh mục (JSON) — cho ô chọn màu ở form thêm xe nhanh */
    public function mau(){
        $ds = [];
        foreach ($this->__model->mauDanhMuc() as $m) $ds[] = ['c' => (int) $m['id'], 'n' => $m['name']];
        $this->ra($ds);
    }

    /**
     * Thêm nhanh một chiếc xe cho khách, gọi từ nút + cạnh ô chọn xe trên báo
     * giá / hoá đơn / phiếu bảo hành.
     *
     * Trả về đúng dạng mà ô chọn xe đang dùng, để thêm xong là chọn được ngay
     * mà không phải tải lại trang và mất những gì đang gõ dở.
     */
    public function themXeNhanh(){
        $f         = $this->__request->getFields();
        $partnerId = !empty($f['partner_id']) ? (int) $f['partner_id'] : 0;
        $bienSo    = isset($f['bien_so']) ? trim((string) $f['bien_so']) : '';

        if ($partnerId <= 0 || empty($this->__partner->getDetail($partnerId))){
            $this->ra(['ok' => false, 'loi' => 'Chọn khách hàng trước']);
        }

        $errors = $this->validate(null);
        if (!empty($errors)){
            $this->ra(['ok' => false, 'loi' => reset($errors)]);
        }

        $dat = $this->buildData();
        $dat['partner_id'] = $partnerId;          // xe thêm từ chứng từ luôn thuộc khách đang chọn
        $this->__model->add($dat);

        /* Trả về đúng dạng của chonTheoChu(): giá trị là BIỂN SỐ, không phải
           id — chứng từ lưu biển số. Lấy lại cả danh sách để ô chọn dựng lại
           nguyên vẹn, xe mới nằm đúng chỗ của nó. */
        $this->ra(['ok' => true, 'bien_so' => $bienSo,
                   'ds'  => $this->__model->chonTheoChu($partnerId)]);
    }

    // ===== Helper =====

    private function validate($id){
        $f      = $this->__request->getFields();
        $errors = [];

        $bienSo = isset($f['bien_so']) ? trim((string) $f['bien_so']) : '';
        $chuan  = chuan_hoa_bien_so($bienSo);
        if ($chuan === ''){
            $errors['bien_so'] = 'Nhập biển số xe';
        } else {
            $trung = $this->__model->theoBienSo($bienSo);
            if (!empty($trung) && ($id === null || (int) $trung['id'] !== (int) $id)){
                $errors['bien_so'] = 'Biển số này đã có xe khác — mở xe đó ra thay vì tạo mới';
            }
        }

        /* Số khung để trống được. Đã ghi thì không được trùng: hai xe không thể
           cùng số khung, trùng ở đây gần như chắc chắn là gõ nhầm. */
        $vin = isset($f['so_khung']) ? strtoupper(trim((string) $f['so_khung'])) : '';
        if ($vin !== ''){
            $trung = $this->__model->theoSoKhung($vin);
            if (!empty($trung) && ($id === null || (int) $trung['id'] !== (int) $id)){
                $errors['so_khung'] = 'Số khung này đã có xe khác';
            }
        }

        /* Hãng và model BẮT BUỘC chọn từ danh mục. Trước đây được bỏ trống rồi
           gõ chữ vào ô riêng, nên lọc theo hãng bỏ sót xe và báo cáo không gom
           được. Danh mục thiếu thì thêm ngay bằng nút + trên form. */
        $brandId = !empty($f['brand_id']) ? (int) $f['brand_id'] : 0;
        $modelId = !empty($f['model_id']) ? (int) $f['model_id'] : 0;
        $yearId  = !empty($f['car_year_id']) ? (int) $f['car_year_id'] : 0;
        $colorId = !empty($f['color_id']) ? (int) $f['color_id'] : 0;

        if ($brandId <= 0){
            $errors['brand_id'] = 'Chọn hãng xe — thiếu trong danh mục thì bấm dấu + để thêm';
        }
        if ($modelId <= 0){
            $errors['model_id'] = 'Chọn model — thiếu trong danh mục thì bấm dấu + để thêm';
        }

        // Model phải thuộc hãng, năm phải thuộc model — kiểm ở server
        if (!$this->__model->modelThuocHang($modelId, $brandId)){
            $errors['model_id'] = 'Chọn lại hãng và model — model phải thuộc hãng đã chọn';
        }
        if (!$this->__model->namThuocModel($yearId, $modelId)){
            $errors['car_year_id'] = 'Chọn lại model và năm — năm phải thuộc model đã chọn';
        }
        if (!$this->__model->mauCoThat($colorId)){
            $errors['color_id'] = 'Màu xe không có trong danh mục';
        }

        if (!empty($f['partner_id']) && empty($this->__partner->getDetail((int) $f['partner_id']))){
            $errors['partner_id'] = 'Chủ xe không hợp lệ';
        }
        return $errors;
    }

    private function buildData(){
        $f = $this->__request->getFields();

        $bienSo  = trim((string) $f['bien_so']);
        $vin     = isset($f['so_khung']) ? strtoupper(trim((string) $f['so_khung'])) : '';
        $brandId = !empty($f['brand_id']) ? (int) $f['brand_id'] : null;
        $modelId = !empty($f['model_id']) ? (int) $f['model_id'] : null;
        $yearId  = !empty($f['car_year_id']) ? (int) $f['car_year_id'] : null;
        $colorId = !empty($f['color_id']) ? (int) $f['color_id'] : null;

        return [
            'partner_id'    => !empty($f['partner_id']) ? (int) $f['partner_id'] : null,
            'bien_so'       => $bienSo,
            'bien_so_chuan' => chuan_hoa_bien_so($bienSo),
            // '' KHÔNG phải một số khung: để rỗng thì lưu NULL, không thì cột
            // duy nhất coi hai xe cùng '' là trùng nhau.
            'so_khung'      => $vin !== '' ? $vin : null,
            'so_may'        => !empty($f['so_may']) ? strtoupper(trim($f['so_may'])) : null,
            'brand_id'      => $brandId,
            'model_id'      => $modelId,
            'car_year_id'   => $yearId,
            'color_id'      => $colorId,
            /* Cột chữ gõ tay cũ (hang_xe, model_xe, nam_sx, phien_ban, mau_xe)
               không còn nhận gì từ form: xoá trắng khi lưu để không còn hai
               nguồn tên cho cùng một thứ. Xe cũ ghi bằng chữ đã được migration
               000081 dồn vào danh mục. */
            'hang_xe'       => null,
            'model_xe'      => null,
            'nam_sx'        => null,
            'phien_ban'     => null,
            'mau_xe'        => null,
            'so_km'         => isset($f['so_km']) && preg_replace('/[^\d]/', '', (string) $f['so_km']) !== ''
                               ? (int) preg_replace('/[^\d]/', '', (string) $f['so_km']) : null,
            'ghi_chu'       => !empty($f['ghi_chu']) ? trim($f['ghi_chu']) : null,
            'status'        => !empty($f['status']) ? 1 : 0,
        ];
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

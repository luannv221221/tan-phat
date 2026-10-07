<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * ĐẶT HÀNG KHO TỔNG — màn của GARA, không phải của kho tổng.
 *
 * Gara chọn hàng + số lượng, bấm Đặt. Một lần đặt sinh ĐÚNG HAI phiếu nháp:
 * phiếu xuất ở kho tổng và phiếu nhập ở gara, nối với nhau qua ô Lý do.
 * Không tự ghi sổ — kho tổng ghi sổ phiếu xuất chính là bước duyệt.
 *
 * Toàn bộ nghiệp vụ nằm ở DatHangNoiBoModel; màn này chỉ nhận form và báo kết
 * quả. Đặt ở model vì nó phải làm việc với HAI gara trong một giao dịch, và
 * như vậy mới thử được bằng dòng lệnh, không phải qua trình duyệt.
 */
class Dathangkhotong extends Controller {

    private $routeBase = 'dat-hang-kho-tong';
    private $__data = [];
    private $__dat, $__part, $__stock, $__kho, $__request, $__response;

    function __construct(){
        $this->__dat      = $this->model('DatHangNoiBoModel');
        $this->__part     = $this->model('PartsModel');
        $this->__stock    = $this->model('StocksModel');
        $this->__kho      = $this->model('WarehousesModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    public function index(){
        $c = &$this->__data['content'];
        $c['page_name'] = 'Đặt hàng kho tổng';
        $c['routeBase'] = $this->routeBase;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
        $c['laKhoTong'] = la_gara_tong();
        $c['hang']      = [];

        if (!$c['laKhoTong']){
            /* Danh mục KHO TỔNG kèm tồn đang có bên đó. Hỏi tồn trong ngữ cảnh
               gara tổng: StocksModel từ chối mọi kho không thuộc gara làm việc,
               hỏi từ phía gara sẽ ra 0 hết. */
            $c['hang'] = $this->hangKhoTong();
        }

        $this->__data['sub_content'] = 'admin/dat-hang-kho-tong/index';
        $this->__data['page_title']  = $c['page_name'];
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postDat(){
        if (!route('admin/' . $this->routeBase . '/add')){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $f  = $this->__request->getFields();
        $sl = isset($f['sl']) && is_array($f['sl']) ? $f['sl'] : [];

        $dong = [];
        foreach ($sl as $partId => $soLuong){
            $soLuong = (float) str_replace(',', '.', preg_replace('/[^\d.,]/', '', (string) $soLuong));
            if ($soLuong <= 0) continue;
            $dong[] = ['part_id' => (int) $partId, 'quantity' => $soLuong];
        }

        try {
            $kq = $this->__dat->dat($dong, isset($f['ghi_chu']) ? trim((string) $f['ghi_chu']) : '');
            Session::flash('msg',
                'Đã đặt hàng. Kho tổng nhận phiếu xuất ' . $kq['pxk']['no']
                . '; phiếu nhập của gara là ' . $kq['pnk']['no']
                . ' — chờ kho tổng ghi sổ, hàng về tới nơi thì gara ghi sổ phiếu nhập.');
        } catch (\Throwable $e){
            Session::flash('msgError', $e->getMessage());
        }

        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    /** Mặt hàng kho tổng + tồn hiện có bên kho tổng */
    private function hangKhoTong(){
        $tong = $this->model('GaragesModel')->getMaster();
        if (empty($tong['id'])) return [];
        $idTong = (int) $tong['id'];

        $cu = \App\core\Model::garaLoc();
        \App\core\Model::epGara($idTong);
        try {
            $kho  = $this->__kho->getDefault();
            $khoId = !empty($kho['id']) ? (int) $kho['id'] : 0;
            $hang = $this->__part->theoNguon(PartsModel::NGUON_TONG, 0, true);
            $ton  = [];
            if ($khoId > 0){
                foreach ($hang as $h) $ton[(int) $h['id']] = $this->__stock->available($khoId, (int) $h['id']);
            }
        } finally {
            \App\core\Model::epGara($cu);
        }

        foreach ($hang as $i => $h){
            $hang[$i]['ton'] = isset($ton[(int) $h['id']]) ? (float) $ton[(int) $h['id']] : 0.0;
        }
        return $hang;
    }
}

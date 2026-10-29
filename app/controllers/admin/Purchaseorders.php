<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * ĐƠN MUA — tài liệu khách gửi 29/10/2026.
 *
 * "Giống phiếu nhập kho nhưng thêm trường serial (đơn mua, nhà cung cấp, sản
 * phẩm, số lượng), dựa vào số lượng sẽ cho phép thêm số serial tương ứng. Từ
 * phiếu mua hàng sẽ chuyển thành phiếu nhập kho (như vậy có 2 cách nhập kho đi
 * từ phiếu mua và cách thức hiện đang dùng)."
 *
 * VÒNG ĐỜI:
 *     Nháp  ──[Chốt]──>  Đã chốt  ──[Tạo phiếu nhập kho]──>  có phiếu nhập
 *
 * CHỈ CHỐT RỒI MỚI TẠO ĐƯỢC PHIẾU NHẬP, và chỉ tạo ĐÚNG MỘT LẦN: cột
 * `goods_receipt_id` vừa là dấu "đã nhập" vừa là đường lần sang phiếu nhập.
 * Không chốt chặn thì bấm hai lần là hai phiếu nhập, tồn kho cộng đôi.
 *
 * PHIẾU NHẬP SINH RA Ở TRẠNG THÁI NHÁP, không tự ghi sổ — cùng lý do với Đặt
 * hàng kho tổng: ghi sổ là lúc tồn thật sự đổi, phải do người ở kho bấm khi
 * hàng về tới nơi, không phải hệ quả ngầm của việc khai đơn mua.
 *
 * GIÁ: đơn mua không có giá (tài liệu: "không cần giá nhập"). Lúc tạo phiếu
 * nhập thì lấy GIÁ NHẬP GẦN NHẤT của từng mặt hàng
 * (PurchaseOrdersModel::giaNhapGanNhat) rồi người dùng sửa lại trên phiếu nhập
 * trước khi ghi sổ. Để 0 thì hàng này có giá vốn 0, bán ra là báo cáo lãi gộp
 * thổi lên bằng đúng doanh thu.
 */
class Purchaseorders extends Controller {

    private $__data = [];
    private $__model, $__item, $__serial, $__partner, $__part, $__warehouse, $__receipt, $__receiptItem;
    private $__request, $__response;

    private $routeBase = 'purchase-orders';
    private $labelOne  = 'đơn mua';
    private $labelMany = 'Đơn mua';
    private $viewDir   = 'admin/purchase-orders';

    function __construct(){
        $this->__model       = $this->model('PurchaseOrdersModel');
        $this->__item        = $this->model('PurchaseOrderItemsModel');
        $this->__serial      = $this->model('PurchaseSerialsModel');
        $this->__partner     = $this->model('PartnersModel');
        $this->__part        = $this->model('PartsModel');
        $this->__warehouse   = $this->model('WarehousesModel');
        $this->__receipt     = $this->model('GoodsReceiptsModel');
        $this->__receiptItem = $this->model('GoodsReceiptItemsModel');
        $this->__request     = new Request();
        $this->__response    = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['labelOne']  = $this->labelOne;
        $c['trangThai'] = PurchaseOrdersModel::$trangThai;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
        $c['errors']    = Session::flash('errors');
        $c['old']       = Session::flash('old');
    }

    /** Dữ liệu dùng chung cho form thêm / sửa */
    private function formData(){
        $c = &$this->__data['content'];
        $c['suppliers'] = $this->__partner->getActiveTheoLoai('supplier');
        $c['warehouses'] = $this->__warehouse->getActive();
        /* Chỉ hàng hoá, KHÔNG có dịch vụ: mua của nhà cung cấp là mua vật tư,
           còn "công thợ thay dầu" thì không ai đi mua rồi nhập kho. */
        $c['parts'] = $this->__part->theoNguon(PartsModel::NGUON_GARA, (int) gara_hien_tai_id(), true);
        if (empty($c['parts'])) $c['parts'] = $this->__part->theoNguon(PartsModel::NGUON_TONG, 0, true);
    }

    public function index(){
        $f = $this->__request->getFields();

        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = $this->labelMany;
        $c['loc']       = ['status' => isset($f['status']) ? $f['status'] : '',
                           'tu' => isset($f['tu']) ? $f['tu'] : '',
                           'den' => isset($f['den']) ? $f['den'] : ''];
        $c['dataList']  = $this->__model->getLists($c['loc']);

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Lập ' . $this->labelOne;
        $c['nextNo']    = $this->__model->nextNo();
        $c['item']      = null;
        $c['lines']     = [];
        $c['serials']   = [];

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $f   = $this->__request->getFields();
        $loi = $this->kiemTra($f);
        if (!empty($loi)){ $this->flash($loi, 'add'); return; }

        $hd = $this->taiHoaDon($loi);
        if ($hd === null){ $this->flash($loi, 'add'); return; }

        $id = $this->__model->add([
            'po_no'        => $this->__model->nextNo(),
            'partner_id'   => (int) $f['partner_id'],
            'partner_name' => $this->tenNcc((int) $f['partner_id']),
            'order_date'   => $f['order_date'],
            'invoice_no'   => $this->oChu($f, 'invoice_no', 100),
            'invoice_url'  => $this->oChu($f, 'invoice_url', 500),
            'invoice_file' => $hd,
            'note'         => $this->oChu($f, 'note', 255),
            'status'       => 0,
            'created_by'   => nguoi_dang_nhap_id(),
        ]);

        $tuChoi = $this->ghiDongVaSerial($id, $f);
        $this->bao('Đã lập ' . $this->labelOne . ' ' . $this->__model->getDetail($id)['po_no'], $tuChoi);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Đơn mua ' . $item['po_no'];

        $this->baseData();
        $this->formData();
        $c = &$this->__data['content'];
        $c['page_name'] = 'Đơn mua ' . $item['po_no'];
        $c['item']      = $item;
        $c['lines']     = $this->__item->getByPo($id);
        $c['serials']   = $this->__serial->getByPo($id);

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;
        if (!$this->suaDuoc($item)) return;

        $f   = $this->__request->getFields();
        $loi = $this->kiemTra($f);
        if (!empty($loi)){ $this->flash($loi, 'edit/' . (int) $id); return; }

        $hd = $this->taiHoaDon($loi);
        if ($hd === null){ $this->flash($loi, 'edit/' . (int) $id); return; }

        $data = [
            'partner_id'   => (int) $f['partner_id'],
            'partner_name' => $this->tenNcc((int) $f['partner_id']),
            'order_date'   => $f['order_date'],
            'invoice_no'   => $this->oChu($f, 'invoice_no', 100),
            'invoice_url'  => $this->oChu($f, 'invoice_url', 500),
            'note'         => $this->oChu($f, 'note', 255),
        ];
        /* Chỉ ghi đè bản chụp hoá đơn khi THỰC SỰ có file mới — ghi đè vô điều
           kiện là mỗi lần sửa ghi chú lại xoá mất bản chụp đã tải lên. */
        if ($hd !== '') $data['invoice_file'] = $hd;

        $this->__model->edit($data, $id);
        $tuChoi = $this->ghiDongVaSerial($id, $f);
        $this->bao('Đã lưu đơn mua ' . $item['po_no'], $tuChoi);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    /** Chốt đơn — chốt rồi mới tạo được phiếu nhập kho */
    public function chot($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        if ($this->__item->demTheoPo($id) === 0){
            Session::flash('msgError', 'Đơn chưa có dòng hàng nào — chốt một đơn trống thì không nhập kho được gì.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $this->__model->edit(['status' => 1], $id);
        Session::flash('msg', 'Đã chốt đơn mua ' . $item['po_no'] . ' — giờ tạo được phiếu nhập kho.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    /** Mở lại đơn đã chốt để sửa — chưa tạo phiếu nhập thì còn mở lại được */
    public function moLai($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        if (!empty($item['goods_receipt_id'])){
            Session::flash('msgError', 'Đơn đã sinh phiếu nhập kho ' . $item['pnk_no']
                . ' nên không mở lại được. Muốn sửa thì xử lý ở phiếu nhập đó.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $this->__model->edit(['status' => 0], $id);
        Session::flash('msg', 'Đã mở lại đơn mua ' . $item['po_no']);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id);
    }

    /**
     * TẠO PHIẾU NHẬP KHO TỪ ĐƠN MUA — đường nhập kho thứ hai của hệ thống.
     *
     * Ba chốt, mỗi chốt chặn một kiểu hỏng:
     *   - phải CHỐT đơn trước: đơn còn nháp là còn sửa, nhập kho theo một bản
     *     chưa chốt thì sửa tiếp là lệch giữa hai chứng từ
     *   - chỉ tạo MỘT LẦN: `goods_receipt_id` còn trống mới cho tạo, không thì
     *     bấm hai lần là tồn kho cộng đôi
     *   - phải CHỌN KHO: phiếu nhập không có kho thì không biết hàng vào đâu
     *
     * Cả việc tạo phiếu + gắn ngược id vào đơn nằm trong MỘT giao dịch: tạo
     * được phiếu mà không gắn được id là lần sau bấm lại ra phiếu thứ hai.
     */
    public function taoPhieuNhap($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        if ((int) $item['status'] !== 1){
            Session::flash('msgError', 'Phải chốt đơn mua trước khi tạo phiếu nhập kho.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }
        if (!empty($item['goods_receipt_id'])){
            Session::flash('msgError', 'Đơn này đã sinh phiếu nhập kho ' . $item['pnk_no'] . ' rồi.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $f    = $this->__request->getFields();
        $kho  = isset($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        $dsKho = $this->__warehouse->getActive();
        $hopLe = false;
        foreach ($dsKho as $k){ if ((int) $k['id'] === $kho) $hopLe = true; }
        if (!$hopLe){
            Session::flash('msgError', 'Chọn kho nhận hàng trước đã.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $dong = $this->__item->getByPo($id);
        if (empty($dong)){
            Session::flash('msgError', 'Đơn không có dòng hàng nào.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $id); return;
        }

        $pnkId = 0;
        $pnkNo = '';
        $this->__model->transaction(function() use (&$pnkId, &$pnkNo, $item, $dong, $kho, $id){
            $pnkNo = $this->__receipt->nextNo();
            $tong  = 0.0;
            $rows  = [];
            foreach ($dong as $d){
                $gia = $this->__model->giaNhapGanNhat((int) $d['part_id']);
                $tt  = round($gia * (float) $d['quantity'], 2);
                $tong += $tt;
                $rows[] = ['part_id' => (int) $d['part_id'], 'quantity' => (float) $d['quantity'],
                           'unit_cost' => $gia, 'amount' => $tt,
                           'note' => 'Đơn mua ' . $item['po_no']];
            }

            $pnkId = (int) $this->__receipt->add([
                'receipt_no'   => $pnkNo,
                'receipt_type' => 'nhap_mua',
                'warehouse_id' => $kho,
                'partner_id'   => !empty($item['partner_id']) ? (int) $item['partner_id'] : null,
                'partner_name' => $item['partner_name'],
                'receipt_date' => date('Y-m-d'),
                'reason'       => 'Theo đơn mua ' . $item['po_no']
                                . (!empty($item['invoice_no']) ? ' — HĐ NCC ' . $item['invoice_no'] : ''),
                'total_amount' => $tong,
                /* NHÁP. Ghi sổ là lúc tồn thật sự đổi — người ở kho bấm khi
                   hàng về tới nơi, và đó cũng là lúc soát lại giá nhập. */
                'status'       => 0,
                'created_by'   => nguoi_dang_nhap_id(),
            ]);
            $this->__receiptItem->syncForReceipt($pnkId, $rows);

            $this->__model->edit(['goods_receipt_id' => $pnkId], $id);
        });

        Session::flash('msg', 'Đã tạo phiếu nhập kho ' . $pnkNo . ' từ đơn mua ' . $item['po_no']
            . '. Giá nhập lấy theo lần nhập gần nhất — soát lại rồi ghi sổ ở màn Phiếu nhập kho.');
        $this->__response->redirect('admin/goods-receipts/edit/' . $pnkId);
    }

    public function delete($id){
        $item = $this->layCuaToi($id);
        if (empty($item)) return;

        if (!empty($item['goods_receipt_id'])){
            Session::flash('msgError', 'Không xoá được: đơn đã sinh phiếu nhập kho ' . $item['pnk_no']
                . '. Xoá đơn thì phiếu nhập mất đường lần ngược về nguồn mua và serial.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__model->remove($id);
        Session::flash('msg', 'Đã xoá đơn mua ' . $item['po_no']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    /**
     * Ghi dòng hàng rồi ghi serial — PHẢI THEO THỨ TỰ NÀY.
     *
     * syncForPo() của dòng hàng XOÁ RỒI GHI LẠI, mà `purchase_serials`.`poi_id`
     * có khoá ngoại CASCADE — nên xoá dòng là serial bay theo. Ghi serial
     * trước là ghi vào khoảng không.
     *
     * Serial gắn theo THỨ TỰ DÒNG trên form, không theo poi_id cũ: id mới sinh
     * sau mỗi lần lưu nên không giữ được, còn thứ tự thì form giữ nguyên.
     */
    private function ghiDongVaSerial($id, array $f){
        $rows = [];
        $parts = isset($f['part']) && is_array($f['part']) ? $f['part'] : [];
        foreach ($parts as $i => $p){
            $rows[] = ['part_id'  => (int) $p,
                       'quantity' => isset($f['qty'][$i]) ? (float) preg_replace('/[^\d.]/', '', (string) $f['qty'][$i]) : 0,
                       'note'     => isset($f['line_note'][$i]) ? trim((string) $f['line_note'][$i]) : ''];
        }
        $daGhi = $this->__item->syncForPo($id, $rows);

        /* Khớp lại poi_id mới với thứ tự dòng trên form. syncForPo bỏ qua dòng
           trống nên hai danh sách không cùng chỉ số — phải đi song song. */
        $poiTheoThuTu = array_keys($daGhi);
        $viTri = 0;
        $banDoDong = [];
        foreach ($parts as $i => $p){
            $q = isset($f['qty'][$i]) ? (float) preg_replace('/[^\d.]/', '', (string) $f['qty'][$i]) : 0;
            if ((int) $p <= 0 || $q <= 0) continue;
            if (isset($poiTheoThuTu[$viTri])) $banDoDong[$i] = $poiTheoThuTu[$viTri];
            $viTri++;
        }

        $sn = [];
        $dsSerial = isset($f['serial']) && is_array($f['serial']) ? $f['serial'] : [];
        foreach ($dsSerial as $i => $nhom){
            if (!is_array($nhom) || !isset($banDoDong[$i])) continue;
            foreach ($nhom as $s){
                $sn[] = ['poi_id'  => $banDoDong[$i],
                         'part_id' => (int) $parts[$i],
                         'serial'  => (string) $s];
            }
        }
        return $this->__serial->syncForPo($id, $sn);
    }

    private function bao($thanhCong, array $tuChoi){
        Session::flash('msg', $thanhCong);
        if (!empty($tuChoi)){
            $mo = [];
            foreach ($tuChoi as $t) $mo[] = $t['serial'] . ' (' . $t['ly_do'] . ')';
            Session::flash('msgError', 'Có ' . count($tuChoi) . ' serial không lưu được: '
                . implode('; ', $mo) . '. Những serial còn lại đã lưu bình thường.');
        }
    }

    private function kiemTra(array $f){
        $loi = [];
        if (empty($f['partner_id'])) $loi['partner_id'] = 'Chọn nhà cung cấp';
        if (empty($f['order_date'])) $loi['order_date'] = 'Chọn ngày mua';
        if (!empty($f['invoice_url']) && !preg_match('~^https?://~i', trim($f['invoice_url']))){
            $loi['invoice_url'] = 'Link hoá đơn phải bắt đầu bằng http:// hoặc https://';
        }
        return $loi;
    }

    /** '' = không có file mới, null = lỗi (đã ghi vào $loi), else = đường dẫn */
    private function taiHoaDon(&$loi){
        $up = upload_chung_tu('invoice_file', 'hoa-don-ncc', 'hd');
        if ($up['status'] === 'error'){ $loi['invoice_file'] = $up['message']; return null; }
        return $up['status'] === 'ok' ? $up['path'] : '';
    }

    private function oChu(array $f, $k, $max){
        return isset($f[$k]) && trim($f[$k]) !== '' ? mb_substr(trim($f[$k]), 0, $max) : null;
    }

    /** Tên NCC chốt tại thời điểm lập — đổi tên NCC không làm đổi đơn cũ */
    private function tenNcc($id){
        $p = $this->__partner->getDetail((int) $id);
        return !empty($p['name']) ? $p['name'] : null;
    }

    private function layCuaToi($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase);
            return null;
        }
        return $item;
    }

    /** Đơn đã chốt / đã nhập kho thì không sửa nội dung được nữa */
    private function suaDuoc($item){
        if ((int) $item['status'] === 1 || !empty($item['goods_receipt_id'])){
            Session::flash('msgError', 'Đơn đã chốt — mở lại rồi mới sửa được.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . (int) $item['id']);
            return false;
        }
        return true;
    }

    private function flash($loi, $back){
        Session::flash('errors', $loi);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msgError', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

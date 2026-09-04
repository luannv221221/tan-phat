<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * BÁN HÀNG — Hoá đơn bán. GHI SỔ khép vòng doanh thu + công nợ + giá vốn + tồn:
 *   Nợ 131 / Có 511  (doanh thu chưa thuế)
 *   Nợ 131 / Có 3331 (thuế GTGT, nếu có)
 *   Nợ 632 / Có 156  (giá vốn — bình quân gia quyền, tính lúc ghi sổ)
 * và trừ tồn kho. Công nợ khách tự lên qua TK 131 (admin/debt).
 */
class Salesinvoices extends Controller {

    const RECEIVABLE = '131';
    const REVENUE    = '511';
    const TAX        = '3331';
    const COGS       = '632';
    const INVENTORY  = '156';
    const DOC_TYPE   = 'sale_invoice';

    private $__data = [];
    private $__model, $__itemModel, $__stock, $__warehouse, $__partner, $__part;
    private $__settings, $__request, $__response;

    private $routeBase = 'sales-invoices';
    private $labelOne  = 'hoá đơn';
    private $labelMany = 'Hoá đơn bán';
    private $viewDir   = 'admin/sales-invoices';

    function __construct(){
        $this->__model        = $this->model('SalesInvoicesModel');
        $this->__itemModel    = $this->model('SalesInvoiceItemsModel');
        $this->__stock        = $this->model('StocksModel');
        $this->__warehouse    = $this->model('WarehousesModel');
        $this->__partner      = $this->model('PartnersModel');
        $this->__part         = $this->model('PartsModel');
        $this->__settings     = $this->model('SettingsModel');
        $this->__request      = new Request();
        $this->__response     = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    private function formData(){
        $this->__data['content']['warehouses'] = $this->__warehouse->getActive();
        $this->__data['content']['partners']   = $this->__partner->getActive();
        $this->__data['content']['parts']      = $this->__part->getForSelect();
        $this->__data['content']['partnerDiscounts'] = $this->__partner->groupDiscountMap();
    }

    /* ==================================================================
     * CHÉP DÒNG HÀNG TỪ CHỨNG TỪ CŨ
     *
     * Khách quay lại làm đúng gói bảo dưỡng tháng trước, hoặc khách đã có báo
     * giá và giờ chốt đơn. Gõ lại từng dòng là việc thừa.
     *
     * CHÉP ĐƯỢC TỪ HAI NGUỒN:
     *   hoadon  — hoá đơn bán cũ (lặp lại đơn cũ)
     *   baogia  — báo giá (chốt đơn từ báo giá đã gửi khách)
     *
     * Khác bản ở màn Báo giá ở MỘT ĐIỂM QUAN TRỌNG: hoá đơn trừ tồn kho, nên
     * kết quả trả về kèm TỒN HIỆN CÓ của từng mặt hàng ở kho đang chọn. Chép
     * xong mới phát hiện thiếu hàng lúc ghi sổ là quá muộn.
     *
     * Hai endpoint trả JSON, form gọi bằng fetch nên không phải tải lại trang
     * và không mất những gì đang gõ dở ở phần đầu phiếu.
     * ================================================================== */

    /** Nguồn hợp lệ -> [nhãn, model chứng từ, model dòng, cột số hiệu] */
    private function nguonChep($tu){
        if ($tu === 'baogia'){
            return ['nhan' => 'báo giá', 'cot_so' => 'quote_no',
                    'ct' => $this->model('QuotationsModel'),
                    'dong' => $this->model('QuotationItemsModel')];
        }
        return ['nhan' => 'hoá đơn', 'cot_so' => 'invoice_no',
                'ct' => $this->__model, 'dong' => $this->__itemModel];
    }

    /** Danh sách chứng từ để chọn. ?tu=hoadon|baogia & ?customer_id= */
    public function copyList(){
        if (!route('admin/' . $this->routeBase . '/add')){ $this->jsonLoi('Không có quyền', 403); return; }

        $f  = $this->__request->getFields();
        $tu = (isset($f['tu']) && $f['tu'] === 'baogia') ? 'baogia' : 'hoadon';
        $kh = !empty($f['customer_id']) ? (int) $f['customer_id'] : 0;
        $n  = $this->nguonChep($tu);

        $ra = [];
        foreach ($n['ct']->danhSachDeChep($kh, 50) ?: [] as $ct){
            $ngay = $tu === 'baogia' ? $ct['quote_date'] : $ct['invoice_date'];
            $ra[] = [
                'id'       => (int) $ct['id'],
                'quote_no' => $ct[$n['cot_so']],
                'ngay'     => !empty($ngay) ? date('d/m/Y', strtotime($ngay)) : '',
                'khach'    => $ct['khach'] !== '' ? $ct['khach'] : '—',
                'so_dong'  => (int) $ct['so_dong'],
                'tong'     => (float) $ct['total_amount'],
                'cua_khach_nay' => ((int) $ct['uu_tien'] === 0 && $kh > 0),
            ];
        }
        $this->json(['tu' => $tu, 'items' => $ra]);
    }

    /** Dòng hàng của một chứng từ, tách sẵn theo tab Hàng hoá / Dịch vụ. */
    public function copyLines($id){
        if (!route('admin/' . $this->routeBase . '/add')){ $this->jsonLoi('Không có quyền', 403); return; }

        $f  = $this->__request->getFields();
        $tu = (isset($f['tu']) && $f['tu'] === 'baogia') ? 'baogia' : 'hoadon';
        $n  = $this->nguonChep($tu);

        $ct = $n['ct']->getDetail($id);
        if (empty($ct)){ $this->jsonLoi('Không tìm thấy ' . $n['nhan'], 404); return; }

        $dong    = $n['dong']->dongDeChep($id);
        $tongGoc = $n['dong']->demDong($id);

        /* Tồn ở KHO ĐANG CHỌN trên form, không phải kho mặc định: người dùng có
           thể đã đổi kho trước khi bấm Chép. Không truyền kho thì bỏ qua phần
           tồn thay vì báo một con số của kho khác — sai còn tệ hơn không có. */
        $khoId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        $ton   = $khoId > 0
            ? $this->__stock->tonTheoNhieuHang($khoId, array_map(function($d){ return $d['part_id']; }, $dong ?: []))
            : [];

        $hang = $dichvu = [];
        $boQuaNgungBan = 0;
        $thieuHang     = 0;

        foreach ($dong ?: [] as $d){
            if ((int) $d['con_ban'] !== 1){ $boQuaNgungBan++; continue; }

            $pid = (int) $d['part_id'];
            $row = [
                'part_id'  => $pid,
                'qty'      => rtrim(rtrim(number_format((float) $d['quantity'], 3, '.', ''), '0'), '.'),
                'gia_cu'   => (int) $d['unit_price'],
                'gia_moi'  => (int) $d['gia_bay_gio'],
                'disc'     => (float) $d['discount_percent'],
                'note'     => (string) $d['note'],
                'ten'      => $d['part_code'] . ' - ' . $d['part_name'],
            ];

            if ($d['item_type'] === PartsModel::LOAI_DICH_VU){
                // Dịch vụ không trừ kho nên không có khái niệm thiếu hàng.
                $dichvu[] = $row;
            } else {
                if ($khoId > 0){
                    $co = isset($ton[$pid]) ? $ton[$pid] : 0.0;
                    $row['ton'] = $co;
                    $row['thieu'] = $co < (float) $d['quantity'];
                    if ($row['thieu']) $thieuHang++;
                }
                $hang[] = $row;
            }
        }

        $daXoa = max(0, $tongGoc - count($dong ?: []));

        $this->json([
            'tu'          => $tu,
            'quote_no'    => $ct[$n['cot_so']],
            'customer_id' => (int) $ct['customer_id'],
            'vat_rate'    => (float) $ct['vat_rate'],
            'hang'        => $hang,
            'dichvu'      => $dichvu,
            'bo_qua'      => ['da_xoa' => $daXoa, 'ngung_ban' => $boQuaNgungBan, 'thieu_hang' => $thieuHang],
        ]);
    }

    private function json(array $data, $code = 200){
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function jsonLoi($msg, $code){
        $this->json(['error' => $msg], $code);
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f      = $this->__request->getFields();
        $status = isset($f['status']) ? trim($f['status']) : '';
        $from   = isset($f['from']) ? trim($f['from']) : '';
        $to     = isset($f['to'])   ? trim($f['to'])   : '';

        $this->__data['content']['page_name']    = $this->labelMany;
        $this->__data['content']['dataList']     = $this->__model->getLists($status, $from, $to);
        $this->__data['content']['filterStatus'] = $status;
        $this->__data['content']['filterFrom']   = $from;
        $this->__data['content']['filterTo']     = $to;
        $this->__data['content']['msg']          = Session::flash('msg');
        $this->__data['content']['msgError']     = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Lập ' . $this->labelOne;
        $this->__data['content']['today']     = date('Y-m-d');
        $this->__data['content']['items']     = [];
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $f     = $this->__request->getFields();
        $lines = $this->buildLines();

        $id = $this->__model->add(array_merge($this->headerData($f), [
            'invoice_no' => $this->__model->nextNo(),
            'status'     => 0,
            'created_by' => Session::get('dataUser'),
        ]));

        $this->syncTotals($id, $lines, $f);

        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' (nháp). Kiểm tra rồi bấm "Ghi sổ".');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Hoá đơn ' . $item['invoice_no'];

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Hoá đơn ' . $item['invoice_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__itemModel->getByInvoice($id);
        $this->__data['content']['voucher']   = null;
        $this->__data['content']['eiDefaults'] = [
            'serial' => $this->__settings->val('einvoice_serial', 'K' . date('y') . 'TTP'),
            'form'   => $this->__settings->val('einvoice_form', '1'),
            'nextNo' => $this->__model->nextEinvoiceNo(),
        ];
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if ((int) $item['status'] === 1){
            Session::flash('msgError', 'Hoá đơn đã ghi sổ — huỷ ghi sổ trước khi sửa.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $f     = $this->__request->getFields();
        $lines = $this->buildLines();

        $this->__model->edit($this->headerData($f), $id);
        $this->syncTotals($id, $lines, $f);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** GHI SỔ: giá vốn + trừ tồn + doanh thu/thuế/giá vốn (KT-6) */
    public function post($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        if ((int) $item['status'] === 1){
            Session::flash('msgError', 'Hoá đơn đã ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $items = $this->__itemModel->getByInvoice($id);
        if (empty($items)){
            Session::flash('msgError', 'Hoá đơn chưa có dòng hàng, không thể ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $wh = (int) $item['warehouse_id'];

        /* DỊCH VỤ không đi qua kho: "thay dầu" tồn luôn bằng 0, kiểm tồn nó là
           chặn mọi hoá đơn dịch vụ. Tách ra ngay từ đây rồi dùng chung $loai
           cho cả bước kiểm lẫn bước ghi sổ, để hai bước không lệch nhau. */
        $loai = $this->__part->loaiTheoId(array_column($items, 'part_id'));

        // Hoá đơn để trống kho nhưng lại có hàng thật -> không biết trừ ở đâu.
        // Xảy ra khi hoá đơn lập lúc chỉ có dịch vụ, sau đó thêm dòng hàng vào.
        $canKho = false;
        foreach ($items as $it){
            if (PartsModel::coKho($loai[(int) $it['part_id']] ?? PartsModel::LOAI_PHU_TUNG)){ $canKho = true; break; }
        }
        if ($canKho && $wh <= 0){
            Session::flash('msgError', 'Hoá đơn có hàng hoá nên phải chọn kho xuất trước khi ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        // Chặn nếu tồn không đủ (gộp cùng hàng hoá) — chỉ với hàng CÓ kho
        $need = [];
        foreach ($items as $it){
            $pid = (int) $it['part_id'];
            if (!PartsModel::coKho($loai[$pid] ?? PartsModel::LOAI_PHU_TUNG)) continue;
            $need[$pid] = ($need[$pid] ?? 0) + (float) $it['quantity'];
        }
        $short = [];
        foreach ($need as $partId => $qty){
            if ($this->__stock->available($wh, $partId) + 1e-9 < $qty){
                $p = $this->__part->getDetail($partId);
                $short[] = ($p ? $p['code'] . ' - ' . $p['name'] : ('#' . $partId))
                    . ' (tồn ' . $this->fmtQty($this->__stock->available($wh, $partId))
                    . ', cần ' . $this->fmtQty($qty) . ')';
            }
        }
        if (!empty($short)){
            Session::flash('msgError', 'Tồn không đủ để xuất bán: ' . implode('; ', $short));
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $date = $item['invoice_date'];
        $no   = $item['invoice_no'];
        $rate = (float) $item['vat_rate'];

        // Chặn ghi sổ lùi ngày — báo trước cho tử tế, engine cũng chặn lần nữa.
        $lui = $this->__stock->kiemLuiNgay($wh, array_column($items, 'part_id'), $date, $this->__part);
        if (!empty($lui)){
            Session::flash('msgError', 'Hoá đơn đề ngày cũ hơn phát sinh đã có, sẽ làm sai tồn đầu kỳ của báo cáo: ' . implode('; ', $lui));
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $this->__model->transaction(function($db) use ($id, $item, $items, $wh, $date, $no, $rate, $loai){
            $subtotal = 0.0; $cost = 0.0;
            foreach ($items as $it){
                $pid = (int) $it['part_id'];

                // Dịch vụ: không trừ kho, không sinh thẻ kho. Giá vốn = 0 —
                // công thợ chưa được theo dõi ở đâu cả, ghi bừa một con số vào
                // đây là làm sai lãi gộp. Doanh thu vẫn ghi nhận đầy đủ.
                if (!PartsModel::coKho($loai[$pid] ?? PartsModel::LOAI_PHU_TUNG)){
                    $this->__itemModel->setCost((int) $it['id'], 0, 0);
                    $subtotal += (float) $it['amount'];
                    continue;
                }

                $avg = $this->__stock->applyOut($wh, $pid, (float) $it['quantity'],
                    self::DOC_TYPE, $id, $no, $date, $it['note']);
                $costAmt = round((float) $it['quantity'] * $avg, 2);
                $this->__itemModel->setCost((int) $it['id'], $avg, $costAmt);
                $subtotal += (float) $it['amount'];
                $cost     += $costAmt;
            }
            $tax   = round($subtotal * $rate / 100, 2);
            $total = $subtotal + $tax;

            $this->__model->edit(['status' => 1, 'subtotal' => $subtotal, 'tax_amount' => $tax,
                'total_amount' => $total, 'cost_amount' => $cost], $id);
        });

        Session::flash('msg', 'Đã ghi sổ ' . $no . ' — trừ tồn kho & ghi nhận giá vốn.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** HUỶ GHI SỔ: hoàn tồn + xoá bút toán (chỉ khi là phát sinh cuối cùng) */
    public function unpost($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        if ((int) $item['status'] !== 1){
            Session::flash('msgError', 'Hoá đơn chưa ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $items = $this->__itemModel->getByInvoice($id);
        $wh = (int) $item['warehouse_id'];

        $blocked = [];
        foreach ($items as $it){
            if (!$this->__stock->isLastMovement($wh, (int) $it['part_id'], self::DOC_TYPE, $id)){
                $blocked[] = $it['part_code'] . ' - ' . $it['part_name'];
            }
        }
        if (!empty($blocked)){
            Session::flash('msgError', 'Không huỷ được: đã có nhập/xuất sau hoá đơn này ở — ' . implode('; ', $blocked)
                . '. Huỷ các phiếu sau trước.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $loaiHuy = $this->__part->loaiTheoId(array_column($items, 'part_id'));
        $this->__model->transaction(function($db) use ($id, $items, $wh, $loaiHuy){
            foreach ($items as $it){
                $pid = (int) $it['part_id'];

                // Dịch vụ lúc ghi sổ không sinh thẻ kho nên cũng không có gì để
                // đảo. Gọi reverseDoc cho nó là tự đẻ ra một dòng `stocks` với
                // số lượng 0 — rác trong báo cáo tồn.
                if (PartsModel::coKho($loaiHuy[$pid] ?? PartsModel::LOAI_PHU_TUNG)){
                    $this->__stock->reverseDoc($wh, $pid, self::DOC_TYPE, $id);
                }
                $this->__itemModel->setCost((int) $it['id'], 0, 0);
            }
            $this->__model->edit(['status' => 0, 'cost_amount' => 0], $id);
        });

        Session::flash('msg', 'Đã huỷ ghi sổ ' . $item['invoice_no'] . ' — hoàn tồn kho.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    // ===== HĐĐT nội bộ (ký hiệu/số HĐ + xuất XML, KHÔNG nối nhà cung cấp) =====

    /** Phát hành HĐĐT: gán ký hiệu/mẫu số/số HĐ + đánh dấu đã phát hành (chỉ khi đã ghi sổ) */
    public function issueEinvoice($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        if ((int) $item['status'] !== 1){
            Session::flash('msgError', 'Phải ghi sổ hoá đơn trước khi phát hành HĐĐT.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        if ($item['einvoice_status'] === 'issued'){
            Session::flash('msgError', 'Hoá đơn đã phát hành HĐĐT.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $f      = $this->__request->getFields();
        $serial = !empty($f['einvoice_serial']) ? trim($f['einvoice_serial']) : $this->__settings->val('einvoice_serial', 'K' . date('y') . 'TTP');
        $form   = !empty($f['einvoice_form']) ? trim($f['einvoice_form']) : $this->__settings->val('einvoice_form', '1');
        $no     = !empty($f['einvoice_no']) ? trim($f['einvoice_no']) : $this->__model->nextEinvoiceNo();

        $this->__model->edit([
            'einvoice_status'    => 'issued',
            'einvoice_serial'    => $serial,
            'einvoice_form'      => $form,
            'einvoice_no'        => $no,
            'einvoice_issued_at' => date('Y-m-d H:i:s'),
        ], $id);
        Session::flash('msg', 'Đã phát hành HĐĐT số ' . $no . ' (ký hiệu ' . $serial . ').');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Thu hồi HĐĐT (đưa về chưa phát hành) */
    public function revokeEinvoice($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $this->__model->edit([
            'einvoice_status'    => 'none',
            'einvoice_no'        => null,
            'einvoice_issued_at' => null,
        ], $id);
        Session::flash('msg', 'Đã thu hồi HĐĐT ' . $item['invoice_no'] . '.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Xuất XML HĐĐT để nộp phần mềm hoá đơn (tải file) */
    public function einvoiceXml($id){
        $item = $this->__model->getDetail($id);
        if (empty($item) || !route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if ($item['einvoice_status'] !== 'issued'){
            Session::flash('msgError', 'Hoá đơn chưa phát hành HĐĐT.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $items = $this->__itemModel->getByInvoice($id);
        $xml   = $this->buildEinvoiceXml($item, $items);

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="HDDT_' . preg_replace('/[^A-Za-z0-9_\-]/', '', $item['invoice_no']) . '.xml"');
        echo $xml;
        exit;
    }

    /** Dựng XML HĐĐT (cấu trúc TĐiệp/HĐon tham khảo TT78/NĐ123 — nộp phần mềm HĐĐT) */
    private function buildEinvoiceXml($item, $items){
        $x = function($v){ return htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8'); };
        $sellerName = $this->__settings->val('site_name', 'CÔNG TY TÂN PHÁT');
        $sellerTax  = $this->__settings->val('tax_code', '');
        $sellerAddr = $this->__settings->val('address', '');
        $sellerTel  = $this->__settings->val('hotline', '');
        $buyerName  = !empty($item['customer_name']) ? $item['customer_name'] : 'Khách lẻ';

        $lines = '';
        $stt = 0;
        foreach ($items as $it){
            $stt++;
            $disc = (float) ($it['discount_percent'] ?? 0);
            $lines .= '      <HHDVu>' . "\n"
                . '        <STT>' . $stt . '</STT>' . "\n"
                . '        <THHDVu>' . $x($it['part_name']) . '</THHDVu>' . "\n"
                . '        <MHHDVu>' . $x($it['part_code']) . '</MHHDVu>' . "\n"
                . '        <DVTinh>' . $x(!empty($it['unit_name']) ? $it['unit_name'] : '') . '</DVTinh>' . "\n"
                . '        <SLuong>' . (float) $it['quantity'] . '</SLuong>' . "\n"
                . '        <DGia>' . (float) $it['unit_price'] . '</DGia>' . "\n"
                . '        <TLCKhau>' . $disc . '</TLCKhau>' . "\n"
                . '        <ThTien>' . (float) $it['amount'] . '</ThTien>' . "\n"
                . '      </HHDVu>' . "\n";
        }

        $subtotal = (float) $item['subtotal'];
        $tax      = (float) $item['tax_amount'];
        $total    = (float) $item['total_amount'];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<HDon>' . "\n";
        $xml .= '  <DLHDon>' . "\n";
        $xml .= '    <TTChung>' . "\n";
        $xml .= '      <KHMSHDon>' . $x($item['einvoice_form']) . '</KHMSHDon>' . "\n";
        $xml .= '      <KHHDon>' . $x($item['einvoice_serial']) . '</KHHDon>' . "\n";
        $xml .= '      <SHDon>' . $x($item['einvoice_no']) . '</SHDon>' . "\n";
        $xml .= '      <NLap>' . $x($item['invoice_date']) . '</NLap>' . "\n";
        $xml .= '      <DVTTe>VND</DVTTe>' . "\n";
        $xml .= '    </TTChung>' . "\n";
        $xml .= '    <NDHDon>' . "\n";
        $xml .= '      <NBan>' . "\n";
        $xml .= '        <Ten>' . $x($sellerName) . '</Ten>' . "\n";
        $xml .= '        <MST>' . $x($sellerTax) . '</MST>' . "\n";
        $xml .= '        <DChi>' . $x($sellerAddr) . '</DChi>' . "\n";
        $xml .= '        <DThoai>' . $x($sellerTel) . '</DThoai>' . "\n";
        $xml .= '      </NBan>' . "\n";
        $xml .= '      <NMua>' . "\n";
        $xml .= '        <Ten>' . $x($buyerName) . '</Ten>' . "\n";
        $xml .= '      </NMua>' . "\n";
        $xml .= '      <DSHHDVu>' . "\n" . $lines . '      </DSHHDVu>' . "\n";
        $xml .= '      <TToan>' . "\n";
        $xml .= '        <TgTCThue>' . $subtotal . '</TgTCThue>' . "\n";
        $xml .= '        <TgTThue>' . $tax . '</TgTThue>' . "\n";
        $xml .= '        <TSuat>' . (float) $item['vat_rate'] . '</TSuat>' . "\n";
        $xml .= '        <TgTTTBSo>' . $total . '</TgTTTBSo>' . "\n";
        $xml .= '      </TToan>' . "\n";
        $xml .= '    </NDHDon>' . "\n";
        $xml .= '  </DLHDon>' . "\n";
        $xml .= '</HDon>' . "\n";
        return $xml;
    }

    /**
     * BIỂU MẪU IN — mở trên trình duyệt để in / lưu PDF, hoặc ?word=1 để tải .doc.
     * Dùng chung mẫu với báo giá: app/views/admin/print/chung-tu.php
     */
    public function inAn($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $f      = $this->__request->getFields();
        $laWord = !empty($f['word']);

        $khach = !empty($item['customer_id']) ? $this->__partner->getDetail((int) $item['customer_id']) : null;
        if (empty($khach) && !empty($item['customer_name'])) $khach = ['name' => $item['customer_name']];

        $ct = [
            'loai'         => 'HOÁ ĐƠN BÁN HÀNG',
            'so'           => $item['invoice_no'],
            'ngay'         => $item['invoice_date'],
            'hieuLuc'      => null,
            'ghiChu'       => $item['note'],
            'subtotal'     => $item['subtotal'],
            'vatRate'      => $item['vat_rate'],
            'tax'          => $item['tax_amount'],
            'total'        => $item['total_amount'],
            // Hoá đơn là chứng từ đòi tiền -> in kèm số tài khoản để khách chuyển khoản
            'hienNganHang' => true,
            'nhanKy'       => ['NGƯỜI MUA HÀNG', 'ĐẠI DIỆN BÊN BÁN'],
            /* Chỉ tự bật hộp thoại In khi tới từ nút "In / Lưu PDF" (?in=1).
               Mở thẳng /print/<id> thì chỉ xem — dán link cho người khác mà
               nó tự nhảy hộp thoại in là khó chịu, và không kịp soát lại
               chứng từ trước khi in. */
            'tuMoHopIn'    => !$laWord && !empty($f['in']),
        ];

        if ($laWord) header_word($item['invoice_no']);

        in_chung_tu($ct, $khach, $this->__itemModel->getByInvoice($id),
            $this->model('SettingsModel')->map(),
            _WEB_URL . '/admin/' . $this->routeBase . '/print/' . (int) $id . '?word=1',
            $laWord);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if ((int) $item['status'] === 1){
            Session::flash('msgError', 'Hoá đơn đã ghi sổ — huỷ ghi sổ trước khi xoá.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    /**
     * KHÔNG còn ghi customer_name và note: hai ô đó đã bỏ khỏi form (04/08/2026).
     *
     * Phải bỏ khỏi ĐÂY chứ không chỉ bỏ ô ngoài giao diện — hàm này dùng chung
     * cho cả thêm lẫn sửa, ô không còn thì $f rỗng, để nguyên là mỗi lần sửa
     * một hoá đơn cũ sẽ XOÁ TRẮNG tên khách vãng lai và ghi chú đã lưu.
     *
     * Cột trong CSDL giữ nguyên. Hoá đơn tạo từ đơn hàng web vẫn được gán
     * customer_name (xem Orders::invoice) nên chỗ đó không mất tên khách.
     */
    private function headerData($f){
        return [
            'customer_id'   => $this->customerId(),
            // NULL khi hoá đơn toàn dịch vụ — không gán bừa một kho để rồi
            // báo cáo kho nhìn như có phát sinh ở đó.
            'warehouse_id'  => !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : null,
            'invoice_date'  => $f['invoice_date'],
            'vat_rate'      => $this->parseRate(isset($f['vat_rate']) ? $f['vat_rate'] : 0),
        ];
    }

    private function syncTotals($id, $lines, $f){
        $subtotal = $this->__itemModel->syncForInvoice($id, $lines);
        $rate = $this->parseRate(isset($f['vat_rate']) ? $f['vat_rate'] : 0);
        $tax  = round($subtotal * $rate / 100, 2);
        $this->__model->edit(['subtotal' => $subtotal, 'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax], $id);
    }

    private function customerId(){
        $f = $this->__request->getFields();
        $id = !empty($f['customer_id']) ? (int) $f['customer_id'] : 0;
        if ($id <= 0) return null;
        return !empty($this->__partner->getDetail($id)) ? $id : null;
    }

    private function validateInput(){
        $f = $this->__request->getFields();
        $errors = [];
        $lines = $this->buildLines();

        /* Kho xuất chỉ BẮT BUỘC khi hoá đơn có hàng thật.
           Hoá đơn toàn dịch vụ ("thay dầu", "bảo dưỡng") không xuất gì khỏi kho
           cả — bắt chọn kho là bắt điền một thông tin vô nghĩa, và người dùng
           sẽ chọn đại một kho rồi số liệu kho nhìn như có phát sinh. */
        $whId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        if ($this->coHangCanKho($lines)){
            if ($whId <= 0 || empty($this->__warehouse->getDetail($whId))){
                $errors['warehouse_id'] = 'Hoá đơn có hàng hoá nên phải chọn kho xuất';
            }
        } elseif ($whId > 0 && empty($this->__warehouse->getDetail($whId))){
            $errors['warehouse_id'] = 'Kho không hợp lệ';
        }

        if (empty($f['invoice_date'])) $errors['invoice_date'] = 'Chọn ngày hoá đơn';
        if (empty($lines)) $errors['lines'] = 'Hoá đơn phải có ít nhất 1 dòng hàng';
        return $errors;
    }

    /** Hoá đơn có ít nhất một dòng đi qua kho (tức không phải toàn dịch vụ) */
    private function coHangCanKho($lines){
        if (empty($lines)) return false;

        $loai = $this->__part->loaiTheoId(array_column($lines, 'part_id'));
        foreach ($lines as $ln){
            if (PartsModel::coKho($loai[(int) $ln['part_id']] ?? PartsModel::LOAI_PHU_TUNG)) return true;
        }
        return false;
    }

    /**
     * Dòng hoá đơn gộp từ CẢ HAI tab: Hàng hoá (line_*) và Dịch vụ (sv_*).
     *
     * Xuống CSDL vẫn là `sales_invoice_items` chung một bảng, phân biệt nhau
     * bằng `parts.item_type` — nên bước ghi sổ (trừ kho, tính giá vốn) không
     * phải biết gì về tab.
     *
     * Tên ô của hai bảng phải KHÁC nhau (line_ / sv_): dùng chung một tên thì
     * thứ tự phần tử phụ thuộc thứ tự DOM, đổi chỗ tab một cái là số lượng
     * nhảy sang mặt hàng khác mà chẳng có lỗi nào báo.
     */
    private function buildLines(){
        return array_merge($this->docDong('line_'), $this->docDong('sv_'));
    }

    /** Đọc một bảng dòng hàng theo tiền tố tên ô ('line_' hoặc 'sv_') */
    private function docDong($tienTo){
        $f   = $this->__request->getFields();
        $lay = function($ten) use ($f, $tienTo){
            $k = $tienTo . $ten;
            return isset($f[$k]) && is_array($f[$k]) ? $f[$k] : [];
        };

        $parts  = $lay('part');
        $qtys   = $lay('qty');
        $prices = $lay('price');
        $discs  = $lay('disc');
        $notes  = $lay('note');

        $lines = [];
        foreach ($parts as $i => $p){
            $partId = (int) $p;
            $qty    = $this->parseNum(isset($qtys[$i]) ? $qtys[$i] : 0);
            $price  = $this->parseMoney(isset($prices[$i]) ? $prices[$i] : 0);
            if ($partId <= 0 || $qty <= 0) continue;
            $lines[] = ['part_id' => $partId, 'quantity' => $qty, 'unit_price' => $price,
                        'discount_percent' => $this->parseRate(isset($discs[$i]) ? $discs[$i] : 0),
                        'note' => isset($notes[$i]) ? trim($notes[$i]) : ''];
        }
        return $lines;
    }

    private function fmtQty($n){ return rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.'); }
    private function parseNum($val){
        $s = preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $val));
        return $s === '' ? 0 : (float) $s;
    }
    private function parseMoney($val){
        $d = preg_replace('/[^\d]/', '', (string) $val);
        return $d === '' ? 0 : (float) $d;
    }
    private function parseRate($val){
        $s = preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $val));
        return $s === '' ? 0.0 : (float) $s;
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

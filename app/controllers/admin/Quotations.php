<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * BÁN HÀNG — Báo giá. Chỉ đề xuất giá (không tác động tồn/kế toán).
 * Có trạng thái nháp/đã gửi/chấp nhận/từ chối; báo giá "chấp nhận" chuyển thành hoá đơn.
 */
class Quotations extends Controller {

    private $__data = [];
    private $__model, $__itemModel, $__partner, $__part, $__invoice, $__warehouse, $__request, $__response;

    private $routeBase = 'quotations';
    private $labelOne  = 'báo giá';
    private $labelMany = 'Báo giá';
    private $viewDir   = 'admin/quotations';

    function __construct(){
        $this->__model     = $this->model('QuotationsModel');
        $this->__itemModel = $this->model('QuotationItemsModel');
        $this->__partner   = $this->model('PartnersModel');
        $this->__part      = $this->model('PartsModel');
        $this->__invoice   = $this->model('SalesInvoicesModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['statuses']  = QuotationsModel::$statuses;
    }

    private function formData(){
        $this->__data['content']['partners'] = $this->__partner->getActive();
        $this->__data['content']['parts']    = $this->__part->getForSelect();
        $this->__data['content']['partnerDiscounts'] = $this->__partner->groupDiscountMap();
    }

    /* ==================================================================
     * CHÉP TỪ BÁO GIÁ CŨ
     *
     * Gara hay lặp lại đúng một đơn: chi nhánh mới mở đặt y hệt chi nhánh cũ,
     * hoặc khách cũ đặt lại combo bảo dưỡng. Gõ lại từng dòng là việc thừa.
     *
     * Hai endpoint trả JSON, form gọi bằng fetch nên không phải tải lại trang
     * và không mất những gì đang gõ dở ở phần đầu phiếu.
     * ================================================================== */

    /** Danh sách báo giá để chọn. ?customer_id= để đẩy đơn của khách đó lên đầu. */
    public function copyList(){
        if (!route('admin/' . $this->routeBase . '/add')){ $this->jsonLoi('Không có quyền', 403); return; }

        $f   = $this->__request->getFields();
        $kh  = !empty($f['customer_id']) ? (int) $f['customer_id'] : 0;
        $ds  = $this->__model->danhSachDeChep($kh, 50);

        $ra = [];
        foreach ($ds ?: [] as $q){
            $ra[] = [
                'id'       => (int) $q['id'],
                'quote_no' => $q['quote_no'],
                'ngay'     => !empty($q['quote_date']) ? date('d/m/Y', strtotime($q['quote_date'])) : '',
                'khach'    => $q['khach'] !== '' ? $q['khach'] : '—',
                'so_dong'  => (int) $q['so_dong'],
                'tong'     => (float) $q['total_amount'],
                'cua_khach_nay' => ((int) $q['uu_tien'] === 0 && $kh > 0),
            ];
        }
        $this->json(['items' => $ra]);
    }

    /** Dòng hàng của một báo giá, đã tách sẵn theo tab Hàng hoá / Dịch vụ. */
    public function copyLines($id){
        if (!route('admin/' . $this->routeBase . '/add')){ $this->jsonLoi('Không có quyền', 403); return; }

        $bg = $this->__model->getDetail($id);
        if (empty($bg)){ $this->jsonLoi('Không tìm thấy báo giá', 404); return; }

        $dong    = $this->__itemModel->dongDeChep($id);
        $tongGoc = $this->__itemModel->demDong($id);

        $hang = $dichvu = [];
        $boQuaNgungBan = 0;

        foreach ($dong ?: [] as $d){
            // Mặt hàng đã ngừng kinh doanh: bỏ ngay ở đây thay vì để tới lúc
            // lưu mới báo lỗi.
            if ((int) $d['con_ban'] !== 1){ $boQuaNgungBan++; continue; }

            $row = [
                'part_id'  => (int) $d['part_id'],
                'qty'      => rtrim(rtrim(number_format((float) $d['quantity'], 3, '.', ''), '0'), '.'),
                'gia_cu'   => (int) $d['unit_price'],
                'gia_moi'  => (int) $d['gia_bay_gio'],
                'disc'     => (float) $d['discount_percent'],
                'note'     => (string) $d['note'],
                'ten'      => $d['part_code'] . ' - ' . $d['part_name'],
            ];

            if ($d['item_type'] === PartsModel::LOAI_DICH_VU) $dichvu[] = $row;
            else                                              $hang[]   = $row;
        }

        // Chênh lệch = số dòng có mặt hàng đã bị XOÁ hẳn khỏi bảng `parts`
        $daXoa = max(0, $tongGoc - count($dong ?: []));

        $this->json([
            'quote_no'    => $bg['quote_no'],
            'customer_id' => (int) $bg['customer_id'],
            'vat_rate'    => (float) $bg['vat_rate'],
            'hang'        => $hang,
            'dichvu'      => $dichvu,
            'bo_qua'      => ['da_xoa' => $daXoa, 'ngung_ban' => $boQuaNgungBan],
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
        $status = isset($f['status']) && isset(QuotationsModel::$statuses[$f['status']]) ? $f['status'] : '';
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
            'quote_no'   => $this->__model->nextNo(),
            'status'     => 'draft',
            'created_by' => Session::get('dataUser'),
        ]));

        $this->syncTotals($id, $lines, $f);

        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' (nháp).');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Báo giá ' . $item['quote_no'];

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Báo giá ' . $item['quote_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__itemModel->getByQuotation($id);
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

        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $f     = $this->__request->getFields();
        $lines = $this->buildLines();

        $this->__model->edit($this->headerData($f), $id);
        $this->syncTotals($id, $lines, $f);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Đổi trạng thái: sent / accepted / rejected / draft */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f = $this->__request->getFields();
        $st = isset($f['status']) ? $f['status'] : '';
        if (!isset(QuotationsModel::$statuses[$st])){
            Session::flash('msgError', 'Trạng thái không hợp lệ');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $this->__model->edit(['status' => $st], $id);
        Session::flash('msg', 'Đã chuyển trạng thái: ' . QuotationsModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Chuyển báo giá -> hoá đơn bán (nháp), copy dòng + giá + thuế */
    public function convert($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/sales-invoices/add')){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $items = $this->__itemModel->getByQuotation($id);
        if (empty($items)){
            Session::flash('msgError', 'Báo giá chưa có dòng hàng.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $wh = $this->__warehouse->getDefault();
        if (empty($wh)){
            Session::flash('msgError', 'Chưa có kho nào để xuất hàng.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $invItem = $this->model('SalesInvoiceItemsModel');
        $invId = $this->__invoice->add([
            'invoice_no'    => $this->__invoice->nextNo(),
            'customer_id'   => $item['customer_id'] !== null ? (int) $item['customer_id'] : null,
            'customer_name' => $item['customer_name'],
            'warehouse_id'  => (int) $wh['id'],
            'quotation_id'  => (int) $id,
            'invoice_date'  => date('Y-m-d'),
            'vat_rate'      => (float) $item['vat_rate'],
            'subtotal'      => 0, 'tax_amount' => 0, 'total_amount' => 0, 'cost_amount' => 0,
            'status'        => 0,
            'note'          => 'Từ báo giá ' . $item['quote_no'],
            'created_by'    => Session::get('dataUser'),
        ]);
        $lines = [];
        foreach ($items as $it){
            $lines[] = ['part_id' => (int) $it['part_id'], 'quantity' => (float) $it['quantity'],
                        'unit_price' => (float) $it['unit_price'],
                        'discount_percent' => (float) ($it['discount_percent'] ?? 0), 'note' => $it['note']];
        }
        $subtotal = $invItem->syncForInvoice($invId, $lines);
        $tax = round($subtotal * (float) $item['vat_rate'] / 100, 2);
        $this->__invoice->edit(['subtotal' => $subtotal, 'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax], $invId);

        Session::flash('msg', 'Đã tạo hoá đơn nháp từ báo giá ' . $item['quote_no']);
        $this->__response->redirect('admin/sales-invoices/edit/' . $invId);
    }

    /**
     * BIỂU MẪU IN — mở trên trình duyệt để in / lưu PDF, hoặc ?word=1 để tải .doc.
     *
     * Cùng một mẫu cho cả hai đường ra, xem app/views/admin/print/chung-tu.php.
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
        // Khách vãng lai không có dòng trong `partners`, chỉ còn cái tên trên phiếu
        if (empty($khach) && !empty($item['customer_name'])) $khach = ['name' => $item['customer_name']];

        $ct = [
            'loai'         => 'BÁO GIÁ',
            'so'           => $item['quote_no'],
            'ngay'         => $item['quote_date'],
            'hieuLuc'      => $item['valid_until'],
            'ghiChu'       => $item['note'],
            'subtotal'     => $item['subtotal'],
            'vatRate'      => $item['vat_rate'],
            'tax'          => $item['tax_amount'],
            'total'        => $item['total_amount'],
            // Báo giá chưa phải đòi tiền nên không in số tài khoản
            'hienNganHang' => false,
            'nhanKy'       => ['KHÁCH HÀNG', 'ĐẠI DIỆN BÊN BÁN'],
            /* Chỉ tự bật hộp thoại In khi tới từ nút "In / Lưu PDF" (?in=1).
               Mở thẳng /print/<id> thì chỉ xem — dán link cho người khác mà
               nó tự nhảy hộp thoại in là khó chịu, và không kịp soát lại
               chứng từ trước khi in. */
            'tuMoHopIn'    => !$laWord && !empty($f['in']),
        ];

        if ($laWord) header_word($item['quote_no']);

        in_chung_tu($ct, $khach, $this->__itemModel->getByQuotation($id),
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
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    /**
     * KHÔNG còn ghi customer_name và note: hai ô đó đã bỏ khỏi form (04/08/2026).
     *
     * Quan trọng là phải bỏ khỏi ĐÂY chứ không chỉ bỏ ô ngoài giao diện. Hàm
     * này dùng chung cho cả thêm lẫn sửa; ô không còn thì $f không có giá trị,
     * để nguyên hai dòng cũ là mỗi lần sửa một phiếu cũ sẽ XOÁ TRẮNG tên khách
     * vãng lai và ghi chú đã lưu trước đây.
     *
     * Cột trong CSDL giữ nguyên — dữ liệu cũ còn đó, màn hình danh sách vẫn đọc.
     */
    private function headerData($f){
        return [
            'customer_id'   => $this->customerId(),
            'quote_date'    => $f['quote_date'],
            'valid_until'   => !empty($f['valid_until']) ? $f['valid_until'] : null,
            'vat_rate'      => $this->parseRate(isset($f['vat_rate']) ? $f['vat_rate'] : 0),
        ];
    }

    private function syncTotals($id, $lines, $f){
        $subtotal = $this->__itemModel->syncForQuotation($id, $lines);
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
        if (empty($f['quote_date'])) $errors['quote_date'] = 'Chọn ngày báo giá';
        // "hàng hoá HOẶC dịch vụ": báo giá chỉ toàn dịch vụ (thay dầu, rửa xe)
        // là hoàn toàn hợp lệ với một gara.
        if (empty($this->buildLines())) $errors['lines'] = 'Báo giá phải có ít nhất 1 dòng hàng hoá hoặc dịch vụ';
        return $errors;
    }

    /**
     * Dòng báo giá gộp từ CẢ HAI tab: Hàng hoá (line_*) và Dịch vụ (sv_*).
     *
     * Hai tab là chuyện của giao diện — xuống CSDL vẫn là `quotation_items`
     * chung một bảng, phân biệt nhau bằng `parts.item_type`. Nhờ vậy tổng tiền,
     * thuế và bước "chuyển thành hoá đơn" không phải biết gì về tab.
     *
     * Tên ô của hai bảng phải KHÁC nhau (line_ / sv_). Dùng chung một tên thì
     * thứ tự phần tử phụ thuộc thứ tự DOM — chỉ cần đổi chỗ hai tab hoặc bọc
     * thêm một thẻ là số lượng lệch sang mặt hàng khác mà không báo lỗi gì.
     */
    private function buildLines(){
        return array_merge($this->docDong('line_'), $this->docDong('sv_'));
    }

    /** Đọc một bảng dòng hàng theo tiền tố tên ô ('line_' hoặc 'sv_') */
    private function docDong($tienTo){
        $f      = $this->__request->getFields();
        $lay    = function($ten) use ($f, $tienTo){
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

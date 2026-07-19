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

    private function headerData($f){
        return [
            'customer_id'   => $this->customerId(),
            'customer_name' => !empty($f['customer_name']) ? trim($f['customer_name']) : null,
            'quote_date'    => $f['quote_date'],
            'valid_until'   => !empty($f['valid_until']) ? $f['valid_until'] : null,
            'vat_rate'      => $this->parseRate(isset($f['vat_rate']) ? $f['vat_rate'] : 0),
            'note'          => !empty($f['note']) ? trim($f['note']) : null,
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
        if (empty($this->buildLines())) $errors['lines'] = 'Báo giá phải có ít nhất 1 dòng hàng';
        return $errors;
    }

    private function buildLines(){
        $f      = $this->__request->getFields();
        $parts  = isset($f['line_part'])  && is_array($f['line_part'])  ? $f['line_part']  : [];
        $qtys   = isset($f['line_qty'])   && is_array($f['line_qty'])   ? $f['line_qty']   : [];
        $prices = isset($f['line_price']) && is_array($f['line_price']) ? $f['line_price'] : [];
        $discs  = isset($f['line_disc'])  && is_array($f['line_disc'])  ? $f['line_disc']  : [];
        $notes  = isset($f['line_note'])  && is_array($f['line_note'])  ? $f['line_note']  : [];

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

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
    private $__accModel, $__voucherModel, $__entryModel, $__request, $__response;

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
        $this->__accModel     = $this->model('AccAccountsModel');
        $this->__voucherModel = $this->model('AccVouchersModel');
        $this->__entryModel   = $this->model('AccVoucherEntriesModel');
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
        $this->__data['content']['voucher']   = $item['acc_voucher_id']
            ? $this->__voucherModel->getDetail($item['acc_voucher_id']) : null;
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

        // Chặn nếu tồn không đủ (gộp cùng phụ tùng)
        $need = [];
        foreach ($items as $it){ $need[(int) $it['part_id']] = ($need[(int) $it['part_id']] ?? 0) + (float) $it['quantity']; }
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

        // Tài khoản
        $acc = [];
        foreach ([self::RECEIVABLE, self::REVENUE, self::TAX, self::COGS, self::INVENTORY] as $code){
            $row = $this->__accModel->findByCode($code);
            if (empty($row)){
                Session::flash('msgError', 'Thiếu tài khoản ' . $code . ' trong danh mục.');
                $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
            }
            $acc[$code] = (int) $row['id'];
        }

        $date = $item['invoice_date'];
        $no   = $item['invoice_no'];
        $rate = (float) $item['vat_rate'];

        $this->__model->transaction(function($db) use ($id, $item, $items, $wh, $date, $no, $rate, $acc){
            $subtotal = 0.0; $cost = 0.0;
            foreach ($items as $it){
                $avg = $this->__stock->applyOut($wh, (int) $it['part_id'], (float) $it['quantity'],
                    self::DOC_TYPE, $id, $no, $date, $it['note']);
                $costAmt = round((float) $it['quantity'] * $avg, 2);
                $this->__itemModel->setCost((int) $it['id'], $avg, $costAmt);
                $subtotal += (float) $it['amount'];
                $cost     += $costAmt;
            }
            $tax   = round($subtotal * $rate / 100, 2);
            $total = $subtotal + $tax;

            $vid = $this->__voucherModel->add([
                'voucher_no'      => $this->__voucherModel->nextNo('ke_toan'),
                'voucher_type'    => 'ke_toan',
                'voucher_date'    => $date,
                'cash_account_id' => null,
                'partner_id'      => $item['customer_id'] !== null ? (int) $item['customer_id'] : null,
                'partner_name'    => $item['customer_name'],
                'reason'          => 'Tự động từ hoá đơn ' . $no,
                'amount'          => $total,
                'status'          => 1,
            ]);
            // Nợ 131 / Có 511 (doanh thu)
            $this->__entryModel->addJournalLine($vid, $acc[self::RECEIVABLE], $acc[self::REVENUE], $subtotal, 'Doanh thu ' . $no);
            // Nợ 131 / Có 3331 (thuế)
            if ($tax > 0){
                $this->__entryModel->addJournalLine($vid, $acc[self::RECEIVABLE], $acc[self::TAX], $tax, 'Thuế GTGT ' . $no);
            }
            // Nợ 632 / Có 156 (giá vốn)
            if ($cost > 0){
                $this->__entryModel->addJournalLine($vid, $acc[self::COGS], $acc[self::INVENTORY], $cost, 'Giá vốn ' . $no);
            }

            $this->__model->edit(['status' => 1, 'subtotal' => $subtotal, 'tax_amount' => $tax,
                'total_amount' => $total, 'cost_amount' => $cost, 'acc_voucher_id' => $vid], $id);
        });

        Session::flash('msg', 'Đã ghi sổ ' . $no . ' — doanh thu, công nợ, giá vốn & trừ tồn kho.');
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

        $voucherId = $item['acc_voucher_id'] ? (int) $item['acc_voucher_id'] : 0;

        $this->__model->transaction(function($db) use ($id, $items, $wh, $voucherId){
            foreach ($items as $it){
                $this->__stock->reverseDoc($wh, (int) $it['part_id'], self::DOC_TYPE, $id);
                $this->__itemModel->setCost((int) $it['id'], 0, 0);
            }
            if ($voucherId > 0){ $this->__voucherModel->remove($voucherId); }
            $this->__model->edit(['status' => 0, 'acc_voucher_id' => null, 'cost_amount' => 0], $id);
        });

        Session::flash('msg', 'Đã huỷ ghi sổ ' . $item['invoice_no'] . ' — hoàn tồn kho & xoá bút toán.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
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

    private function headerData($f){
        return [
            'customer_id'   => $this->customerId(),
            'customer_name' => !empty($f['customer_name']) ? trim($f['customer_name']) : null,
            'warehouse_id'  => (int) $f['warehouse_id'],
            'invoice_date'  => $f['invoice_date'],
            'vat_rate'      => $this->parseRate(isset($f['vat_rate']) ? $f['vat_rate'] : 0),
            'note'          => !empty($f['note']) ? trim($f['note']) : null,
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
        $whId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        if ($whId <= 0 || empty($this->__warehouse->getDetail($whId))){
            $errors['warehouse_id'] = 'Chọn kho xuất hàng';
        }
        if (empty($f['invoice_date'])) $errors['invoice_date'] = 'Chọn ngày hoá đơn';
        if (empty($this->buildLines())) $errors['lines'] = 'Hoá đơn phải có ít nhất 1 dòng hàng';
        return $errors;
    }

    private function buildLines(){
        $f      = $this->__request->getFields();
        $parts  = isset($f['line_part'])  && is_array($f['line_part'])  ? $f['line_part']  : [];
        $qtys   = isset($f['line_qty'])   && is_array($f['line_qty'])   ? $f['line_qty']   : [];
        $prices = isset($f['line_price']) && is_array($f['line_price']) ? $f['line_price'] : [];
        $notes  = isset($f['line_note'])  && is_array($f['line_note'])  ? $f['line_note']  : [];

        $lines = [];
        foreach ($parts as $i => $p){
            $partId = (int) $p;
            $qty    = $this->parseNum(isset($qtys[$i]) ? $qtys[$i] : 0);
            $price  = $this->parseMoney(isset($prices[$i]) ? $prices[$i] : 0);
            if ($partId <= 0 || $qty <= 0) continue;
            $lines[] = ['part_id' => $partId, 'quantity' => $qty, 'unit_price' => $price,
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

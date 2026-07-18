<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KT-3 — Phiếu kế toán (general journal): định khoản TỰ DO Nợ/Có.
 * Mỗi dòng = Nợ TK / Có TK / số tiền. Dùng cho nghiệp vụ không có chứng từ quỹ
 * (điều chuyển tiền, kết chuyển, điều chỉnh...). Ghi sổ/khoá như phiếu thu/chi.
 */
class Journal extends Controller {

    private $__data = [];
    private $__model, $__entryModel, $__accModel, $__request, $__response;

    private $routeBase = 'journal';
    private $labelOne  = 'phiếu kế toán';
    private $labelMany = 'Phiếu kế toán';
    private $viewDir   = 'admin/journal';

    function __construct(){
        $this->__model      = $this->model('AccVouchersModel');
        $this->__entryModel = $this->model('AccVoucherEntriesModel');
        $this->__accModel   = $this->model('AccAccountsModel');
        $this->__request    = new Request();
        $this->__response   = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
    }

    /** map id => "code - name" cho hiển thị định khoản */
    private function accountMap(){
        $map = [];
        foreach ($this->__accModel->getTree() as $a){
            $map[(int) $a['id']] = $a['code'] . ' - ' . $a['name'];
        }
        return $map;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f    = $this->__request->getFields();
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $this->__data['content']['page_name']  = $this->labelMany;
        $this->__data['content']['dataList']   = $this->__model->getJournalList($from, $to);
        $this->__data['content']['filterFrom'] = $from;
        $this->__data['content']['filterTo']   = $to;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['msgError']   = Session::flash('msgError');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;

        $this->baseData();
        $this->__data['content']['page_name'] = 'Lập ' . $this->labelOne;
        $this->__data['content']['accounts']  = $this->__accModel->getDetailAccounts();
        $this->__data['content']['today']     = date('Y-m-d');
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
        $total = 0.0; foreach ($lines as $l){ $total += $l['amount']; }

        $voucherId = $this->__model->add([
            'voucher_no'      => $this->__model->nextNo('ke_toan'),
            'voucher_type'    => 'ke_toan',
            'voucher_date'    => $f['voucher_date'],
            'cash_account_id' => null,
            'partner_name'    => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'reason'          => !empty($f['reason']) ? trim($f['reason']) : null,
            'amount'          => $total,
            'status'          => 0,
        ]);

        $this->__entryModel->syncJournalForVoucher($voucherId, $lines);

        Session::flash('msg', 'Đã lập phiếu kế toán (nháp). Kiểm tra rồi bấm "Ghi sổ".');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $voucherId);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item) || $item['voucher_type'] !== 'ke_toan'){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Phiếu ' . $item['voucher_no'];

        $this->baseData();
        $this->__data['content']['page_name']  = 'Phiếu ' . $item['voucher_no'];
        $this->__data['content']['item']       = $item;
        $this->__data['content']['accounts']   = $this->__accModel->getDetailAccounts();
        $this->__data['content']['entries']    = $this->__entryModel->getJournalByVoucher($id);
        $this->__data['content']['accountMap'] = $this->accountMap();
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['errors']     = Session::flash('errors');
        $this->__data['content']['old']        = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item) || $item['voucher_type'] !== 'ke_toan'){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if ((int) $item['status'] === 1){
            Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi sửa.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $f     = $this->__request->getFields();
        $lines = $this->buildLines();
        $total = 0.0; foreach ($lines as $l){ $total += $l['amount']; }

        $this->__model->edit([
            'voucher_date' => $f['voucher_date'],
            'partner_name' => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'reason'       => !empty($f['reason']) ? trim($f['reason']) : null,
            'amount'       => $total,
        ], $id);

        $this->__entryModel->syncJournalForVoucher($id, $lines);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function post($id){
        $item = $this->__model->getDetail($id);
        if (empty($item) || $item['voucher_type'] !== 'ke_toan'){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        if (empty($this->__entryModel->getJournalByVoucher($id))){
            Session::flash('msgError', 'Phiếu chưa có định khoản, không thể ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $this->__model->edit(['status' => 1], $id);
        Session::flash('msg', 'Đã ghi sổ phiếu ' . $item['voucher_no']);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function unpost($id){
        $item = $this->__model->getDetail($id);
        if (empty($item) || $item['voucher_type'] !== 'ke_toan'){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $this->__model->edit(['status' => 0], $id);
        Session::flash('msg', 'Đã huỷ ghi sổ phiếu ' . $item['voucher_no']);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item) || $item['voucher_type'] !== 'ke_toan'){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if ((int) $item['status'] === 1){
            Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi xoá.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function validateInput(){
        $f = $this->__request->getFields();
        $errors = [];
        if (empty($f['voucher_date'])){
            $errors['voucher_date'] = 'Chọn ngày phiếu';
        }
        if (empty($this->buildLines())){
            $errors['lines'] = 'Phải có ít nhất 1 dòng định khoản hợp lệ (Nợ + Có + số tiền > 0)';
        }
        return $errors;
    }

    private function buildLines(){
        $f  = $this->__request->getFields();
        $dr = isset($f['line_debit'])  && is_array($f['line_debit'])  ? $f['line_debit']  : [];
        $cr = isset($f['line_credit']) && is_array($f['line_credit']) ? $f['line_credit'] : [];
        $am = isset($f['line_amount']) && is_array($f['line_amount']) ? $f['line_amount'] : [];
        $ds = isset($f['line_desc'])   && is_array($f['line_desc'])   ? $f['line_desc']   : [];

        $lines = [];
        foreach ($dr as $i => $d){
            $dd  = (int) $d;
            $cc  = isset($cr[$i]) ? (int) $cr[$i] : 0;
            $amt = $this->parseMoney(isset($am[$i]) ? $am[$i] : 0);
            if ($dd <= 0 || $cc <= 0 || $amt <= 0) continue;
            $lines[] = [
                'debit_account_id'  => $dd,
                'credit_account_id' => $cc,
                'amount'            => $amt,
                'description'       => isset($ds[$i]) ? trim($ds[$i]) : '',
            ];
        }
        return $lines;
    }

    private function parseMoney($val){
        $digits = preg_replace('/[^\d]/', '', (string) $val);
        return $digits === '' ? 0 : (float) $digits;
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

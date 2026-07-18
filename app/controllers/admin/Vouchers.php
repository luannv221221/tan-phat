<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KT-2 — Phiếu thu / chi (bút toán kép).
 *
 * Phiếu ở trạng thái NHÁP (status=0) mới sửa/xoá được; ĐÃ GHI SỔ (status=1) bị khoá,
 * muốn sửa phải huỷ ghi sổ. Số tiền phiếu = tổng các dòng định khoản.
 */
class Vouchers extends Controller {

    private $__data = [];
    private $__model, $__entryModel, $__accModel, $__costModel, $__projModel, $__partnerModel, $__request, $__response;

    private $routeBase = 'vouchers';
    private $labelOne  = 'phiếu';
    private $labelMany = 'Phiếu thu / chi';
    private $viewDir   = 'admin/vouchers';

    function __construct(){
        $this->__model      = $this->model('AccVouchersModel');
        $this->__entryModel = $this->model('AccVoucherEntriesModel');
        $this->__accModel   = $this->model('AccAccountsModel');
        $this->__costModel  = $this->model('AccCostItemsModel');
        $this->__projModel  = $this->model('AccProjectsModel');
        $this->__partnerModel = $this->model('PartnersModel');
        $this->__request    = new Request();
        $this->__response   = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['types']     = AccVouchersModel::$types;
    }

    private function formData(){
        $this->__data['content']['accounts']     = $this->__accModel->getDetailAccounts();
        $this->__data['content']['cashAccounts'] = $this->__accModel->getCashAccounts();
        $this->__data['content']['costItems']    = $this->__costModel->getLists();
        $this->__data['content']['projects']     = $this->__projModel->getLists();
        $this->__data['content']['partners']     = $this->__partnerModel->getActive();
    }

    /** partner_id hợp lệ (tồn tại) hoặc null */
    private function partnerId(){
        $f = $this->__request->getFields();
        $id = !empty($f['partner_id']) ? (int) $f['partner_id'] : 0;
        if ($id <= 0) return null;
        return !empty($this->__partnerModel->getDetail($id)) ? $id : null;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f    = $this->__request->getFields();
        $type = isset($f['type']) && in_array($f['type'], ['thu', 'chi'], true) ? $f['type'] : '';
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';

        $this->__data['content']['page_name']  = $this->labelMany;
        $this->__data['content']['dataList']   = $this->__model->getLists($type, $from, $to);
        $this->__data['content']['filterType'] = $type;
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
        $this->formData();
        $this->__data['content']['page_name'] = 'Lập ' . $this->labelOne;
        $this->__data['content']['today']     = date('Y-m-d');
        $this->__data['content']['entries']   = [];
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }

        $f     = $this->__request->getFields();
        $type  = $f['type'];
        $lines = $this->buildLines();
        $total = $this->linesTotal($lines);

        $voucherId = $this->__model->add([
            'voucher_no'      => $this->__model->nextNo($type),
            'voucher_type'    => $type,
            'voucher_date'    => $f['voucher_date'],
            'cash_account_id' => (int) $f['cash_account_id'],
            'partner_id'      => $this->partnerId(),
            'partner_name'    => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'reason'          => !empty($f['reason']) ? trim($f['reason']) : null,
            'amount'          => $total,
            'status'          => 0,
        ]);

        $this->__entryModel->syncForVoucher($voucherId, $lines);

        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' (nháp). Kiểm tra rồi bấm "Ghi sổ".');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $voucherId);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Phiếu ' . $item['voucher_no'];

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Phiếu ' . $item['voucher_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['entries']   = $this->__entryModel->getByVoucher($id);
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
            Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi sửa.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $f     = $this->__request->getFields();
        $lines = $this->buildLines();
        $total = $this->linesTotal($lines);

        // Không cho đổi loại phiếu (giữ số phiếu nhất quán)
        $this->__model->edit([
            'voucher_date'    => $f['voucher_date'],
            'cash_account_id' => (int) $f['cash_account_id'],
            'partner_id'      => $this->partnerId(),
            'partner_name'    => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'reason'          => !empty($f['reason']) ? trim($f['reason']) : null,
            'amount'          => $total,
        ], $id);

        $this->__entryModel->syncForVoucher($id, $lines);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Ghi sổ (nháp -> đã ghi sổ) */
    public function post($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $entries = $this->__entryModel->getByVoucher($id);
        if (empty($entries)){
            Session::flash('msgError', 'Phiếu chưa có định khoản, không thể ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $this->__model->edit(['status' => 1], $id);
        Session::flash('msg', 'Đã ghi sổ phiếu ' . $item['voucher_no']);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** Huỷ ghi sổ (đã ghi sổ -> nháp) */
    public function unpost($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
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
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne);
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if ((int) $item['status'] === 1){
            Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi xoá.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        $this->__model->remove($id); // entries CASCADE
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function validateInput(){
        $f = $this->__request->getFields();
        $errors = [];

        if (empty($f['type']) || !in_array($f['type'], ['thu', 'chi'], true)){
            $errors['type'] = 'Chọn loại phiếu (thu/chi)';
        }
        if (empty($f['voucher_date'])){
            $errors['voucher_date'] = 'Chọn ngày phiếu';
        }
        $cashId = !empty($f['cash_account_id']) ? (int) $f['cash_account_id'] : 0;
        if ($cashId <= 0 || empty($this->__accModel->getDetail($cashId))){
            $errors['cash_account_id'] = 'Chọn tài khoản quỹ';
        }
        $lines = $this->buildLines();
        if (empty($lines)){
            $errors['lines'] = 'Phiếu phải có ít nhất 1 dòng định khoản (tài khoản + số tiền > 0)';
        }
        return $errors;
    }

    /** Ghép các mảng song song line_account[]/line_amount[]... thành danh sách dòng hợp lệ */
    private function buildLines(){
        $f     = $this->__request->getFields();
        $accs  = isset($f['line_account'])  && is_array($f['line_account'])  ? $f['line_account']  : [];
        $amts  = isset($f['line_amount'])   && is_array($f['line_amount'])   ? $f['line_amount']   : [];
        $descs = isset($f['line_desc'])     && is_array($f['line_desc'])     ? $f['line_desc']     : [];
        $costs = isset($f['line_cost'])     && is_array($f['line_cost'])     ? $f['line_cost']     : [];
        $projs = isset($f['line_project'])  && is_array($f['line_project'])  ? $f['line_project']  : [];

        $lines = [];
        foreach ($accs as $i => $acc){
            $accId  = (int) $acc;
            $amount = $this->parseMoney(isset($amts[$i]) ? $amts[$i] : 0);
            if ($accId <= 0 || $amount <= 0) continue;
            $lines[] = [
                'account_id'   => $accId,
                'amount'       => $amount,
                'description'  => isset($descs[$i]) ? trim($descs[$i]) : '',
                'cost_item_id' => isset($costs[$i]) && $costs[$i] !== '' ? (int) $costs[$i] : null,
                'project_id'   => isset($projs[$i]) && $projs[$i] !== '' ? (int) $projs[$i] : null,
            ];
        }
        return $lines;
    }

    private function linesTotal($lines){
        $t = 0.0;
        foreach ($lines as $l){ $t += (float) $l['amount']; }
        return $t;
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

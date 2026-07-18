<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO — Phiếu xuất kho (giá vốn bình quân gia quyền + KT-6).
 *
 * Giá vốn tính tại thời điểm GHI SỔ (bình quân hiện tại). Ghi sổ -> trừ tồn +
 * sinh phiếu kế toán: Nợ [đối ứng, mặc định 632 Giá vốn] / Có 156 Hàng hóa.
 * Phiếu xuất bán ở Kho chỉ ghi GIÁ VỐN; doanh thu do phân hệ Bán hàng sinh sau.
 */
class Goodsissues extends Controller {

    const INVENTORY_CODE  = '156';
    const DEFAULT_COUNTER = '632'; // giá vốn hàng bán
    const DOC_TYPE = 'issue';

    private $__data = [];
    private $__model, $__itemModel, $__stock, $__warehouse, $__partner, $__part;
    private $__accModel, $__voucherModel, $__entryModel, $__request, $__response;

    private $routeBase = 'goods-issues';
    private $labelOne  = 'phiếu xuất';
    private $labelMany = 'Phiếu xuất kho';
    private $viewDir   = 'admin/goods-issues';

    function __construct(){
        $this->__model        = $this->model('GoodsIssuesModel');
        $this->__itemModel    = $this->model('GoodsIssueItemsModel');
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
        $this->__data['content']['types']     = GoodsIssuesModel::$types;
    }

    private function formData(){
        $this->__data['content']['warehouses'] = $this->__warehouse->getActive();
        $this->__data['content']['partners']   = $this->__partner->getActive();
        $this->__data['content']['parts']      = $this->__part->getForSelect();
        $this->__data['content']['accounts']   = $this->__accModel->getDetailAccounts();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f    = $this->__request->getFields();
        $type = isset($f['type']) && isset(GoodsIssuesModel::$types[$f['type']]) ? $f['type'] : '';
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

        $id = $this->__model->add([
            'issue_no'           => $this->__model->nextNo(),
            'issue_type'         => $f['type'],
            'warehouse_id'       => (int) $f['warehouse_id'],
            'partner_id'         => $this->partnerId(),
            'partner_name'       => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'counter_account_id' => $this->counterId(),
            'issue_date'         => $f['issue_date'],
            'reason'             => !empty($f['reason']) ? trim($f['reason']) : null,
            'total_amount'       => 0,
            'status'             => 0,
        ]);

        $this->__itemModel->syncForIssue($id, $lines);

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
        $this->__data['page_title']  = 'Phiếu ' . $item['issue_no'];

        $this->baseData();
        $this->formData();
        $items = $this->__itemModel->getByIssue($id);
        // Tồn hiện tại từng phụ tùng (để cảnh báo khi lập)
        $stockMap = [];
        foreach ($items as $it){
            $stockMap[(int) $it['part_id']] = $this->__stock->available((int) $item['warehouse_id'], (int) $it['part_id']);
        }

        $this->__data['content']['page_name'] = 'Phiếu ' . $item['issue_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $items;
        $this->__data['content']['stockMap']  = $stockMap;
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
            Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi sửa.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $errors = $this->validateInput();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }

        $f     = $this->__request->getFields();
        $lines = $this->buildLines();

        $this->__model->edit([
            'issue_type'         => $f['type'],
            'warehouse_id'       => (int) $f['warehouse_id'],
            'partner_id'         => $this->partnerId(),
            'partner_name'       => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'counter_account_id' => $this->counterId(),
            'issue_date'         => $f['issue_date'],
            'reason'             => !empty($f['reason']) ? trim($f['reason']) : null,
        ], $id);

        $this->__itemModel->syncForIssue($id, $lines);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** GHI SỔ: tính giá vốn bình quân, trừ tồn + sinh bút toán KT-6 */
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
            Session::flash('msgError', 'Phiếu đã ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $items = $this->__itemModel->getByIssue($id);
        if (empty($items)){
            Session::flash('msgError', 'Phiếu chưa có dòng hàng, không thể ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $wh = (int) $item['warehouse_id'];

        // Chặn nếu tồn không đủ (gộp số lượng cùng phụ tùng)
        $need = [];
        foreach ($items as $it){ $need[(int) $it['part_id']] = ($need[(int) $it['part_id']] ?? 0) + (float) $it['quantity']; }
        $short = [];
        foreach ($need as $partId => $qty){
            if ($this->__stock->available($wh, $partId) + 1e-9 < $qty){
                $p = $this->__part->getDetail($partId);
                $short[] = ($p ? $p['code'] . ' - ' . $p['name'] : ('#' . $partId))
                    . ' (tồn ' . rtrim(rtrim(number_format($this->__stock->available($wh, $partId), 3), '0'), '.')
                    . ', cần ' . rtrim(rtrim(number_format($qty, 3), '0'), '.') . ')';
            }
        }
        if (!empty($short)){
            Session::flash('msgError', 'Tồn không đủ để xuất: ' . implode('; ', $short));
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $inv = $this->__accModel->findByCode(self::INVENTORY_CODE);
        if (empty($inv)){
            Session::flash('msgError', 'Thiếu tài khoản kho ' . self::INVENTORY_CODE . ' trong danh mục.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }
        $invId     = (int) $inv['id'];
        $counterId = $this->resolveCounter($item['counter_account_id']);
        if ($counterId <= 0){
            Session::flash('msgError', 'Chưa chọn tài khoản đối ứng cho phiếu.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $date = $item['issue_date'];
        $no   = $item['issue_no'];

        $this->__model->transaction(function($db) use ($id, $item, $items, $wh, $date, $no, $invId, $counterId){
            $total = 0.0;
            foreach ($items as $it){
                $avg = $this->__stock->applyOut($wh, (int) $it['part_id'], (float) $it['quantity'],
                    self::DOC_TYPE, $id, $no, $date, $it['note']);
                $amount = round((float) $it['quantity'] * $avg, 2);
                $this->__itemModel->setCost((int) $it['id'], $avg, $amount);
                $total += $amount;
            }

            // Bút toán KT-6: Nợ [đối ứng, 632] / Có 156
            $vid = $this->__voucherModel->add([
                'voucher_no'      => $this->__voucherModel->nextNo('ke_toan'),
                'voucher_type'    => 'ke_toan',
                'voucher_date'    => $date,
                'cash_account_id' => null,
                'partner_id'      => $item['partner_id'] !== null ? (int) $item['partner_id'] : null,
                'partner_name'    => $item['partner_name'],
                'reason'          => 'Tự động từ phiếu xuất ' . $no,
                'amount'          => $total,
                'status'          => 1,
            ]);
            $this->__entryModel->addJournalLine($vid, $counterId, $invId, $total, 'Giá vốn xuất kho ' . $no);

            $this->__model->edit(['status' => 1, 'total_amount' => $total, 'acc_voucher_id' => $vid], $id);
        });

        Session::flash('msg', 'Đã ghi sổ ' . $no . ' — trừ tồn kho & ghi giá vốn.');
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
            Session::flash('msgError', 'Phiếu chưa ghi sổ.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $items = $this->__itemModel->getByIssue($id);
        $wh = (int) $item['warehouse_id'];

        $blocked = [];
        foreach ($items as $it){
            if (!$this->__stock->isLastMovement($wh, (int) $it['part_id'], self::DOC_TYPE, $id)){
                $blocked[] = $it['part_code'] . ' - ' . $it['part_name'];
            }
        }
        if (!empty($blocked)){
            Session::flash('msgError', 'Không huỷ được: đã có nhập/xuất sau phiếu này ở — ' . implode('; ', $blocked)
                . '. Huỷ các phiếu sau trước.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $voucherId = $item['acc_voucher_id'] ? (int) $item['acc_voucher_id'] : 0;

        $this->__model->transaction(function($db) use ($id, $items, $wh, $voucherId){
            foreach ($items as $it){
                $this->__stock->reverseDoc($wh, (int) $it['part_id'], self::DOC_TYPE, $id);
                $this->__itemModel->setCost((int) $it['id'], 0, 0);
            }
            if ($voucherId > 0){
                $this->__voucherModel->remove($voucherId);
            }
            $this->__model->edit(['status' => 0, 'acc_voucher_id' => null, 'total_amount' => 0], $id);
        });

        Session::flash('msg', 'Đã huỷ ghi sổ ' . $item['issue_no'] . ' — hoàn tồn kho & xoá bút toán.');
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
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====

    private function resolveCounter($counterId){
        $counterId = (int) $counterId;
        if ($counterId > 0) return $counterId;
        $def = $this->__accModel->findByCode(self::DEFAULT_COUNTER);
        return !empty($def) ? (int) $def['id'] : 0;
    }

    private function counterId(){
        $f = $this->__request->getFields();
        $id = !empty($f['counter_account_id']) ? (int) $f['counter_account_id'] : 0;
        if ($id <= 0) return null;
        return !empty($this->__accModel->getDetail($id)) ? $id : null;
    }

    private function partnerId(){
        $f = $this->__request->getFields();
        $id = !empty($f['partner_id']) ? (int) $f['partner_id'] : 0;
        if ($id <= 0) return null;
        return !empty($this->__partner->getDetail($id)) ? $id : null;
    }

    private function validateInput(){
        $f = $this->__request->getFields();
        $errors = [];

        if (empty($f['type']) || !isset(GoodsIssuesModel::$types[$f['type']])){
            $errors['type'] = 'Chọn loại phiếu xuất';
        }
        $whId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        if ($whId <= 0 || empty($this->__warehouse->getDetail($whId))){
            $errors['warehouse_id'] = 'Chọn kho xuất';
        }
        if (empty($f['issue_date'])){
            $errors['issue_date'] = 'Chọn ngày phiếu';
        }
        if (empty($this->buildLines())){
            $errors['lines'] = 'Phiếu phải có ít nhất 1 dòng hàng (phụ tùng + số lượng > 0)';
        }
        return $errors;
    }

    private function buildLines(){
        $f     = $this->__request->getFields();
        $parts = isset($f['line_part']) && is_array($f['line_part']) ? $f['line_part'] : [];
        $qtys  = isset($f['line_qty'])  && is_array($f['line_qty'])  ? $f['line_qty']  : [];
        $notes = isset($f['line_note']) && is_array($f['line_note']) ? $f['line_note'] : [];

        $lines = [];
        foreach ($parts as $i => $p){
            $partId = (int) $p;
            $qty    = $this->parseNum(isset($qtys[$i]) ? $qtys[$i] : 0);
            if ($partId <= 0 || $qty <= 0) continue;
            $lines[] = [
                'part_id'  => $partId,
                'quantity' => $qty,
                'note'     => isset($notes[$i]) ? trim($notes[$i]) : '',
            ];
        }
        return $lines;
    }

    private function parseNum($val){
        $s = str_replace(',', '.', (string) $val);
        $s = preg_replace('/[^\d.]/', '', $s);
        return $s === '' ? 0 : (float) $s;
    }

    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

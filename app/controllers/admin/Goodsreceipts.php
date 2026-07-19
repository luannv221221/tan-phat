<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO — Phiếu nhập kho (bình quân gia quyền + KT-6).
 *
 * Nháp (status=0) mới sửa/xoá. GHI SỔ -> cập nhật tồn + sinh phiếu kế toán:
 *   Nợ 156 Hàng hóa / Có [TK đối ứng] (mặc định 331 với nhập mua).
 * HUỶ GHI SỔ chỉ được nếu phiếu là phát sinh cuối cùng của mọi phụ tùng (bình
 * quân gia quyền không đảo ngược được nếu đã có nhập/xuất chen sau).
 */
class Goodsreceipts extends Controller {

    const INVENTORY_CODE = '156'; // TK hàng hóa
    const DEFAULT_COUNTER = '331'; // TK đối ứng mặc định (phải trả NCC)
    const DOC_TYPE = 'receipt';

    private $__data = [];
    private $__model, $__itemModel, $__stock, $__warehouse, $__partner, $__part;
    private $__accModel, $__voucherModel, $__entryModel, $__request, $__response;

    private $routeBase = 'goods-receipts';
    private $labelOne  = 'phiếu nhập';
    private $labelMany = 'Phiếu nhập kho';
    private $viewDir   = 'admin/goods-receipts';

    function __construct(){
        $this->__model        = $this->model('GoodsReceiptsModel');
        $this->__itemModel    = $this->model('GoodsReceiptItemsModel');
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
        $this->__data['content']['types']     = GoodsReceiptsModel::$types;
    }

    private function formData(){
        $this->__data['content']['warehouses'] = $this->__warehouse->getActive();
        $this->__data['content']['partners']   = $this->__partner->getActive();
        $this->__data['content']['parts']      = $this->__part->getForSelect();
        $this->__data['content']['accounts']   = $this->__accModel->getDetailAccounts();
        // KHO-3: gợi ý vị trí trong kho (datalist) cho ô "Vị trí" dòng hàng
        $this->__data['content']['locations']  = $this->model('WarehouseLocationsModel')->getActivePaths();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $f    = $this->__request->getFields();
        $type = isset($f['type']) && isset(GoodsReceiptsModel::$types[$f['type']]) ? $f['type'] : '';
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
            'receipt_no'         => $this->__model->nextNo(),
            'receipt_type'       => $f['type'],
            'warehouse_id'       => (int) $f['warehouse_id'],
            'partner_id'         => $this->partnerId(),
            'partner_name'       => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'counter_account_id' => $this->counterId(),
            'receipt_date'       => $f['receipt_date'],
            'reason'             => !empty($f['reason']) ? trim($f['reason']) : null,
            'total_amount'       => 0,
            'status'             => 0,
        ]);

        $total = $this->__itemModel->syncForReceipt($id, $lines);
        $this->__model->edit(['total_amount' => $total], $id);

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
        $this->__data['page_title']  = 'Phiếu ' . $item['receipt_no'];

        $this->baseData();
        $this->formData();
        $this->__data['content']['page_name'] = 'Phiếu ' . $item['receipt_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__itemModel->getByReceipt($id);
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
            'receipt_type'       => $f['type'],
            'warehouse_id'       => (int) $f['warehouse_id'],
            'partner_id'         => $this->partnerId(),
            'partner_name'       => !empty($f['partner_name']) ? trim($f['partner_name']) : null,
            'counter_account_id' => $this->counterId(),
            'receipt_date'       => $f['receipt_date'],
            'reason'             => !empty($f['reason']) ? trim($f['reason']) : null,
        ], $id);

        $total = $this->__itemModel->syncForReceipt($id, $lines);
        $this->__model->edit(['total_amount' => $total], $id);

        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** GHI SỔ: cập nhật tồn (bình quân gia quyền) + sinh bút toán KT-6 */
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

        $items = $this->__itemModel->getByReceipt($id);
        if (empty($items)){
            Session::flash('msgError', 'Phiếu chưa có dòng hàng, không thể ghi sổ.');
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

        $wh   = (int) $item['warehouse_id'];
        $date = $item['receipt_date'];
        $no   = $item['receipt_no'];

        $this->__model->transaction(function($db) use ($id, $item, $items, $wh, $date, $no, $invId, $counterId){
            $total = 0.0;
            foreach ($items as $it){
                $this->__stock->applyIn($wh, (int) $it['part_id'], (float) $it['quantity'],
                    (float) $it['unit_cost'], self::DOC_TYPE, $id, $no, $date, $it['note']);
                $total += (float) $it['amount'];
            }

            // Bút toán KT-6: Nợ 156 / Có [đối ứng]
            $vid = $this->__voucherModel->add([
                'voucher_no'      => $this->__voucherModel->nextNo('ke_toan'),
                'voucher_type'    => 'ke_toan',
                'voucher_date'    => $date,
                'cash_account_id' => null,
                'partner_id'      => $item['partner_id'] !== null ? (int) $item['partner_id'] : null,
                'partner_name'    => $item['partner_name'],
                'reason'          => 'Tự động từ phiếu nhập ' . $no,
                'amount'          => $total,
                'status'          => 1,
            ]);
            $this->__entryModel->addJournalLine($vid, $invId, $counterId, $total, 'Nhập kho ' . $no);

            $this->__model->edit(['status' => 1, 'total_amount' => $total, 'acc_voucher_id' => $vid], $id);
        });

        Session::flash('msg', 'Đã ghi sổ ' . $no . ' — cập nhật tồn kho & sinh bút toán.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** HUỶ GHI SỔ: đảo tồn + xoá bút toán (chỉ khi là phát sinh cuối cùng) */
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

        $items = $this->__itemModel->getByReceipt($id);
        $wh = (int) $item['warehouse_id'];

        // Chặn nếu có phát sinh sau ở bất kỳ phụ tùng nào
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
            }
            if ($voucherId > 0){
                $this->__voucherModel->remove($voucherId); // entries CASCADE
            }
            $this->__model->edit(['status' => 0, 'acc_voucher_id' => null], $id);
        });

        Session::flash('msg', 'Đã huỷ ghi sổ ' . $item['receipt_no'] . ' — hoàn tồn kho & xoá bút toán.');
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
        $this->__model->remove($id); // items CASCADE
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

        if (empty($f['type']) || !isset(GoodsReceiptsModel::$types[$f['type']])){
            $errors['type'] = 'Chọn loại phiếu nhập';
        }
        $whId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        if ($whId <= 0 || empty($this->__warehouse->getDetail($whId))){
            $errors['warehouse_id'] = 'Chọn kho nhập';
        }
        if (empty($f['receipt_date'])){
            $errors['receipt_date'] = 'Chọn ngày phiếu';
        }
        if (empty($this->buildLines())){
            $errors['lines'] = 'Phiếu phải có ít nhất 1 dòng hàng (phụ tùng + số lượng > 0)';
        }
        return $errors;
    }

    /** Ghép mảng song song line_part[]/line_qty[]/line_cost[]... thành dòng hợp lệ */
    private function buildLines(){
        $f     = $this->__request->getFields();
        $parts = isset($f['line_part']) && is_array($f['line_part']) ? $f['line_part'] : [];
        $qtys  = isset($f['line_qty'])  && is_array($f['line_qty'])  ? $f['line_qty']  : [];
        $costs = isset($f['line_cost']) && is_array($f['line_cost']) ? $f['line_cost'] : [];
        $locs  = isset($f['line_loc'])  && is_array($f['line_loc'])  ? $f['line_loc']  : [];
        $notes = isset($f['line_note']) && is_array($f['line_note']) ? $f['line_note'] : [];

        $lines = [];
        foreach ($parts as $i => $p){
            $partId = (int) $p;
            $qty    = $this->parseNum(isset($qtys[$i]) ? $qtys[$i] : 0);
            $cost   = $this->parseMoney(isset($costs[$i]) ? $costs[$i] : 0);
            if ($partId <= 0 || $qty <= 0) continue;
            $lines[] = [
                'part_id'   => $partId,
                'quantity'  => $qty,
                'unit_cost' => $cost,
                'location'  => isset($locs[$i]) ? trim($locs[$i]) : '',
                'note'      => isset($notes[$i]) ? trim($notes[$i]) : '',
            ];
        }
        return $lines;
    }

    private function parseNum($val){
        $s = str_replace(',', '.', (string) $val);
        $s = preg_replace('/[^\d.]/', '', $s);
        return $s === '' ? 0 : (float) $s;
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

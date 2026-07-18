<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO-2 — Phiếu kiểm kê kho (WH-07).
 * Chốt (ghi sổ): so tồn sổ với thực tế; thừa -> nhập + Nợ156/Có711;
 * thiếu -> xuất + Nợ632/Có156 (1 phiếu kế toán KT-6).
 */
class Stocktakes extends Controller {

    const DOC_TYPE   = 'stock_take';
    const INVENTORY  = '156';
    const SHORTAGE   = '632'; // giá vốn — hàng thiếu
    const SURPLUS    = '711'; // thu nhập khác — hàng thừa

    private $__data = [];
    private $__model, $__itemModel, $__stock, $__warehouse, $__part;
    private $__accModel, $__voucherModel, $__entryModel, $__request, $__response;

    private $routeBase = 'stock-takes';
    private $labelOne  = 'phiếu kiểm kê';
    private $labelMany = 'Kiểm kê kho';
    private $viewDir   = 'admin/stock-takes';

    function __construct(){
        $this->__model        = $this->model('StockTakesModel');
        $this->__itemModel    = $this->model('StockTakeItemsModel');
        $this->__stock        = $this->model('StocksModel');
        $this->__warehouse    = $this->model('WarehousesModel');
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
        $this->__data['content']['parts']      = $this->__part->getForSelect();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $f    = $this->__request->getFields();
        $from = isset($f['from']) ? trim($f['from']) : '';
        $to   = isset($f['to'])   ? trim($f['to'])   : '';
        $this->__data['content']['page_name']  = $this->labelMany;
        $this->__data['content']['dataList']   = $this->__model->getLists($from, $to);
        $this->__data['content']['filterFrom'] = $from;
        $this->__data['content']['filterTo']   = $to;
        $this->__data['content']['msg']        = Session::flash('msg');
        $this->__data['content']['msgError']   = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function add(){
        $this->__data['sub_content'] = $this->viewDir . '/add';
        $this->__data['page_title']  = 'Lập ' . $this->labelOne;
        $this->baseData(); $this->formData();
        $this->__data['content']['page_name'] = 'Lập ' . $this->labelOne;
        $this->__data['content']['today']     = date('Y-m-d');
        $this->__data['content']['items']     = [];
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postAdd(){
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'add'); return; }
        $f = $this->__request->getFields();
        $id = $this->__model->add([
            'take_no'      => $this->__model->nextNo(),
            'warehouse_id' => (int) $f['warehouse_id'],
            'take_date'    => $f['take_date'],
            'reason'       => !empty($f['reason']) ? trim($f['reason']) : null,
            'status'       => 0,
            'created_by'   => Session::get('dataUser'),
        ]);
        $this->__itemModel->syncForTake($id, $this->buildLines());
        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' (nháp). Nhập số thực tế rồi bấm "Chốt kiểm kê".');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Phiếu ' . $item['take_no'];
        $this->baseData(); $this->formData();

        // Hiển thị tồn sổ hiện tại từng dòng (khi còn nháp)
        $items = $this->__itemModel->getByTake($id);
        $bookMap = [];
        foreach ($items as $it){ $bookMap[(int) $it['part_id']] = $this->__stock->available((int) $item['warehouse_id'], (int) $it['part_id']); }

        $this->__data['content']['page_name'] = 'Phiếu ' . $item['take_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $items;
        $this->__data['content']['bookMap']   = $bookMap;
        $this->__data['content']['voucher']   = $item['acc_voucher_id'] ? $this->__voucherModel->getDetail($item['acc_voucher_id']) : null;
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if ((int) $item['status'] === 1){ Session::flash('msgError', 'Phiếu đã chốt — huỷ chốt trước khi sửa.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $f = $this->__request->getFields();
        $this->__model->edit([
            'warehouse_id' => (int) $f['warehouse_id'],
            'take_date'    => $f['take_date'],
            'reason'       => !empty($f['reason']) ? trim($f['reason']) : null,
        ], $id);
        $this->__itemModel->syncForTake($id, $this->buildLines());
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** CHỐT KIỂM KÊ: điều chỉnh tồn theo thực tế + bút toán thừa/thiếu */
    public function post($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        if ((int) $item['status'] === 1){ Session::flash('msgError', 'Phiếu đã chốt.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $items = $this->__itemModel->getByTake($id);
        if (empty($items)){ Session::flash('msgError', 'Phiếu chưa có dòng hàng.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $acc = [];
        foreach ([self::INVENTORY, self::SHORTAGE, self::SURPLUS] as $code){
            $row = $this->__accModel->findByCode($code);
            if (empty($row)){ Session::flash('msgError', 'Thiếu tài khoản ' . $code . ' trong danh mục.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }
            $acc[$code] = (int) $row['id'];
        }

        $wh = (int) $item['warehouse_id']; $date = $item['take_date']; $no = $item['take_no'];
        $this->__model->transaction(function($db) use ($id, $items, $wh, $date, $no, $acc){
            $surplus = 0.0; $shortage = 0.0;
            foreach ($items as $it){
                $pid = (int) $it['part_id'];
                $actual = (float) $it['actual_qty'];
                $book = $this->__stock->available($wh, $pid);
                $avg  = $this->__stock->avgCost($wh, $pid);
                $diff = round($actual - $book, 3);
                if ($diff > 1e-9){
                    $this->__stock->applyIn($wh, $pid, $diff, $avg, self::DOC_TYPE, $id, $no, $date, $it['note']);
                    $surplus += round($diff * $avg, 2);
                } elseif ($diff < -1e-9){
                    $this->__stock->applyOut($wh, $pid, -$diff, self::DOC_TYPE, $id, $no, $date, $it['note']);
                    $shortage += round((-$diff) * $avg, 2);
                }
                $this->__itemModel->setResult((int) $it['id'], $book, $diff, $avg, round($diff * $avg, 2));
            }

            $vid = null;
            if ($surplus > 0 || $shortage > 0){
                $vid = $this->__voucherModel->add([
                    'voucher_no'      => $this->__voucherModel->nextNo('ke_toan'),
                    'voucher_type'    => 'ke_toan',
                    'voucher_date'    => $date,
                    'cash_account_id' => null,
                    'partner_id'      => null,
                    'partner_name'    => null,
                    'reason'          => 'Tự động từ kiểm kê ' . $no,
                    'amount'          => $surplus + $shortage,
                    'status'          => 1,
                ]);
                if ($surplus > 0) $this->__entryModel->addJournalLine($vid, $acc[self::INVENTORY], $acc[self::SURPLUS], $surplus, 'Hàng thừa ' . $no);
                if ($shortage > 0) $this->__entryModel->addJournalLine($vid, $acc[self::SHORTAGE], $acc[self::INVENTORY], $shortage, 'Hàng thiếu ' . $no);
            }

            $this->__model->edit(['status' => 1, 'surplus_value' => $surplus, 'shortage_value' => $shortage, 'acc_voucher_id' => $vid], $id);
        });
        Session::flash('msg', 'Đã chốt ' . $no . ' — điều chỉnh tồn kho.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** HUỶ CHỐT: đảo điều chỉnh + xoá bút toán (chỉ khi là phát sinh cuối) */
    public function unpost($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        if ((int) $item['status'] !== 1){ Session::flash('msgError', 'Phiếu chưa chốt.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $items = $this->__itemModel->getByTake($id);
        $wh = (int) $item['warehouse_id'];
        $blocked = [];
        foreach ($items as $it){
            if (abs((float) $it['diff_qty']) < 1e-9) continue; // dòng không lệch -> không có thẻ kho
            if (!$this->__stock->isLastMovement($wh, (int) $it['part_id'], self::DOC_TYPE, $id)){
                $blocked[] = $it['part_code'] . ' - ' . $it['part_name'];
            }
        }
        if (!empty($blocked)){ Session::flash('msgError', 'Không huỷ được: đã có phát sinh sau ở — ' . implode('; ', $blocked)); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $voucherId = $item['acc_voucher_id'] ? (int) $item['acc_voucher_id'] : 0;
        $this->__model->transaction(function($db) use ($id, $items, $wh, $voucherId){
            foreach ($items as $it){
                if (abs((float) $it['diff_qty']) < 1e-9) continue;
                $this->__stock->reverseDoc($wh, (int) $it['part_id'], self::DOC_TYPE, $id);
            }
            foreach ($items as $it){ $this->__itemModel->setResult((int) $it['id'], 0, 0, 0, 0); }
            if ($voucherId > 0) $this->__voucherModel->remove($voucherId);
            $this->__model->edit(['status' => 0, 'surplus_value' => 0, 'shortage_value' => 0, 'acc_voucher_id' => null], $id);
        });
        Session::flash('msg', 'Đã huỷ chốt ' . $item['take_no'] . ' — hoàn tồn kho & xoá bút toán.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if ((int) $item['status'] === 1){ Session::flash('msgError', 'Phiếu đã chốt — huỷ chốt trước khi xoá.'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====
    private function validate(){
        $f = $this->__request->getFields();
        $errors = [];
        $whId = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        if ($whId <= 0 || empty($this->__warehouse->getDetail($whId))) $errors['warehouse_id'] = 'Chọn kho kiểm kê';
        if (empty($f['take_date'])) $errors['take_date'] = 'Chọn ngày';
        if (empty($this->buildLines())) $errors['lines'] = 'Phiếu phải có ít nhất 1 dòng phụ tùng';
        return $errors;
    }

    private function buildLines(){
        $f     = $this->__request->getFields();
        $parts = isset($f['line_part'])   && is_array($f['line_part'])   ? $f['line_part']   : [];
        $acts  = isset($f['line_actual']) && is_array($f['line_actual']) ? $f['line_actual'] : [];
        $notes = isset($f['line_note'])   && is_array($f['line_note'])   ? $f['line_note']   : [];
        $lines = [];
        foreach ($parts as $i => $p){
            $partId = (int) $p;
            if ($partId <= 0) continue;
            $lines[] = ['part_id' => $partId, 'actual_qty' => $this->parseNum(isset($acts[$i]) ? $acts[$i] : 0), 'note' => isset($notes[$i]) ? trim($notes[$i]) : ''];
        }
        return $lines;
    }
    private function parseNum($val){
        $s = preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $val));
        return $s === '' ? 0 : (float) $s;
    }
    private function flash($errors, $back){
        Session::flash('errors', $errors);
        Session::flash('old', $this->__request->getFields());
        Session::flash('msg', 'Vui lòng kiểm tra các lỗi bên dưới');
        $this->__response->redirect('admin/' . $this->routeBase . '/' . $back);
    }
}

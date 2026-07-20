<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO-2 — Phiếu điều chuyển kho (WH-05).
 * Ghi sổ: xuất kho nguồn tại giá bình quân, nhập kho đích tại CHÍNH giá đó
 * -> bảo toàn giá vốn, tổng tồn không đổi -> KHÔNG sinh bút toán.
 */
class Transfers extends Controller {

    const DT_OUT = 'transfer_out';
    const DT_IN  = 'transfer_in';

    private $__data = [];
    private $__model, $__itemModel, $__stock, $__warehouse, $__part, $__request, $__response;

    private $routeBase = 'transfers';
    private $labelOne  = 'phiếu điều chuyển';
    private $labelMany = 'Điều chuyển kho';
    private $viewDir   = 'admin/transfers';

    function __construct(){
        $this->__model     = $this->model('WarehouseTransfersModel');
        $this->__itemModel = $this->model('WarehouseTransferItemsModel');
        $this->__stock     = $this->model('StocksModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__part      = $this->model('PartsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
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
            'transfer_no'       => $this->__model->nextNo(),
            'from_warehouse_id' => (int) $f['from_warehouse_id'],
            'to_warehouse_id'   => (int) $f['to_warehouse_id'],
            'transfer_date'     => $f['transfer_date'],
            'reason'            => !empty($f['reason']) ? trim($f['reason']) : null,
            'total_value'       => 0,
            'status'            => 0,
            'created_by'        => Session::get('dataUser'),
        ]);
        $this->__itemModel->syncForTransfer($id, $this->buildLines());
        Session::flash('msg', 'Đã lập ' . $this->labelOne . ' (nháp). Kiểm tra rồi bấm "Ghi sổ".');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Phiếu ' . $item['transfer_no'];
        $this->baseData(); $this->formData();
        $this->__data['content']['page_name'] = 'Phiếu ' . $item['transfer_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__itemModel->getByTransfer($id);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['errors']    = Session::flash('errors');
        $this->__data['content']['old']       = Session::flash('old');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function postEdit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if ((int) $item['status'] === 1){ Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi sửa.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }
        $errors = $this->validate();
        if (!empty($errors)){ $this->flash($errors, 'edit/' . $id); return; }
        $f = $this->__request->getFields();
        $this->__model->edit([
            'from_warehouse_id' => (int) $f['from_warehouse_id'],
            'to_warehouse_id'   => (int) $f['to_warehouse_id'],
            'transfer_date'     => $f['transfer_date'],
            'reason'            => !empty($f['reason']) ? trim($f['reason']) : null,
        ], $id);
        $this->__itemModel->syncForTransfer($id, $this->buildLines());
        Session::flash('msg', 'Cập nhật ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** GHI SỔ: xuất kho nguồn -> nhập kho đích tại cùng giá vốn */
    public function post($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        if ((int) $item['status'] === 1){ Session::flash('msgError', 'Phiếu đã ghi sổ.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $items = $this->__itemModel->getByTransfer($id);
        if (empty($items)){ Session::flash('msgError', 'Phiếu chưa có dòng hàng.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $fromWh = (int) $item['from_warehouse_id'];
        $toWh   = (int) $item['to_warehouse_id'];

        // Chặn tồn không đủ ở kho nguồn
        $need = [];
        foreach ($items as $it){ $need[(int) $it['part_id']] = ($need[(int) $it['part_id']] ?? 0) + (float) $it['quantity']; }
        $short = [];
        foreach ($need as $partId => $qty){
            if ($this->__stock->available($fromWh, $partId) + 1e-9 < $qty){
                $p = $this->__part->getDetail($partId);
                $short[] = ($p ? $p['code'] . ' - ' . $p['name'] : ('#' . $partId));
            }
        }
        if (!empty($short)){ Session::flash('msgError', 'Tồn kho nguồn không đủ: ' . implode('; ', $short)); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $date = $item['transfer_date']; $no = $item['transfer_no'];
        $this->__model->transaction(function($db) use ($id, $items, $fromWh, $toWh, $date, $no){
            $total = 0.0;
            foreach ($items as $it){
                $pid = (int) $it['part_id']; $qty = (float) $it['quantity'];
                $avg = $this->__stock->applyOut($fromWh, $pid, $qty, self::DT_OUT, $id, $no, $date, $it['note']);
                $this->__stock->applyIn($toWh, $pid, $qty, $avg, self::DT_IN, $id, $no, $date, $it['note']);
                $amt = round($qty * $avg, 2);
                $this->__itemModel->setCost((int) $it['id'], $avg, $amt);
                $total += $amt;
            }
            $this->__model->edit(['status' => 1, 'total_value' => $total], $id);
        });
        Session::flash('msg', 'Đã ghi sổ ' . $no . ' — chuyển tồn giữa kho.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    /** HUỶ GHI SỔ: đảo cả xuất nguồn + nhập đích (chỉ khi là phát sinh cuối) */
    public function unpost($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        if ((int) $item['status'] !== 1){ Session::flash('msgError', 'Phiếu chưa ghi sổ.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $items = $this->__itemModel->getByTransfer($id);
        $fromWh = (int) $item['from_warehouse_id']; $toWh = (int) $item['to_warehouse_id'];
        $blocked = [];
        foreach ($items as $it){
            $pid = (int) $it['part_id'];
            if (!$this->__stock->isLastMovement($fromWh, $pid, self::DT_OUT, $id) || !$this->__stock->isLastMovement($toWh, $pid, self::DT_IN, $id)){
                $blocked[] = $it['part_code'] . ' - ' . $it['part_name'];
            }
        }
        if (!empty($blocked)){ Session::flash('msgError', 'Không huỷ được: đã có phát sinh sau ở — ' . implode('; ', $blocked)); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $this->__model->transaction(function($db) use ($id, $items, $fromWh, $toWh){
            foreach ($items as $it){
                $pid = (int) $it['part_id'];
                $this->__stock->reverseDoc($toWh, $pid, self::DT_IN, $id);
                $this->__stock->reverseDoc($fromWh, $pid, self::DT_OUT, $id);
                $this->__itemModel->setCost((int) $it['id'], 0, 0);
            }
            $this->__model->edit(['status' => 0, 'total_value' => 0], $id);
        });
        Session::flash('msg', 'Đã huỷ ghi sổ ' . $item['transfer_no'] . ' — hoàn tồn về kho nguồn.');
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function delete($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if ((int) $item['status'] === 1){ Session::flash('msgError', 'Phiếu đã ghi sổ — huỷ ghi sổ trước khi xoá.'); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Helper =====
    private function validate(){
        $f = $this->__request->getFields();
        $errors = [];
        $fromW = !empty($f['from_warehouse_id']) ? (int) $f['from_warehouse_id'] : 0;
        $toW   = !empty($f['to_warehouse_id']) ? (int) $f['to_warehouse_id'] : 0;
        if ($fromW <= 0 || empty($this->__warehouse->getDetail($fromW))) $errors['from_warehouse_id'] = 'Chọn kho nguồn';
        if ($toW <= 0 || empty($this->__warehouse->getDetail($toW)))     $errors['to_warehouse_id'] = 'Chọn kho đích';
        if ($fromW > 0 && $fromW === $toW) $errors['to_warehouse_id'] = 'Kho đích phải khác kho nguồn';
        if (empty($f['transfer_date'])) $errors['transfer_date'] = 'Chọn ngày';
        if (empty($this->buildLines())) $errors['lines'] = 'Phiếu phải có ít nhất 1 dòng hàng';
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
            $lines[] = ['part_id' => $partId, 'quantity' => $qty, 'note' => isset($notes[$i]) ? trim($notes[$i]) : ''];
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

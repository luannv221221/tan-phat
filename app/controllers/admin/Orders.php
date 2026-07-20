<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** STOREFRONT (admin) — Quản lý đơn hàng. */
class Orders extends Controller {

    private $__data = [];
    private $__model, $__itemModel, $__inv, $__invItem, $__warehouse, $__reservation, $__request, $__response;
    private $routeBase = 'orders';
    private $labelOne  = 'đơn hàng';
    private $labelMany = 'Đơn hàng';
    private $viewDir   = 'admin/orders';

    function __construct(){
        $this->__model     = $this->model('OrdersModel');
        $this->__itemModel = $this->model('OrderItemsModel');
        $this->__inv       = $this->model('SalesInvoicesModel');
        $this->__invItem   = $this->model('SalesInvoiceItemsModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__reservation = $this->model('StockReservationsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $this->__data['content']['routeBase'] = $this->routeBase;
        $this->__data['content']['labelOne']  = $this->labelOne;
        $this->__data['content']['statuses']  = OrdersModel::$statuses;
        $this->__data['content']['payments']  = OrdersModel::$payments;
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/lists';
        $this->__data['page_title']  = $this->labelMany;
        $this->baseData();
        $f = $this->__request->getFields();
        $status  = isset($f['status']) && isset(OrdersModel::$statuses[$f['status']]) ? $f['status'] : '';
        $keyword = isset($f['q']) ? trim($f['q']) : '';
        $this->__data['content']['page_name']     = $this->labelMany;
        $this->__data['content']['dataList']      = $this->__model->getLists($status, $keyword);
        $this->__data['content']['newCount']      = $this->__model->countNew();
        $this->__data['content']['filterStatus']  = $status;
        $this->__data['content']['filterKeyword'] = $keyword;
        $this->__data['content']['msg']           = Session::flash('msg');
        $this->__data['content']['msgError']      = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function edit($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__data['sub_content'] = $this->viewDir . '/edit';
        $this->__data['page_title']  = 'Đơn ' . $item['order_no'];
        $this->baseData();
        $this->__data['content']['page_name'] = 'Đơn ' . $item['order_no'];
        $this->__data['content']['item']      = $item;
        $this->__data['content']['items']     = $this->__itemModel->getByOrder($id);
        $this->__data['content']['invoice']   = !empty($item['sales_invoice_id']) ? $this->__inv->getDetail($item['sales_invoice_id']) : null;
        $this->__data['content']['reserving'] = $this->__reservation->hasForOrder($id);
        $this->__data['content']['msg']       = Session::flash('msg');
        $this->__data['content']['msgError']  = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /** Tạo hoá đơn bán (nháp) từ đơn hàng -> admin duyệt & ghi sổ (trừ tồn + KT-6) */
    public function invoice($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        if (!empty($item['sales_invoice_id'])){
            Session::flash('msg', 'Đơn đã có hoá đơn.');
            $this->__response->redirect('admin/sales-invoices/edit/' . $item['sales_invoice_id']); return;
        }
        if (!route('admin/sales-invoices/add')){
            Session::flash('msgError', 'Bạn không có quyền tạo hoá đơn.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
        }

        $wh = $this->__warehouse->getDefault();
        if (empty($wh)){ Session::flash('msgError', 'Chưa có kho để xuất hàng.'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $lines = [];
        foreach ($this->__itemModel->getByOrder($id) as $it){
            if (empty($it['part_id'])) continue; // SP đã xoá -> bỏ qua
            $lines[] = ['part_id' => (int) $it['part_id'], 'quantity' => (float) $it['quantity'],
                        'unit_price' => (float) $it['unit_price'], 'note' => ''];
        }
        if (empty($lines)){ Session::flash('msgError', 'Đơn không có dòng hàng hợp lệ (sản phẩm đã xoá).'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }

        $invId = $this->__inv->add([
            'invoice_no'    => $this->__inv->nextNo(),
            'customer_id'   => null,
            'customer_name' => $item['customer_name'],
            'warehouse_id'  => (int) $wh['id'],
            'invoice_date'  => date('Y-m-d'),
            'vat_rate'      => 0, // giá web đã là giá bán cuối
            'subtotal'      => 0, 'tax_amount' => 0, 'total_amount' => 0, 'cost_amount' => 0,
            'status'        => 0,
            'note'          => 'Từ đơn hàng ' . $item['order_no'],
            'created_by'    => Session::get('dataUser'),
        ]);
        $subtotal = $this->__invItem->syncForInvoice($invId, $lines);
        $this->__inv->edit(['subtotal' => $subtotal, 'tax_amount' => 0, 'total_amount' => $subtotal], $invId);
        $this->__model->edit(['sales_invoice_id' => $invId], $id);

        // Nhả giữ tồn: từ đây hàng do hoá đơn quản (ghi sổ hoá đơn sẽ trừ tồn thật)
        $this->__reservation->releaseForOrder($id);

        Session::flash('msg', 'Đã tạo hoá đơn nháp từ đơn ' . $item['order_no'] . '. Kiểm tra rồi "Ghi sổ" để trừ tồn & ghi doanh thu.');
        $this->__response->redirect('admin/sales-invoices/edit/' . $invId);
    }

    /** Cập nhật trạng thái đơn */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }
        $f = $this->__request->getFields();
        $st = isset($f['status']) ? $f['status'] : '';
        if (!isset(OrdersModel::$statuses[$st])){ Session::flash('msgError', 'Trạng thái không hợp lệ'); $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return; }
        $this->__model->edit(['status' => $st], $id);
        // Huỷ đơn -> nhả giữ tồn
        if ($st === 'cancelled') $this->__reservation->releaseForOrder($id);
        Session::flash('msg', 'Đã chuyển trạng thái: ' . OrdersModel::$statuses[$st]);
        $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id);
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

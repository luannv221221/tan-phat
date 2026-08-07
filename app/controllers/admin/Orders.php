<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/** STOREFRONT (admin) — Quản lý đơn hàng. */
class Orders extends Controller {

    private $__data = [];
    private $__model, $__itemModel, $__inv, $__invItem, $__warehouse, $__reservation, $__request, $__response;
    private $__stock, $__part;

    /** Loại chứng từ ghi lên thẻ kho — để truy vết phát sinh từ đơn web */
    const DOC_OUT = 'order';
    const DOC_IN  = 'order_return';

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
        $this->__stock     = $this->model('StocksModel');
        $this->__part      = $this->model('PartsModel');
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
        $wh = !empty($item['warehouse_id']) ? $this->__warehouse->getDetail((int) $item['warehouse_id']) : null;
        $this->__data['content']['warehouseName'] = !empty($wh) ? $wh['name'] : '';
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
        // Đơn đã trừ kho theo trạng thái rồi; ghi sổ hoá đơn sẽ trừ THÊM một lần
        // nữa vì hoá đơn không biết gì về đơn. Chặn ngay từ lúc tạo.
        if ((int) ($item['stock_applied'] ?? 0) === 1){
            Session::flash('msgError', 'Đơn đã trừ kho theo trạng thái "Hoàn thành" nên không xuất hoá đơn kho được nữa. '
                . 'Muốn đi đường hoá đơn thì chuyển đơn về "Hoàn hàng" để cộng tồn lại trước.');
            $this->__response->redirect('admin/' . $this->routeBase . '/edit/' . $id); return;
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

    /**
     * Cập nhật trạng thái đơn — và đây cũng là nơi ĐƠN KHÁCH ĐẶT động vào kho.
     *
     * Chốt 04/08/2026, hai luồng tách bạch:
     *   - Hoá đơn bán tại quầy : trừ kho ngay lúc ghi sổ (Salesinvoices::post)
     *   - Đơn khách đặt trên web: "Hoàn thành" mới trừ, "Hoàn hàng" thì cộng lại
     *
     * Cờ `stock_applied` là NGUỒN SỰ THẬT về "đơn này đã trừ kho chưa", không
     * suy ra từ trạng thái. Vì trạng thái bấm qua bấm lại được, suy từ trạng
     * thái là trừ trùng.
     */
    public function setStatus($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){ $this->__response->redirect('admin/khong-co-quyen'); return; }

        $back = 'admin/' . $this->routeBase . '/edit/' . $id;
        $f  = $this->__request->getFields();
        $st = isset($f['status']) ? $f['status'] : '';
        if (!isset(OrdersModel::$statuses[$st])){
            Session::flash('msgError', 'Trạng thái không hợp lệ');
            $this->__response->redirect($back); return;
        }

        $applied = (int) ($item['stock_applied'] ?? 0) === 1;

        if ($st === 'completed' && !$applied){
            if (!$this->applyStockForOrder($id, $item, $back)) return;   // tự flash + redirect khi lỗi
        } elseif ($st === 'returned'){
            if (!$applied){
                // Đơn chưa từng trừ kho mà cộng lại là ĐẺ RA HÀNG KHÔNG CÓ THẬT.
                // Gồm cả đơn đã xuất hoá đơn: hoàn loại đó phải huỷ ghi sổ hoá đơn.
                Session::flash('msgError', 'Đơn này chưa trừ kho nên không hoàn hàng được.'
                    . (!empty($item['sales_invoice_id']) ? ' Đơn đã xuất hoá đơn — huỷ ghi sổ hoá đơn để hoàn tồn.' : ''));
                $this->__response->redirect($back); return;
            }
            $this->returnStockForOrder($id, $item);
        } elseif ($st === 'cancelled' && $applied){
            Session::flash('msgError', 'Đơn đã trừ kho rồi — chuyển "Hoàn hàng" để cộng tồn lại, sau đó mới huỷ.');
            $this->__response->redirect($back); return;
        } else {
            $this->__model->edit(['status' => $st], $id);
            if ($st === 'cancelled' || $st === 'completed') $this->__reservation->releaseForOrder($id);
        }

        Session::flash('msg', 'Đã chuyển trạng thái: ' . OrdersModel::$statuses[$st]);
        $this->__response->redirect($back);
    }

    /**
     * TRỪ KHO cho đơn khi chuyển "Hoàn thành".
     * @return bool false nếu đã flash lỗi và redirect (caller phải return ngay)
     */
    private function applyStockForOrder($id, $item, $back){
        // Đơn đã xuất hoá đơn và hoá đơn đã ghi sổ -> hoá đơn trừ rồi, không trừ nữa.
        if (!empty($item['sales_invoice_id'])){
            $inv = $this->__inv->getDetail((int) $item['sales_invoice_id']);
            if (!empty($inv) && (int) $inv['status'] === 1){
                $this->__model->edit(['status' => 'completed'], $id);
                $this->__reservation->releaseForOrder($id);
                Session::flash('msg', 'Đã hoàn thành. Tồn kho do hoá đơn ' . $inv['invoice_no'] . ' trừ, đơn không trừ thêm.');
                $this->__response->redirect($back);
                return false;
            }
        }

        $items = $this->__itemModel->getByOrder($id);
        $lines = [];
        foreach ($items as $it){
            if (empty($it['part_id']) || (float) $it['quantity'] <= 0) continue;
            $lines[] = $it;
        }
        if (empty($lines)){
            Session::flash('msgError', 'Đơn không có dòng hàng hợp lệ để trừ kho.');
            $this->__response->redirect($back); return false;
        }

        $wh = $this->__warehouse->getDefault();
        if (empty($wh)){
            Session::flash('msgError', 'Chưa cấu hình kho — không trừ tồn được.');
            $this->__response->redirect($back); return false;
        }
        $whId = (int) $wh['id'];

        // Gộp theo mã hàng TRƯỚC khi so tồn: đơn có 2 dòng cùng mã, mỗi dòng 10,
        // tồn 15 mà so từng dòng thì cả hai đều lọt rồi tồn xuống âm.
        $need = [];
        foreach ($lines as $it){ $need[(int) $it['part_id']] = ($need[(int) $it['part_id']] ?? 0) + (float) $it['quantity']; }
        $short = [];
        foreach ($need as $pid => $qty){
            $have = $this->__stock->available($whId, $pid);
            if ($have + 1e-9 < $qty){
                $short[] = $this->fmtShort($pid, $have, $qty);
            }
        }
        if (!empty($short)){
            Session::flash('msgError', 'Tồn không đủ để hoàn thành đơn: ' . implode('; ', $short));
            $this->__response->redirect($back); return false;
        }

        $no   = $item['order_no'];
        $date = date('Y-m-d');
        $this->__model->transaction(function($db) use ($id, $lines, $whId, $no, $date){
            $cost = 0.0;
            foreach ($lines as $it){
                $qty = (float) $it['quantity'];
                $avg = $this->__stock->applyOut($whId, (int) $it['part_id'], $qty,
                    self::DOC_OUT, $id, $no, $date, 'Đơn hàng ' . $no);
                $amt = round($qty * $avg, 2);
                $this->__itemModel->setCost((int) $it['id'], $avg, $amt);
                $cost += $amt;
            }
            $this->__model->edit([
                'status' => 'completed', 'warehouse_id' => $whId, 'stock_applied' => 1,
            ], $id);
        });

        $this->__reservation->releaseForOrder($id);
        return true;
    }

    /**
     * CỘNG LẠI KHO khi chuyển "Hoàn hàng".
     *
     * Dùng applyIn chứ KHÔNG dùng reverseDoc: hoàn hàng là nghiệp vụ có thật,
     * phải nằm lại trên thẻ kho chứ không được xoá dấu vết. Làm vậy cũng thoát
     * ràng buộc "chỉ huỷ được phát sinh cuối cùng" của reverseDoc.
     *
     * Giá nhập lại = giá vốn ĐÃ CHỐT lúc xuất (order_items.unit_cost).
     */
    private function returnStockForOrder($id, $item){
        $whId = (int) ($item['warehouse_id'] ?: 0);
        if ($whId <= 0){
            $wh = $this->__warehouse->getDefault();
            $whId = !empty($wh) ? (int) $wh['id'] : 0;
        }

        $items = $this->__itemModel->getByOrder($id);
        $no    = $item['order_no'];
        $date  = date('Y-m-d');

        $this->__model->transaction(function($db) use ($id, $items, $whId, $no, $date){
            foreach ($items as $it){
                $qty = (float) $it['quantity'];
                if (empty($it['part_id']) || $qty <= 0) continue;
                $this->__stock->applyIn($whId, (int) $it['part_id'], $qty, (float) $it['unit_cost'],
                    self::DOC_IN, $id, $no, $date, 'Hoàn hàng đơn ' . $no);
            }
            $this->__model->edit(['status' => 'returned', 'stock_applied' => 0], $id);
        });
    }

    /** Mô tả một mã hàng thiếu tồn cho thông báo lỗi */
    private function fmtShort($partId, $have, $need){
        $p = $this->__part->getDetail($partId);
        $n = function($v){ return rtrim(rtrim(number_format($v, 3), '0'), '.'); };
        return ($p ? $p['code'] . ' - ' . $p['name'] : ('#' . $partId))
             . ' (tồn ' . $n($have) . ', cần ' . $n($need) . ')';
    }

    public function delete($id){
        if (empty($this->__model->getDetail($id))){ Session::flash('msgError', 'Không tìm thấy ' . $this->labelOne); $this->__response->redirect('admin/' . $this->routeBase); return; }
        $this->__model->remove($id);
        Session::flash('msg', 'Xoá ' . $this->labelOne . ' thành công');
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

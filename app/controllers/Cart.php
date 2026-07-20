<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * STOREFRONT — Giỏ hàng (session) -> gửi YÊU CẦU BÁO GIÁ (TASK_83/94).
 * Khi gửi, tạo một Báo giá (quotations) trạng thái "Đã gửi" trong phân hệ Bán hàng.
 */
class Cart extends Controller {

    private $__data = [];
    private $__part, $__quote, $__quoteItem, $__member, $__order, $__orderItem, $__reservation, $__settings, $__request, $__response;

    function __construct(){
        $this->__part      = $this->model('PartsModel');
        $this->__quote     = $this->model('QuotationsModel');
        $this->__quoteItem = $this->model('QuotationItemsModel');
        $this->__member    = $this->model('MembersModel');
        $this->__order     = $this->model('OrdersModel');
        $this->__orderItem = $this->model('OrderItemsModel');
        $this->__reservation = $this->model('StockReservationsModel');
        $this->__settings  = $this->model('SettingsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function getCart(){
        $c = Session::get('cart');
        return (!empty($c) && is_array($c)) ? $c : [];
    }

    /** Dựng dòng giỏ hàng kèm thông tin sản phẩm + giá */
    private function buildRows($cart){
        $rows = []; $total = 0.0;
        foreach ($cart as $partId => $qty){
            $p = $this->__part->getDetail((int) $partId);
            if (empty($p)) continue;
            $price = !empty($p['sale_price']) ? (float) $p['sale_price'] : (float) $p['price'];
            $amount = $price * (int) $qty;
            $rows[] = ['part' => $p, 'qty' => (int) $qty, 'price' => $price, 'amount' => $amount];
            $total += $amount;
        }
        return ['rows' => $rows, 'total' => $total];
    }

    public function index(){
        $data = $this->buildRows($this->getCart());

        $this->__data['sub_content'] = 'storefront/cart';
        $this->__data['page_title']  = 'Giỏ hàng';
        $memberId = Session::get('dataMember');
        $c = &$this->__data['content'];
        $c['rows']   = $data['rows'];
        $c['total']  = $data['total'];
        $c['member'] = !empty($memberId) ? $this->__member->getDetail($memberId) : null;
        $c['msg']    = Session::flash('msg');
        $c['errors'] = Session::flash('errors');
        $this->render('layouts/storefront/master', $this->__data);
    }

    public function add(){
        $f = $this->__request->getFields();
        $partId = !empty($f['part_id']) ? (int) $f['part_id'] : 0;
        $qty    = !empty($f['qty']) ? max(1, (int) $f['qty']) : 1;

        if ($partId > 0 && !empty($this->__part->getDetail($partId))){
            $cart = $this->getCart();
            $cart[$partId] = (isset($cart[$partId]) ? (int) $cart[$partId] : 0) + $qty;
            Session::set('cart', $cart);
            Session::flash('msg', 'Đã thêm vào giỏ hàng.');
        } else {
            Session::flash('errors', ['add' => 'Sản phẩm không hợp lệ']);
        }
        $this->__response->redirect('gio-hang');
    }

    public function update(){
        $f = $this->__request->getFields();
        $qtys = isset($f['qty']) && is_array($f['qty']) ? $f['qty'] : [];
        $cart = [];
        foreach ($qtys as $partId => $q){
            $partId = (int) $partId; $q = (int) $q;
            if ($partId > 0 && $q > 0) $cart[$partId] = $q;
        }
        Session::set('cart', $cart);
        Session::flash('msg', 'Đã cập nhật giỏ hàng.');
        $this->__response->redirect('gio-hang');
    }

    public function remove($partId = 0){
        $cart = $this->getCart();
        unset($cart[(int) $partId]);
        Session::set('cart', $cart);
        Session::flash('msg', 'Đã xoá khỏi giỏ hàng.');
        $this->__response->redirect('gio-hang');
    }

    /** Gửi yêu cầu báo giá -> tạo Báo giá (quotations) trạng thái sent */
    public function submit(){
        $cart = $this->getCart();
        $data = $this->buildRows($cart);
        if (empty($data['rows'])){
            Session::flash('errors', ['cart' => 'Giỏ hàng trống']);
            $this->__response->redirect('gio-hang'); return;
        }

        $f = $this->__request->getFields();
        $memberId = Session::get('dataMember');
        $member = !empty($memberId) ? $this->__member->getDetail($memberId) : null;

        $name  = !empty($f['name']) ? trim($f['name']) : ($member['name'] ?? '');
        $phone = !empty($f['phone']) ? trim($f['phone']) : ($member['phone'] ?? '');
        $note  = !empty($f['note']) ? trim($f['note']) : '';
        if ($phone !== '') $note = trim('SĐT: ' . $phone . ($note !== '' ? ' — ' . $note : ''));

        if ($name === ''){
            Session::flash('errors', ['name' => 'Vui lòng nhập họ tên người yêu cầu']);
            $this->__response->redirect('gio-hang'); return;
        }

        $qid = $this->__quote->add([
            'quote_no'      => $this->__quote->nextNo(),
            'customer_id'   => null,
            'customer_name' => $name,
            'quote_date'    => date('Y-m-d'),
            'vat_rate'      => 0,
            'status'        => 'sent',
            'note'          => $note !== '' ? $note : null,
            'created_by'    => null,
        ]);

        $lines = [];
        foreach ($data['rows'] as $r){
            $lines[] = ['part_id' => (int) $r['part']['id'], 'quantity' => $r['qty'],
                        'unit_price' => $r['price'], 'note' => ''];
        }
        $subtotal = $this->__quoteItem->syncForQuotation($qid, $lines);
        $this->__quote->edit(['subtotal' => $subtotal, 'tax_amount' => 0, 'total_amount' => $subtotal], $qid);

        Session::remove('cart');
        $q = $this->__quote->getDetail($qid);
        Session::flash('lastQuoteNo', $q['quote_no']);
        $this->__response->redirect('gio-hang/hoan-tat');
    }

    public function done(){
        $this->__data['sub_content'] = 'storefront/cart_done';
        $this->__data['page_title']  = 'Đã gửi yêu cầu báo giá';
        $this->__data['content']['quoteNo'] = Session::flash('lastQuoteNo');
        $this->render('layouts/storefront/master', $this->__data);
    }

    // ================= ĐẶT HÀNG =================

    public function checkout(){
        $data = $this->buildRows($this->getCart());
        if (empty($data['rows'])){
            Session::flash('errors', ['cart' => 'Giỏ hàng trống']);
            $this->__response->redirect('gio-hang'); return;
        }
        $memberId = Session::get('dataMember');
        $this->__data['sub_content'] = 'storefront/checkout';
        $this->__data['page_title']  = 'Đặt hàng';
        $c = &$this->__data['content'];
        $c['rows']     = $data['rows'];
        $c['total']    = $data['total'];
        $c['member']   = !empty($memberId) ? $this->__member->getDetail($memberId) : null;
        $c['payments'] = OrdersModel::$payments;
        $c['errors']   = Session::flash('errors');
        $c['old']      = Session::flash('old');
        $this->render('layouts/storefront/master', $this->__data);
    }

    public function placeOrder(){
        $data = $this->buildRows($this->getCart());
        if (empty($data['rows'])){
            Session::flash('errors', ['cart' => 'Giỏ hàng trống']);
            $this->__response->redirect('gio-hang'); return;
        }

        $f = $this->__request->getFields();
        $name    = !empty($f['name']) ? trim($f['name']) : '';
        $phone   = !empty($f['phone']) ? trim($f['phone']) : '';
        $address = !empty($f['address']) ? trim($f['address']) : '';
        $pay     = (!empty($f['payment_method']) && isset(OrdersModel::$payments[$f['payment_method']])) ? $f['payment_method'] : 'bank_transfer';

        $errors = [];
        if ($name === '')    $errors['name'] = 'Nhập họ tên';
        if ($phone === '')   $errors['phone'] = 'Nhập số điện thoại';
        if ($address === '') $errors['address'] = 'Nhập địa chỉ nhận hàng';
        if (!empty($errors)){
            Session::flash('errors', $errors);
            Session::flash('old', $f);
            $this->__response->redirect('dat-hang'); return;
        }

        $memberId = Session::get('dataMember');
        $orderId = $this->__order->add([
            'order_no'       => $this->__order->nextNo(),
            'member_id'      => !empty($memberId) ? (int) $memberId : null,
            'customer_name'  => $name,
            'phone'          => $phone,
            'email'          => !empty($f['email']) ? trim($f['email']) : null,
            'address'        => $address,
            'note'           => !empty($f['note']) ? trim($f['note']) : null,
            'payment_method' => $pay,
            'subtotal'       => 0,
            'total_amount'   => 0,
            'status'         => 'new',
        ]);
        $total = $this->__orderItem->syncForOrder($orderId, $data['rows']);
        $this->__order->edit(['subtotal' => $total, 'total_amount' => $total], $orderId);

        // Giữ tồn (đặt trước) theo phụ tùng — chưa trừ tồn thật
        $resv = [];
        foreach ($data['rows'] as $r){
            if (!empty($r['part']['id'])) $resv[] = ['part_id' => (int) $r['part']['id'], 'quantity' => (float) $r['qty']];
        }
        if (!empty($resv)) $this->__reservation->reserveForOrder($orderId, $resv);

        Session::remove('cart');
        $order = $this->__order->getDetail($orderId);
        Session::flash('lastOrderNo', $order['order_no']);
        Session::flash('lastOrderPay', $pay);
        Session::flash('lastOrderTotal', $total);
        $this->__response->redirect('dat-hang/hoan-tat');
    }

    public function orderDone(){
        $this->__data['sub_content'] = 'storefront/order_done';
        $this->__data['page_title']  = 'Đặt hàng thành công';
        $c = &$this->__data['content'];
        $c['orderNo'] = Session::flash('lastOrderNo');
        $c['pay']     = Session::flash('lastOrderPay');
        $c['total']   = Session::flash('lastOrderTotal');
        $c['settings']= $this->__settings->map();
        $this->render('layouts/storefront/master', $this->__data);
    }
}

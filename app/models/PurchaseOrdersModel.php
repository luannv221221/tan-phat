<?php

use App\core\Model;
use App\core\Load;

/**
 * ĐƠN MUA — gara mua hàng của nhà cung cấp (tài liệu 29/10/2026).
 *
 * ĐÂY KHÔNG PHẢI PHIẾU NHẬP KHO. Nó là bước TRƯỚC đó: ghi lại mình đã đặt /
 * đã mua những gì của ai, kèm serial từng chiếc và hoá đơn của nhà cung cấp.
 * Hàng về tới nơi thì bấm một nút để sinh phiếu nhập kho — lúc đó tồn mới đổi.
 *
 * Nên hệ thống có HAI ĐƯỜNG NHẬP KHO, đúng như tài liệu nói:
 *     đơn mua -> phiếu nhập kho        (có serial, có hoá đơn NCC)
 *     lập thẳng phiếu nhập kho          (cách đang dùng, giữ nguyên)
 *
 * ĐƠN MUA KHÔNG CÓ GIÁ — tài liệu ghi "không cần giá nhập". Giá chỉ xuất hiện
 * lúc chuyển thành phiếu nhập, lấy giá nhập gần nhất của mặt hàng rồi cho sửa.
 */
class PurchaseOrdersModel extends Model {

    protected $_table    = 'purchase_orders';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;   // số đơn đánh riêng trong mỗi gara

    public static $trangThai = [
        0 => 'Nháp',
        1 => 'Đã chốt',
    ];

    public function getLists(array $loc = []){
        $q = $this->bangGara()
            ->select('`purchase_orders`.*, `partners`.`name` AS partner_full, '
                   . '`goods_receipts`.`receipt_no` AS pnk_no')
            ->leftJoinOn('partners', 'purchase_orders.partner_id', 'partners.id')
            ->leftJoinOn('goods_receipts', 'purchase_orders.goods_receipt_id', 'goods_receipts.id');

        if (isset($loc['status']) && $loc['status'] !== ''){
            $q = $q->where('purchase_orders.status', '=', (int) $loc['status']);
        }
        if (!empty($loc['tu']))  $q = $q->where('purchase_orders.order_date', '>=', $loc['tu']);
        if (!empty($loc['den'])) $q = $q->where('purchase_orders.order_date', '<=', $loc['den']);

        return $q->orderBy('purchase_orders.order_date', 'DESC')
                 ->orderBy('purchase_orders.id', 'DESC')->get();
    }

    public function getDetail($id){
        $r = $this->bangGara()
            ->select('`purchase_orders`.*, `partners`.`name` AS partner_full, '
                   . '`goods_receipts`.`receipt_no` AS pnk_no')
            ->leftJoinOn('partners', 'purchase_orders.partner_id', 'partners.id')
            ->leftJoinOn('goods_receipts', 'purchase_orders.goods_receipt_id', 'goods_receipts.id')
            ->where('purchase_orders.id', '=', (int) $id)->first();
        return !empty($r) ? $r : null;
    }

    /**
     * Số đơn tiếp theo, ĐẾM TRONG GARA NÀY.
     *
     * Chỉ mục duy nhất của bảng là (garage_id, po_no) nên hai gara cùng có
     * DM-000001 là bình thường. Xem migration 000102 và lỗi đã gặp thật ở
     * `orders` (000099) nếu định đổi một trong hai vế.
     */
    public function nextNo(){
        $row = $this->bangGara()->select('`po_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['po_no'], $m)) $n = (int) $m[1];
        return 'DM-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        /* ÉP INT: lastInsertId() cua PDO tra ve CHUOI */
        return (int) $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    /** Xoá được khi CHƯA sinh phiếu nhập kho — dòng hàng và serial đi theo (CASCADE) */
    public function remove($id){
        $d = $this->getDetail($id);
        if (empty($d) || !empty($d['goods_receipt_id'])) return false;
        return $this->deleteById($id);
    }

    /**
     * GIÁ NHẬP GẦN NHẤT của một mặt hàng trong gara này — dùng khi chuyển đơn
     * mua thành phiếu nhập kho.
     *
     * Đơn mua không mang giá (tài liệu: "không cần giá nhập"), nhưng phiếu nhập
     * thì cần: `goods_receipt_items`.`unit_cost` là cái cả bộ máy kho dùng để
     * tính giá vốn. Để 0 thì hàng nhập đường này có giá vốn 0, bán ra là báo
     * cáo lãi gộp thổi lên bằng đúng doanh thu — sai mà nhìn rất đẹp.
     *
     * Lấy phiếu nhập ĐÃ GHI SỔ gần nhất có mặt hàng đó. Chưa từng nhập bao giờ
     * thì rơi về `parts`.`price`; vẫn không có thì 0 và người dùng tự điền.
     */
    public function giaNhapGanNhat($partId){
        /* dkGara('gr') CHỨ KHÔNG PHẢI dkGara('goods_receipts'): câu dưới đặt bí
           danh `gr` cho bảng, nên điều kiện cũng phải mang bí danh đó —
           `goods_receipts`.`garage_id` là tên MySQL không còn biết tới. */
        list($dk, $bd) = $this->dkGara('gr');
        $bd[] = (int) $partId;
        $r = $this->firstRaw(
            'SELECT `gri`.`unit_cost`
               FROM `goods_receipt_items` `gri`
               JOIN `goods_receipts` `gr` ON `gr`.`id` = `gri`.`receipt_id`
              WHERE ' . $dk . ' AND `gr`.`status` = 1 AND `gri`.`part_id` = ?
                AND `gri`.`unit_cost` > 0
              ORDER BY `gr`.`receipt_date` DESC, `gr`.`id` DESC
              LIMIT 1', $bd);
        if (!empty($r['unit_cost'])) return (float) $r['unit_cost'];

        $p = Load::model('PartsModel')->getFirst((int) $partId);
        return !empty($p['price']) ? (float) $p['price'] : 0.0;
    }

    /** Số đơn mua đang trỏ vào một NCC — còn thì không xoá NCC đó */
    public function demTheoNcc($partnerId){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `purchase_orders` WHERE `partner_id` = ?',
                             [(int) $partnerId]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }
}

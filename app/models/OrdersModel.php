<?php

use App\core\Model;

/** STOREFRONT — Đơn hàng. */
class OrdersModel extends Model {

    protected $_table   = 'orders';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Mã trạng thái giữ nguyên như cũ (chỉ đổi nhãn hiển thị) để không phải
     * chuyển đổi dữ liệu đơn đang có. 'returned' là mã mới.
     *
     * Hai trạng thái có ĐỘNG TỚI KHO — xem Orders::setStatus():
     *   completed -> trừ kho     |     returned -> cộng lại kho
     */
    public static $statuses = [
        'new'       => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã huỷ',
        'returned'  => 'Hoàn hàng',
    ];
    public static $payments = ['bank_transfer' => 'Chuyển khoản', 'cod' => 'Thanh toán khi nhận hàng (COD)'];

    public function getLists($status = '', $keyword = ''){
        $q = $this->table($this->_table)->select('*');
        if ($status !== '' && isset(self::$statuses[$status])) $q = $q->where('status', '=', $status);
        if ($keyword !== ''){
            $q = $q->where(function($sub) use ($keyword){
                $like = '%' . $keyword . '%';
                $sub->whereLike('order_no', $like);
                $sub->whereOrLike('customer_name', $like);
                $sub->whereOrLike('phone', $like);
            });
        }
        return $q->orderBy('id', 'DESC')->get();
    }

    public function countNew(){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->where('status', '=', 'new')->first();
        return (int) ($r['c'] ?? 0);
    }

    /**
     * Số đơn và tổng tiền theo từng trạng thái trong một khoảng thời gian.
     *
     * Dùng cho trang Tổng quan. Trả về:
     *   ['completed' => ['count' => 3, 'sum' => 1250000.0], ...]
     * Trạng thái không có đơn nào thì không xuất hiện trong mảng — bên gọi
     * phải tự coi như 0 (xem Dashboard::bucket()).
     *
     * @param string $from 'Y-m-d H:i:s' — tính từ (>=)
     * @param string $to   'Y-m-d H:i:s' — đến trước (<)
     */
    public function statsByStatus($from, $to){
        $rows = $this->getRaw(
            'SELECT `status`, COUNT(*) AS c, COALESCE(SUM(`total_amount`), 0) AS s
               FROM `orders`
              WHERE `create_at` >= ? AND `create_at` < ?
              GROUP BY `status`',
            [$from, $to]
        );

        $out = [];
        foreach ((array) $rows as $r){
            $out[$r['status']] = ['count' => (int) $r['c'], 'sum' => (float) $r['s']];
        }
        return $out;
    }

    public function getDetail($id){ return $this->getFirst($id); }

    public function nextNo(){
        $row = $this->table($this->_table)->select('`order_no`')->orderBy('id', 'DESC')->first();
        $n = 0;
        if (!empty($row) && preg_match('/(\d+)$/', $row['order_no'], $m)){ $n = (int) $m[1]; }
        return 'DH-' . str_pad($n + 1, 6, '0', STR_PAD_LEFT);
    }

    public function add($data){ $data['create_at'] = date('Y-m-d H:i:s'); $this->addNew($data); return $this->lastId(); }
    public function edit($data, $id){ $data['update_at'] = date('Y-m-d H:i:s'); return $this->updateById($data, $id); }
    public function remove($id){ return $this->deleteById($id); }
}

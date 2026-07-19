<?php

use App\core\Model;

/** STOREFRONT — Đơn hàng. */
class OrdersModel extends Model {

    protected $_table   = 'orders';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $statuses = [
        'new'       => 'Mới',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã huỷ',
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

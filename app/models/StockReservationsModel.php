<?php

use App\core\Model;

/**
 * ĐẶT HÀNG — Tồn đang giữ (reservation) theo đơn hàng.
 */
class StockReservationsModel extends Model {

    protected $_table   = 'stock_reservations';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Giữ tồn cho 1 đơn. $lines: mỗi phần tử có part_id + quantity (>0).
     * Gộp cùng phụ tùng để 1 dòng/1 phụ tùng.
     */
    public function reserveForOrder($orderId, array $lines){
        $orderId = (int) $orderId;
        $agg = [];
        foreach ($lines as $ln){
            $pid = isset($ln['part_id']) ? (int) $ln['part_id'] : 0;
            $qty = isset($ln['quantity']) ? (float) $ln['quantity'] : 0;
            if ($pid <= 0 || $qty <= 0) continue;
            $agg[$pid] = ($agg[$pid] ?? 0) + $qty;
        }
        $now = date('Y-m-d H:i:s');
        foreach ($agg as $pid => $qty){
            $this->insert('stock_reservations', [
                'order_id' => $orderId, 'part_id' => $pid, 'quantity' => $qty, 'create_at' => $now,
            ]);
        }
    }

    /** Nhả toàn bộ giữ tồn của 1 đơn */
    public function releaseForOrder($orderId){
        return $this->delete('stock_reservations', '`order_id` = ?', [(int) $orderId]);
    }

    public function hasForOrder($orderId){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->where('order_id', '=', (int) $orderId)->first();
        return (int) ($r['c'] ?? 0) > 0;
    }

    /** Tổng đang giữ của 1 phụ tùng (trên mọi đơn chưa nhả) */
    public function totalReserved($partId){
        $r = $this->table($this->_table)->select('SUM(`quantity`) AS total')
                  ->where('part_id', '=', (int) $partId)->first();
        return (float) ($r['total'] ?? 0);
    }
}

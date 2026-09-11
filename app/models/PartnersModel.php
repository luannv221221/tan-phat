<?php

use App\core\Model;

/**
 * KT-4 — Đối tượng: khách hàng + nhà cung cấp (DÙNG CHUNG toàn hệ thống).
 */
class PartnersModel extends Model {

    protected $_table   = 'partners';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public static $types = [
        'customer' => 'Khách hàng',
        'supplier' => 'Nhà cung cấp',
        'both'     => 'Cả hai',
    ];

    /**
     * Danh sách đối tượng, có lọc.
     *
     * @param array $loc [
     *   'q'      => tìm theo mã / tên / SĐT / MST,
     *   'type'   => 'customer' | 'supplier' | 'both' | '',
     *   'group'  => id nhóm khách | 'none' (chưa xếp nhóm) | '',
     *   'status' => '1' | '0' | '',
     * ]
     * Không truyền gì = như cũ, lấy hết (giữ tương thích cho nơi gọi cũ).
     *
     * LOẠI "CẢ HAI" PHẢI HIỆN Ở CẢ HAI BỘ LỌC. Lọc "Khách hàng" mà so bằng
     * `type = 'customer'` thì đối tác vừa mua vừa bán (type = 'both') biến mất
     * khỏi danh sách khách — và cũng biến mất khỏi danh sách NCC. Họ không
     * thuộc danh sách nào dù là cả hai. Nên "Khách hàng" = customer + both,
     * "Nhà cung cấp" = supplier + both; chỉ chọn đích danh "Cả hai" mới ra
     * riêng nhóm đó.
     */
    public function getLists(array $loc = []){
        $q = $this->table($this->_table);

        $tu = isset($loc['q']) ? trim((string) $loc['q']) : '';
        if ($tu !== ''){
            $q = $q->where(function($sub) use ($tu){
                $like = '%' . $tu . '%';
                $sub->whereLike('code', $like);
                $sub->whereOrLike('name', $like);
                $sub->whereOrLike('phone', $like);
                $sub->whereOrLike('tax_code', $like);
            });
        }

        $loai = isset($loc['type']) ? (string) $loc['type'] : '';
        if ($loai === 'customer')     $q = $q->whereIn('type', ['customer', 'both']);
        elseif ($loai === 'supplier') $q = $q->whereIn('type', ['supplier', 'both']);
        elseif ($loai === 'both')     $q = $q->where('type', '=', 'both');

        $nhom = isset($loc['group']) ? (string) $loc['group'] : '';
        if ($nhom === 'none')            $q = $q->whereNull('group_id');
        elseif ((int) $nhom > 0)         $q = $q->where('group_id', '=', (int) $nhom);

        $tt = isset($loc['status']) ? (string) $loc['status'] : '';
        if ($tt === '1' || $tt === '0')  $q = $q->where('status', '=', (int) $tt);

        return $q->orderBy('sort_order', 'ASC')
                 ->orderBy('name', 'ASC')
                 ->get();
    }

    /** Tổng số đối tượng, không lọc — để hiện "đang xem 3 / 6" */
    public function demTatCa(){
        $r = $this->table($this->_table)->select('COUNT(*) AS c')->first();
        return !empty($r['c']) ? (int) $r['c'] : 0;
    }

    /** Đang hoạt động — cho dropdown chọn đối tượng trên phiếu */
    public function getActive(){
        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** [partner_id => % chiết khấu nhóm KH] — cho auto-điền chiết khấu dòng */
    public function groupDiscountMap(){
        $rows = $this->table($this->_table)
            ->select('`partners`.`id`, `customer_groups`.`discount_percent`')
            ->joinOn('customer_groups', 'partners.group_id', 'customer_groups.id')
            ->get();
        $map = [];
        foreach ($rows ?: [] as $r){ $map[(int) $r['id']] = (float) $r['discount_percent']; }
        return $map;
    }

    public function findByCode($code){
        return $this->table($this->_table)->where('code', '=', $code)->first();
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){
        // acc_vouchers.partner_id ON DELETE SET NULL -> xoá an toàn (phiếu giữ partner_name).
        return $this->deleteById($id);
    }
}

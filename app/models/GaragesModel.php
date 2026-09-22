<?php

use App\core\Model;

/**
 * GARA — đơn vị kinh doanh. Kho, nhân viên, báo giá, hoá đơn đều thuộc về một gara.
 *
 * Một dòng được đánh dấu `is_master` = gara tổng, chủ sở hữu danh mục tổng.
 */
class GaragesModel extends Model {

    protected $_table   = 'garages';
    protected $_fields  = '*';
    protected $_primary = 'id';

    public function getLists(){
        return $this->table($this->_table)
                    ->orderBy('is_master', 'DESC')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Đang hoạt động — cho ô chọn gara */
    public function getActive(){
        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('is_master', 'DESC')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Gara tổng (hoặc gara đầu tiên đang bật, nếu chưa ai đánh dấu) */
    public function getMaster(){
        $r = $this->table($this->_table)
                  ->where('status', '=', 1)
                  ->where('is_master', '=', 1)
                  ->first();
        if (!empty($r)) return $r;

        return $this->table($this->_table)
                    ->where('status', '=', 1)
                    ->orderBy('id', 'ASC')->first();
    }

    public function getDetail($id){ return $this->getFirst($id); }

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

    /** Bỏ cờ gara tổng ở mọi gara khác — chỉ được có MỘT gara tổng */
    public function clearMasterExcept($id){
        return $this->update('garages', ['is_master' => 0], '`id` != ?', [(int) $id]);
    }

    public function remove($id){
        return $this->deleteById($id);
    }

    /**
     * Gara này còn dữ liệu gì — để báo cho người dùng trước khi xoá.
     *
     * Khoá ngoại của các bảng riêng gara là RESTRICT (000065, 000076): MySQL
     * sẽ từ chối lệnh xoá, nhưng người dùng chỉ thấy một lỗi CSDL khó hiểu.
     * Đếm ở đây để nói thẳng "gara này còn N đối tượng, M phiếu nhập...".
     * Bảng chưa có cột `garage_id` (CSDL chưa migrate) thì bỏ qua.
     */
    public function dangDungODau($id){
        $id  = (int) $id;
        $ket = [];
        foreach ([
            'warehouses'          => 'kho',
            'users'               => 'người dùng',
            'partners'            => 'đối tượng',
            'customer_groups'     => 'nhóm khách',
            'vehicles'            => 'xe',
            'receptions'          => 'phiếu tiếp nhận',
            'quotations'          => 'báo giá',
            'sales_invoices'      => 'hoá đơn',
            'warranty_requests'   => 'phiếu bảo hành / bảo trì',
            'warranty_handovers'  => 'biên bản bàn giao',
            'goods_receipts'      => 'phiếu nhập',
            'goods_issues'        => 'phiếu xuất',
            'stock_takes'         => 'phiếu kiểm kê',
            'warehouse_transfers' => 'phiếu chuyển kho',
            'parts'               => 'hàng riêng',
            'garage_part_prices'  => 'mặt hàng đã chọn',
        ] as $bang => $nhan){
            try {
                $row = $this->table($bang)->select('COUNT(*) AS c')
                            ->where('garage_id', '=', $id)->first();
            } catch (\Throwable $e){
                continue;
            }
            $n = !empty($row['c']) ? (int) $row['c'] : 0;
            if ($n > 0) $ket[$nhan] = $n;
        }
        return $ket;
    }
}

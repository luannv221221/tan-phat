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
     * Gara này còn ràng buộc gì không — để báo cho người dùng biết trước khi xoá.
     *
     * Khoá ngoại đặt ON DELETE SET NULL nên MySQL sẽ CHO xoá và âm thầm bỏ
     * trống `garage_id` của kho, nhân viên, báo giá cũ. Đó là hành vi đúng khi
     * dọn dẹp, nhưng người bấm Xoá cần biết mình đang làm gì.
     */
    public function dangDungODau($id){
        $id  = (int) $id;
        $ket = [];
        foreach ([
            'warehouses'     => 'kho',
            'users'          => 'người dùng',
            'quotations'     => 'báo giá',
            'sales_invoices' => 'hoá đơn',
            /* `parts` phải có mặt ở đây. Khoá ngoại của nó là RESTRICT nên
               MySQL sẽ từ chối lệnh xoá — nhưng người dùng chỉ thấy một lỗi
               CSDL khó hiểu. Đếm ở đây để nói thẳng: "gara này còn N hàng riêng". */
            'parts'          => 'hàng riêng',
        ] as $bang => $nhan){
            $row = $this->table($bang)->select('COUNT(*) AS c')
                        ->where('garage_id', '=', $id)->first();
            $n = !empty($row['c']) ? (int) $row['c'] : 0;
            if ($n > 0) $ket[$nhan] = $n;
        }
        return $ket;
    }
}

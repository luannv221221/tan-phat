<?php

use App\core\Model;

/**
 * KHO — Danh mục kho CỦA TỪNG GARA (mỗi gara một kho mặc định).
 *
 * Gara độc lập (22/09/2026): gara chỉ thấy, chỉ xuất / nhập vào kho của mình.
 * Hàng mua của Tân Phát thì lập phiếu nhập vào kho mình như mua của NCC khác —
 * không có chuyển kho giữa hai gara.
 */
class WarehousesModel extends Model {

    protected $_table    = 'warehouses';
    protected $_fields   = '*';
    protected $_primary  = 'id';
    protected $_theoGara = true;

    public function getLists(){
        return $this->bangGara()
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Đang hoạt động — cho dropdown chọn kho trên phiếu */
    public function getActive(){
        return $this->bangGara()
                    ->where('status', '=', 1)
                    ->orderBy('is_default', 'DESC')
                    ->orderBy('name', 'ASC')
                    ->get();
    }

    /** Kho mặc định CỦA GARA làm việc (hoặc kho đầu tiên đang bật của gara đó) */
    public function getDefault(){
        $r = $this->bangGara()
                  ->where('status', '=', 1)
                  ->where('is_default', '=', 1)
                  ->first();
        if (!empty($r)) return $r;
        return $this->bangGara()
                    ->where('status', '=', 1)
                    ->orderBy('id', 'ASC')->first();
    }

    /** Id các kho của gara làm việc — để giới hạn truy vấn tồn kho / thẻ kho */
    public function idCuaGara(){
        return array_map('intval', array_column((array) $this->bangGara()->select('`id`')->get(), 'id'));
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /** Theo mã — trong gara làm việc (mã kho chỉ duy nhất trong một gara) */
    public function findByCode($code){
        return $this->bangGara()->where('code', '=', $code)->first();
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

    /**
     * Bỏ cờ mặc định ở mọi kho khác CỦA CÙNG GARA (mỗi gara 1 kho mặc định).
     * Không giới hạn gara thì đặt kho mặc định ở gara B là gỡ cờ ở kho của mọi
     * gara khác — hoá đơn của họ không còn kho nào để trừ.
     */
    public function clearDefaultExcept($id){
        list($dk, $b) = $this->dkGara();
        return $this->update('warehouses', ['is_default' => 0], '`id` != ? AND ' . $dk, array_merge([(int) $id], $b));
    }

    public function remove($id){
        return $this->deleteById($id);
    }
}

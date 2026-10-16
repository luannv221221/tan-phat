<?php

use App\core\Model;

/**
 * DANH MỤC HÃNG BẢO HIỂM — dùng khi lập báo giá có phần "bảo hiểm trả".
 *
 * CHUNG VÀ RIÊNG (Model::$_chungVaRieng): Bảo Việt, PVI, PJICO… là hãng toàn
 * quốc, gara nào cũng làm việc với đúng mấy hãng đó — Tân Phát khai sẵn
 * (migration 000098), gara thêm hãng địa phương của mình thì chỉ gara đó thấy.
 *
 * Mọi truy vấn TỰ VIẾT phải tự ghép điều kiện gara (locChungVaRieng); cờ ở lớp
 * cha chỉ với tới getList / getFirst / updateById / deleteById.
 */
class InsurersModel extends Model {

    protected $_table   = 'insurers';
    protected $_fields  = '*';
    protected $_primary = 'id';
    protected $_chungVaRieng = true;

    public function getLists(){
        return $this->bangChungVaRieng()
                    ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    /** Chỉ hãng đang bật — cho ô chọn lúc lập báo giá */
    public function getActive(){
        return $this->bangChungVaRieng()->where('status', '=', 1)
                    ->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /**
     * Tra theo mã, TRONG TẦM THẤY của gara làm việc.
     *
     * Chỉ mục (garage_id, code) KHÔNG chặn được hai dòng CHUNG trùng mã vì
     * MySQL coi các NULL là khác nhau — kiểm trùng phải làm ở đây.
     */
    public function findByCode($code){
        return $this->bangChungVaRieng()->where('code', '=', trim((string) $code))->first();
    }

    /** Số báo giá đang trỏ vào hãng này — còn thì không xoá, chỉ tắt */
    public function demBaoGia($id){
        $r = $this->firstRaw('SELECT COUNT(*) AS c FROM `quotations` WHERE `insurer_id` = ?', [(int) $id]);
        return (int) (isset($r['c']) ? $r['c'] : 0);
    }

    public function add($data){
        $data['create_at'] = date('Y-m-d H:i:s');
        $this->addNew($data);
        /* ÉP INT: lastInsertId() cua PDO tra ve CHUOI, moi phep so sanh chat
           deu truot ma khong bao gi. */
        return (int) $this->lastId();
    }

    public function edit($data, $id){
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    /** Trả false khi còn báo giá trỏ vào — báo giá cũ phải giữ được tên hãng */
    public function remove($id){
        if ($this->demBaoGia($id) > 0) return false;
        return $this->deleteById($id);
    }
}

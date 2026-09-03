<?php

use App\core\Model;

/**
 * Xe của khách hàng (1 khách — N xe).
 *
 * Xem migration 000062 để biết vì sao tách bảng riêng thay vì thêm hai cột
 * vào `members`, và vì sao biển số lưu thành hai cột.
 */
class MemberVehiclesModel extends Model {

    protected $_table   = 'member_vehicles';
    protected $_fields  = '*';
    protected $_primary = 'id';

    /**
     * Chuẩn hoá biển số: bỏ hết dấu, viết hoa.
     *
     *   "30A-123.45"  -> "30A12345"
     *   " 30a 123 45" -> "30A12345"
     *
     * Dùng CHUNG cho cả lúc lưu lẫn lúc tra. Hai chỗ mà chuẩn hoá khác nhau
     * thì tìm mãi không ra, đúng kiểu lỗi rất khó đoán.
     */
    public static function chuanHoaBienSo($s){
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $s));
    }

    /** Xe của một khách */
    public function getByMember($memberId){
        return $this->table($this->_table)
            ->where('member_id', '=', (int) $memberId)
            ->orderBy('id', 'ASC')->get();
    }

    public function getDetail($id){ return $this->getFirst($id); }

    /**
     * Tra theo biển số — trả về kèm thông tin khách.
     *
     * Khớp theo cột đã chuẩn hoá nên gõ kiểu gì cũng ra: có gạch, có chấm,
     * chữ thường, thừa khoảng trắng.
     */
    public function timTheoBienSo($bienSo){
        $chuan = self::chuanHoaBienSo($bienSo);
        if ($chuan === '') return [];

        return $this->getRaw(
            'SELECT `member_vehicles`.*, `members`.`name` AS ten_khach,
                    `members`.`phone` AS dt_khach, `members`.`email` AS email_khach
               FROM `member_vehicles`
               JOIN `members` ON `members`.`id` = `member_vehicles`.`member_id`
              WHERE `member_vehicles`.`bien_so_chuan` LIKE ?
              ORDER BY `member_vehicles`.`id` DESC',
            ['%' . $chuan . '%']
        );
    }

    /** Biển số của nhiều khách cùng lúc: [member_id => [xe, ...]] — tránh N+1 ở màn danh sách */
    public function theoNhieuKhach(array $memberIds){
        $ids = array_values(array_unique(array_map('intval', $memberIds)));
        if (empty($ids)) return [];

        $rows = $this->table($this->_table)
                     ->whereIn('member_id', $ids)
                     ->orderBy('id', 'ASC')->get();

        $map = [];
        foreach ($rows ?: [] as $r){ $map[(int) $r['member_id']][] = $r; }
        return $map;
    }

    public function add($data){
        $data['bien_so_chuan'] = self::chuanHoaBienSo($data['bien_so'] ?? '');
        $data['create_at']     = date('Y-m-d H:i:s');
        $this->addNew($data);
        return $this->lastId();
    }

    public function edit($data, $id){
        if (isset($data['bien_so'])){
            $data['bien_so_chuan'] = self::chuanHoaBienSo($data['bien_so']);
        }
        $data['update_at'] = date('Y-m-d H:i:s');
        return $this->updateById($data, $id);
    }

    public function remove($id){ return $this->deleteById($id); }
}

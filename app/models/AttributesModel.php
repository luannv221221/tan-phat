<?php

require_once __DIR__ . '/LookupModel.php';

/**
 * TASK_90 — Thông số kỹ thuật (Chất liệu, Trọng lượng, Điện áp...).
 *
 * Kế thừa LookupModel (name/slug/sort_order/status + CRUD) và có thêm cột `unit`
 * (đơn vị đo, vd "kg", "mm"). buildData ở controller truyền `unit` vào $data,
 * LookupModel::add/edit lưu nguyên nên không cần override.
 */
class AttributesModel extends LookupModel {
    protected $_table = 'part_attributes';

    /** Chỉ các thông số đang bật — dùng khi gán giá trị cho phụ tùng */
    public function getActive(){
        return $this->getLists(true);
    }

    /**
     * Thông số đang bật CÓ ÁP cho một loại hàng.
     *
     * `item_types` là cột SET nên FIND_IN_SET lọc thẳng trong SQL được.
     * Dùng để: (1) form hàng hoá chỉ nhận đúng thông số của loại đó,
     * (2) trang chi tiết ngoài web không hiện thông số lạc loại.
     */
    public function getForItemType($itemType){
        /* getRaw() chứ không phải query builder: builder không có whereRaw(),
           mà FIND_IN_SET không dựng được bằng where() thường. Không thêm
           whereRaw() vào builder chỉ vì một chỗ này — đó là cửa hậu để nối
           chuỗi vào SQL, mở ra là sớm muộn có người truyền dữ liệu người dùng
           vào. Ở đây giá trị vẫn đi qua placeholder. */
        return $this->getRaw(
            'SELECT * FROM `part_attributes`
             WHERE `status` = 1 AND FIND_IN_SET(?, `item_types`)
             ORDER BY `sort_order` ASC, `name` ASC',
            [$itemType]
        );
    }
}

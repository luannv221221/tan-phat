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
    protected $_table = 'attributes';

    /** Chỉ các thông số đang bật — dùng khi gán giá trị cho phụ tùng */
    public function getActive(){
        return $this->getLists(true);
    }
}

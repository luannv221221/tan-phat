<?php

require_once __DIR__ . '/LookupModel.php';

/**
 * "Dòng xe" trong sheet Tracking — hiểu là KIỂU DÁNG THÂN XE.
 *
 * ✅ ĐÃ CHỐT (17/07/2026): sheet ghi "Dòng xe (hackback, sedan..)" —
 * "hatchback, sedan" là kiểu dáng, nên bảng tên là car_body_types.
 * Xem CAY_DANH_MUC_XE.md mục 1.
 */
class CarBodyTypesModel extends LookupModel {
    protected $_table = 'car_body_types';

    /** Số model đang dùng kiểu dáng này */
    public function countModels($id){
        $r = $this->table('car_models')
                  ->select('COUNT(*) AS total')
                  ->where('body_type_id', '=', $id)
                  ->first();
        return (int) ($r['total'] ?? 0);
    }
}

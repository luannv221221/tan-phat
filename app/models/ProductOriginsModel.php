<?php

require_once __DIR__ . '/LookupModel.php';

/** Xuất xứ phụ tùng — Nhật Bản, Đức... */
class ProductOriginsModel extends LookupModel {
    protected $_table = 'part_origins';
    /* Danh mục chung-và-riêng (08/10/2026): NULL = danh mục tổng, mọi gara đều
       thấy; có garage_id = riêng của gara đó. Xem Model::$_chungVaRieng. */
    protected $_chungVaRieng = true;

}

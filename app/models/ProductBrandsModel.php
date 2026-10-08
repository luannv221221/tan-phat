<?php

require_once __DIR__ . '/LookupModel.php';

/**
 * Thương hiệu PHỤ TÙNG — Bosch, Denso, Aisin...
 *
 * ⚠️ KHÁC `car_brands` (hãng XE: Toyota, Honda).
 *   car_brands  -> phụ tùng lắp cho xe nào
 *   part_brands -> ai làm ra món phụ tùng đó
 */
class ProductBrandsModel extends LookupModel {
    protected $_table = 'part_brands';
    /* Danh mục chung-và-riêng (08/10/2026): NULL = danh mục tổng, mọi gara đều
       thấy; có garage_id = riêng của gara đó. Xem Model::$_chungVaRieng. */
    protected $_chungVaRieng = true;

}

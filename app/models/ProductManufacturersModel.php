<?php

require_once __DIR__ . '/LookupModel.php';

/**
 * Hãng sản xuất — nơi gia công thật.
 * Có thể khác thương hiệu bán ra (vd: hàng Toyota Genuine do Denso gia công).
 */
class ProductManufacturersModel extends LookupModel {
    protected $_table = 'part_manufacturers';
    /* Danh mục chung-và-riêng (08/10/2026): NULL = danh mục tổng, mọi gara đều
       thấy; có garage_id = riêng của gara đó. Xem Model::$_chungVaRieng. */
    protected $_chungVaRieng = true;

}

<?php

require_once __DIR__ . '/LookupModel.php';

/**
 * Hãng sản xuất — nơi gia công thật.
 * Có thể khác thương hiệu bán ra (vd: hàng Toyota Genuine do Denso gia công).
 */
class ProductManufacturersModel extends LookupModel {
    protected $_table = 'product_manufacturers';
}

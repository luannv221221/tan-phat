<?php

require_once __DIR__ . '/LookupModel.php';

/**
 * Thương hiệu PHỤ TÙNG — Bosch, Denso, Aisin...
 *
 * ⚠️ KHÁC `car_brands` (hãng XE: Toyota, Honda).
 *   car_brands     -> phụ tùng lắp cho xe nào
 *   product_brands -> ai làm ra món phụ tùng đó
 */
class ProductBrandsModel extends LookupModel {
    protected $_table = 'product_brands';
}

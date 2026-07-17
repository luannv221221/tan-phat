<?php

/**
 * Thương hiệu phụ tùng
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Productbrands extends \App\core\LookupCrudController {
    protected $modelName = 'ProductBrandsModel';
    protected $routeBase = 'product-brands';
    protected $labelOne  = 'thương hiệu';
    protected $labelMany = 'Thương hiệu phụ tùng';
}

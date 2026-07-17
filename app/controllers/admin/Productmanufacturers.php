<?php

/**
 * Hãng sản xuất
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Productmanufacturers extends \App\core\LookupCrudController {
    protected $modelName = 'ProductManufacturersModel';
    protected $routeBase = 'product-manufacturers';
    protected $labelOne  = 'hãng sản xuất';
    protected $labelMany = 'Hãng sản xuất';
}

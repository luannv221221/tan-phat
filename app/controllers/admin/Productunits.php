<?php

/**
 * Đơn vị tính
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Productunits extends \App\core\LookupCrudController {
    protected $modelName = 'ProductUnitsModel';
    protected $routeBase = 'product-units';
    protected $labelOne  = 'đơn vị tính';
    protected $labelMany = 'Đơn vị tính';
}

<?php

/**
 * Xuất xứ
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Productorigins extends \App\core\LookupCrudController {
    protected $modelName = 'ProductOriginsModel';
    protected $routeBase = 'product-origins';
    protected $labelOne  = 'xuất xứ';
    protected $labelMany = 'Xuất xứ';
}

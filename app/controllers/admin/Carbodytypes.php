<?php

/**
 * Dòng xe (kiểu dáng)
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Carbodytypes extends \App\core\LookupCrudController {
    protected $modelName = 'CarBodyTypesModel';
    protected $routeBase = 'car-body-types';
    protected $labelOne  = 'dòng xe';
    protected $labelMany = 'Dòng xe (kiểu dáng)';
}

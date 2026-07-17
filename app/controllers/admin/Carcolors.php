<?php

/**
 * Màu xe
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Carcolors extends \App\core\LookupCrudController {
    protected $modelName = 'CarColorsModel';
    protected $routeBase = 'car-colors';
    protected $labelOne  = 'màu xe';
    protected $labelMany = 'Màu xe';
    protected $hasHex   = true;
}

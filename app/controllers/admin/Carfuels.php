<?php

/**
 * Nhiên liệu (động cơ xe)
 * Toàn bộ CRUD nằm ở App\core\LookupCrudController — xem file đó.
 */
class Carfuels extends \App\core\LookupCrudController {
    protected $modelName = 'CarFuelsModel';
    protected $routeBase = 'car-fuels';
    protected $labelOne  = 'nhiên liệu';
    protected $labelMany = 'Nhiên liệu (động cơ xe)';
}

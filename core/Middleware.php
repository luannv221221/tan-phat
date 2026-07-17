<?php

namespace App\core;

abstract class Middleware{
    public $path; //lưu path khi kiểm tra quyền middleware
    abstract public function handle();
}
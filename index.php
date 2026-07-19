<?php
//session_start();
//Một số ứng dụng đặt là file init.php
require_once __DIR__ . '/bootstrap.php'; //File khởi chạy để điều hướng request, import (require_once) các file trong app, core

use App\app\App;

$app = new App(); //Khởi tạo đối tượng App
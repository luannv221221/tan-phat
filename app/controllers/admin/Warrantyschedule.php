<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * CSKH — Lịch bảo hành / bảo trì (CS-03).
 *
 * Phiếu chưa hoàn tất của cả hai loại, theo ngày hẹn — và là chỗ LẬP PHIẾU:
 * người trực quầy mở màn này đầu ngày, thấy hôm nay hẹn ai, khách gọi tới
 * đặt lịch thì lập phiếu ngay tại đây. Form lập phiếu dùng chung với màn
 * Phiếu bảo hành / bảo trì (admin/warranty/add?loai=...).
 */
class Warrantyschedule extends Controller {

    private $__data = [];
    private $__model, $__request;

    public static $khoangs = [
        ''         => 'Mọi ngày hẹn',
        'qua_han'  => 'Quá hạn',
        'hom_nay'  => 'Hôm nay',
        '7_ngay'   => '7 ngày tới',
        'chua_hen' => 'Chưa hẹn ngày',
    ];

    function __construct(){
        $this->__model   = $this->model('WarrantyRequestsModel');
        $this->__request = new Request();
    }

    public function index(){
        $f      = $this->__request->getFields();
        $loai   = isset($f['loai']) ? WarrantyRequestsModel::loaiHopLe($f['loai'], '') : '';
        $khoang = (isset($f['khoang']) && is_string($f['khoang']) && isset(self::$khoangs[$f['khoang']])) ? $f['khoang'] : '';
        $today  = date('Y-m-d');

        // Số phiếu đang mở của từng loại — hiện cạnh nút lọc
        $demLoai = array_fill_keys(array_keys(WarrantyRequestsModel::$loais), 0);
        foreach ((array) $this->__model->getSchedule('', '', $today) as $r){
            $l = WarrantyRequestsModel::loaiHopLe($r['loai'] ?? '');
            $demLoai[$l]++;
        }

        $this->__data['sub_content'] = 'admin/lich-bao-hanh/index';
        $this->__data['page_title']  = 'Lịch bảo hành / bảo trì';

        $c = &$this->__data['content'];
        $c['page_name'] = 'Lịch bảo hành / bảo trì';
        $c['rows']      = $this->__model->getSchedule($loai, $khoang, $today);
        $c['statuses']  = WarrantyRequestsModel::$statuses;
        $c['loais']     = WarrantyRequestsModel::$loais;
        $c['khoangs']   = self::$khoangs;
        $c['loai']      = $loai;
        $c['khoang']    = $khoang;
        $c['demLoai']   = $demLoai;
        $c['dangLoc']   = ($loai !== '' || $khoang !== '');
        $c['today']     = $today;
        $c['msg']       = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

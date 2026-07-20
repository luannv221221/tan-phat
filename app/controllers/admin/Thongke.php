<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/** STOREFRONT (admin) — Thống kê truy cập. */
class Thongke extends Controller {

    private $__data = [];
    private $__model, $__request;

    function __construct(){
        $this->__model   = $this->model('VisitsModel');
        $this->__request = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/thong-ke/index';
        $this->__data['page_title']  = 'Thống kê truy cập';

        $f    = $this->__request->getFields();
        $days = isset($f['days']) && in_array((int) $f['days'], [7, 30, 90], true) ? (int) $f['days'] : 30;
        $from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        $rows = $this->__model->fetchSince($from);

        $selfHost = parse_url(_WEB_URL, PHP_URL_HOST);
        $pages = []; $refs = []; $kws = []; $byDay = [];
        // khởi tạo khung ngày
        for ($i = $days - 1; $i >= 0; $i--){ $byDay[date('Y-m-d', strtotime('-' . $i . ' days'))] = 0; }

        foreach ($rows as $r){
            $d = substr($r['create_at'], 0, 10);
            if (isset($byDay[$d])) $byDay[$d]++;

            $u = $r['url'] !== '' ? $r['url'] : '/';
            $pages[$u] = ($pages[$u] ?? 0) + 1;

            if (!empty($r['referrer'])){
                $h = parse_url($r['referrer'], PHP_URL_HOST);
                if (!empty($h) && $h !== $selfHost){ $refs[$h] = ($refs[$h] ?? 0) + 1; }
            }
            if (!empty($r['keyword'])){ $kws[$r['keyword']] = ($kws[$r['keyword']] ?? 0) + 1; }
        }
        arsort($pages); arsort($refs); arsort($kws);

        $c = &$this->__data['content'];
        $c['page_name']   = 'Thống kê truy cập';
        $c['days']        = $days;
        $c['totalAll']    = $this->__model->totalViews('');
        $c['totalRange']  = $this->__model->totalViews($from);
        $c['uniqueRange'] = $this->__model->uniqueVisitors($from);
        $c['byDay']       = $byDay;
        $c['maxDay']      = $byDay ? max($byDay) : 0;
        $c['topPages']    = array_slice($pages, 0, 12, true);
        $c['topRefs']     = array_slice($refs, 0, 10, true);
        $c['topKeywords'] = array_slice($kws, 0, 10, true);
        $c['msg']         = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }
}

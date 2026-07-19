<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH — Nhắc bảo trì tự động.
 *
 * Ngày bảo trì kế tiếp = ngày hoàn tất phiếu bảo hành + chu kỳ (tháng, cấu hình).
 * Màn hình liệt kê phiếu tới hạn / quá hạn để CSKH gọi nhắc khách.
 */
class Baotri extends Controller {

    private $__data = [];
    private $__model, $__settings, $__request, $__response;

    private $routeBase = 'nhac-bao-tri';

    function __construct(){
        $this->__model    = $this->model('WarrantyRequestsModel');
        $this->__settings = $this->model('SettingsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function cfg(){
        $interval = (int) $this->__settings->val('maintenance_interval_months', '6');
        if ($interval <= 0) $interval = 6;
        $window = (int) $this->__settings->val('maintenance_window_days', '30');
        if ($window < 0) $window = 30;
        return ['interval' => $interval, 'window' => $window];
    }

    /** Cộng tháng vào ngày, trả 'Y-m-d' */
    private function addMonths($date, $months){
        try { $d = new \DateTime(substr($date, 0, 10)); } catch (\Exception $e){ return null; }
        $d->modify('+' . (int) $months . ' months');
        return $d->format('Y-m-d');
    }

    /** Số ngày lịch từ hôm nay đến $date (âm = đã quá hạn) */
    private function daysUntil($date){
        try {
            $t = new \DateTime(date('Y-m-d'));
            $d = new \DateTime(substr($date, 0, 10));
        } catch (\Exception $e){ return null; }
        $diff = (int) $t->diff($d)->days;
        return ($d < $t) ? -$diff : $diff;
    }

    public function index(){
        $cfg = $this->cfg();
        $f   = $this->__request->getFields();
        $mode= isset($f['mode']) && in_array($f['mode'], ['due', 'overdue', 'all']) ? $f['mode'] : 'due';

        $all = $this->__model->getCompleted();
        $rows = [];
        $cntDue = 0; $cntOverdue = 0;
        foreach ($all ?: [] as $w){
            $next = $this->addMonths($w['completed_date'], $cfg['interval']);
            if ($next === null) continue;
            $days = $this->daysUntil($next);
            $isOverdue = ($days !== null && $days < 0);
            $isDue     = ($days !== null && $days <= $cfg['window']); // gồm cả quá hạn
            if ($isOverdue) $cntOverdue++;
            if ($isDue && !$isOverdue) $cntDue++;

            if ($mode === 'due' && !$isDue) continue;
            if ($mode === 'overdue' && !$isOverdue) continue;

            $w['next_date']  = $next;
            $w['days_until'] = $days;
            $w['is_overdue'] = $isOverdue;
            $w['is_due']     = $isDue;
            $rows[] = $w;
        }
        // sắp: quá hạn nhiều nhất trước
        usort($rows, function($a, $b){ return ($a['days_until'] ?? 0) <=> ($b['days_until'] ?? 0); });

        $this->__data['sub_content'] = 'admin/nhac-bao-tri/index';
        $this->__data['page_title']  = 'Nhắc bảo trì';
        $c = &$this->__data['content'];
        $c['routeBase']   = $this->routeBase;
        $c['page_name']   = 'Nhắc bảo trì định kỳ';
        $c['rows']        = $rows;
        $c['interval']    = $cfg['interval'];
        $c['window']      = $cfg['window'];
        $c['mode']        = $mode;
        $c['cntDue']      = $cntDue;
        $c['cntOverdue']  = $cntOverdue;
        $c['today']       = date('Y-m-d');
        $c['msg']         = Session::flash('msg');
        $c['msgError']    = Session::flash('msgError');
        $this->render('layouts/admin/master_admin', $this->__data);
    }

    public function saveConfig(){
        if (!route('admin/' . $this->routeBase . '/edit/0')){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $f = $this->__request->getFields();
        $interval = max(1, (int) ($f['interval'] ?? 6));
        $window   = max(0, (int) ($f['window'] ?? 30));
        $this->__settings->saveMany([
            'maintenance_interval_months' => (string) $interval,
            'maintenance_window_days'     => (string) $window,
        ]);
        Session::flash('msg', 'Đã lưu chu kỳ bảo trì: ' . $interval . ' tháng, nhắc trước ' . $window . ' ngày.');
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function markReminded($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy phiếu');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $this->__model->setReminded($id, date('Y-m-d'));
        Session::flash('msg', 'Đã đánh dấu đã nhắc khách phiếu ' . $item['request_no']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    public function unremind($id){
        $item = $this->__model->getDetail($id);
        if (empty($item)){
            Session::flash('msgError', 'Không tìm thấy phiếu');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }
        if (!route('admin/' . $this->routeBase . '/edit/' . $id)){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }
        $this->__model->setReminded($id, null);
        Session::flash('msg', 'Đã bỏ đánh dấu nhắc phiếu ' . $item['request_no']);
        $this->__response->redirect('admin/' . $this->routeBase);
    }
}

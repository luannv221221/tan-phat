<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * CSKH — Nhắc bảo trì định kỳ.
 *
 * Nguồn: LẦN BẢO TRÌ CUỐI của mỗi xe / thiết bị (phiếu bảo trì đã hoàn tất).
 * Hạn = cái nào tới trước giữa:
 *   - ngày hoàn tất + chu kỳ tháng
 *   - ngày xe ước chạm mốc km (km lúc bảo trì + chu kỳ km)
 * Cách ước km nằm ở han_bao_tri() (app/helpers/functions.php).
 *
 * Trước đây nguồn là ngày hoàn tất PHIẾU BẢO HÀNH — sửa một cái đèn pha
 * hỏng bị tính như vừa bảo dưỡng xe, còn xe chưa hỏng lần nào thì không bao
 * giờ được nhắc.
 */
class Baotri extends Controller {

    private $__data = [];
    private $__model, $__settings, $__request, $__response;

    private $routeBase = 'nhac-bao-tri';

    function __construct(){
        $this->__model    = $this->model('WarrantyRequestsModel');
        /* Chu kỳ bảo trì là cấu hình RIÊNG của từng gara (chưa đặt thì lấy
           Cấu hình chung). Lưu vào site_settings là gara này đổi hộ mọi gara. */
        $this->__settings = $this->model('GarageSettingsModel');
        $this->__request  = new Request();
        $this->__response = new Response();
    }

    private function cfg(){
        $interval = (int) $this->__settings->val('maintenance_interval_months', '6');
        if ($interval <= 0) $interval = 6;
        $window = (int) $this->__settings->val('maintenance_window_days', '30');
        if ($window < 0) $window = 30;
        // 0 = không nhắc theo km
        $km = (int) $this->__settings->val('maintenance_interval_km', '5000');
        if ($km < 0) $km = 0;
        return ['interval' => $interval, 'window' => $window, 'km' => $km];
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

        /* Chỉ LẦN BẢO TRÌ CUỐI của mỗi xe / thiết bị mới sinh ra lời nhắc: xe
           bảo trì tháng 1 rồi tháng 7 thì lần tháng 1 không được nhắc nữa.
           baoTriDaXong() đã xếp mới nhất trước — gặp đầu tiên là lần cuối. */
        $cuoi = [];
        foreach ((array) $this->__model->baoTriDaXong() as $w){
            $k = WarrantyRequestsModel::khoaDoiTuong($w);
            if (!isset($cuoi[$k])) $cuoi[$k] = $w;
        }

        /* Đã lập phiếu bảo trì MỚI (chưa xong) cho cùng xe sau lần cuối -> đã
           hẹn, không cần gọi nhắc nữa. Vẫn hiện ở "Tất cả" để biết ai đã hẹn. */
        $daHen = [];
        foreach ((array) $this->__model->baoTriDangMo() as $o){
            $k = WarrantyRequestsModel::khoaDoiTuong($o);
            if (isset($cuoi[$k]) && $o['received_date'] >= $cuoi[$k]['completed_date']) $daHen[$k] = $o;
        }

        $docKm = $this->__model->docKmTheoBienSo(array_column($cuoi, 'bien_so_chuan'));

        $rows = [];
        $cntDue = 0; $cntOverdue = 0;
        foreach ($cuoi as $k => $w){
            $bsc = !empty($w['bien_so_chuan']) ? $w['bien_so_chuan'] : '';
            $h = han_bao_tri(
                ['ngay' => $w['completed_date'], 'km' => $w['so_km']],
                ($bsc !== '' && isset($docKm[$bsc])) ? $docKm[$bsc] : [],
                ['thang' => $cfg['interval'], 'km' => $cfg['km']]
            );
            if ($h['han'] === null) continue;

            $days      = $this->daysUntil($h['han']);
            $booked    = isset($daHen[$k]);
            $isOverdue = !$booked && $days !== null && $days < 0;
            $isDue     = !$booked && $days !== null && $days <= $cfg['window']; // gồm cả quá hạn
            if ($isOverdue) $cntOverdue++;
            if ($isDue && !$isOverdue) $cntDue++;

            if ($mode === 'due' && !$isDue) continue;
            if ($mode === 'overdue' && !$isOverdue) continue;

            $w['han']        = $h;
            $w['days_until'] = $days;
            $w['is_overdue'] = $isOverdue;
            $w['is_due']     = $isDue;
            $w['da_hen']     = $booked ? $daHen[$k] : null;
            $rows[] = $w;
        }
        // quá hạn nhiều nhất trước
        usort($rows, function($a, $b){ return ($a['days_until'] ?? 0) <=> ($b['days_until'] ?? 0); });

        $this->__data['sub_content'] = 'admin/nhac-bao-tri/index';
        $this->__data['page_title']  = 'Nhắc bảo trì';
        $c = &$this->__data['content'];
        $c['routeBase']   = $this->routeBase;
        $c['page_name']   = 'Nhắc bảo trì định kỳ';
        $c['rows']        = $rows;
        $c['interval']    = $cfg['interval'];
        $c['window']      = $cfg['window'];
        $c['kmCfg']       = $cfg['km'];
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
        $km       = max(0, (int) preg_replace('/[^\d]/', '', (string) ($f['km'] ?? '5000')));
        $this->__settings->saveMany([
            'maintenance_interval_months' => (string) $interval,
            'maintenance_window_days'     => (string) $window,
            'maintenance_interval_km'     => (string) $km,
        ]);
        Session::flash('msg', 'Đã lưu chu kỳ bảo trì: ' . $interval . ' tháng'
            . ($km > 0 ? ' hoặc ' . number_format($km, 0, ',', '.') . ' km' : ' (không nhắc theo km)')
            . ', nhắc trước ' . $window . ' ngày.');
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

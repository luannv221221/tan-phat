<?php
use App\core\Controller;

class Dashboard extends Controller {

    private $__data = [];
    private $__orderModel;

    /** Các khoảng thời gian cho ô lọc. Khoá = số ngày. */
    public static $ranges = [
        7   => '7 ngày qua',
        30  => '30 ngày qua',
        90  => '90 ngày qua',
        365 => '12 tháng qua',
    ];

    public function __construct(){
        $this->__orderModel = $this->model('OrdersModel');
    }

    public function index(){

        // --- Khoảng thời gian ---
        $days = isset($_GET['range']) ? (int) $_GET['range'] : 7;
        if (!isset(self::$ranges[$days])) { $days = 7; }

        // Kỳ hiện tại: [đầu ngày cách đây $days-1 ngày, đầu ngày mai)
        $to       = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $from     = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' day'));
        // Kỳ trước: cùng độ dài, liền kề phía trước
        $prevTo   = $from;
        $prevFrom = date('Y-m-d 00:00:00', strtotime($from . ' -' . $days . ' day'));

        /* Gara độc lập: đơn hàng website là của Tân Phát. Gara khác xem Tổng quan
           thì thấy số của CHÍNH MÌNH (hoá đơn, báo giá) — không được thấy doanh
           thu web của Tân Phát. */
        if (la_gara_tong()){
            $cur  = $this->__orderModel->statsByStatus($from, $to);
            $prev = $this->__orderModel->statsByStatus($prevFrom, $prevTo);

            // "Chốt" = đã xác nhận trở đi (bỏ đơn mới chưa duyệt và đơn đã huỷ).
            $closed = ['confirmed', 'shipping', 'completed'];

            $this->__data['content']['cards'] = [
                $this->card('Tổng hàng chốt',  'clipboard-check', 'green', $cur, $prev, $closed),
                $this->card('Đơn đã huỷ',      'arrow-left-right', 'amber', $cur, $prev, ['cancelled']),
                $this->card('Đơn hoàn thành',  'shopping-bag',    'blue',  $cur, $prev, ['completed']),
            ];
        } else {
            $hd  = $this->model('SalesInvoicesModel');
            $bg  = $this->model('QuotationsModel');
            $hdCur = $hd->thongKeKy($from, $to);  $hdPrev = $hd->thongKeKy($prevFrom, $prevTo);
            $bgCur = $bg->thongKeKy($from, $to);  $bgPrev = $bg->thongKeKy($prevFrom, $prevTo);

            $this->__data['content']['cards'] = [
                $this->card('Doanh thu đã ghi sổ', 'clipboard-check',  'green', $hdCur, $hdPrev, ['1']),
                $this->card('Hoá đơn chưa ghi sổ', 'shopping-bag',     'amber', $hdCur, $hdPrev, ['0']),
                $this->card('Báo giá đã lập',      'arrow-left-right', 'blue',  $bgCur, $bgPrev, array_keys(QuotationsModel::$statuses)),
            ];
        }

        $this->__data['content']['ranges']    = self::$ranges;
        $this->__data['content']['rangeDays'] = $days;
        $this->__data['content']['compare']   = 'Trước đó ' . $days . ' ngày';

        $this->__data['page_title'] = 'Tổng quan';
        $this->__data['content']['page_name'] = 'Tổng quan';

        $this->__data['sub_content'] = 'admin/dashboard';

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /** Cộng dồn count/sum của nhiều trạng thái trong 1 kỳ. */
    private function bucket($stats, $statuses){
        $count = 0; $sum = 0.0;
        foreach ($statuses as $s){
            if (isset($stats[$s])){
                $count += $stats[$s]['count'];
                $sum   += $stats[$s]['sum'];
            }
        }
        return ['count' => $count, 'sum' => $sum];
    }

    /**
     * Dựng dữ liệu 1 thẻ thống kê: giá trị kỳ này + % so với kỳ trước.
     * Kỳ trước bằng 0 thì không chia được — coi như +100% nếu kỳ này có số,
     * và 0% nếu cả hai đều rỗng.
     */
    private function card($title, $icon, $color, $cur, $prev, $statuses){
        $c = $this->bucket($cur,  $statuses);
        $p = $this->bucket($prev, $statuses);

        return [
            'title' => $title,
            'icon'  => $icon,
            'color' => $color,
            'sum'   => $c['sum'],
            'count' => $c['count'],
            'deltaSum'   => $this->delta($c['sum'],   $p['sum']),
            'deltaCount' => $this->delta($c['count'], $p['count']),
        ];
    }

    /** ['dir' => 'up'|'down'|'flat', 'percent' => float] */
    private function delta($now, $before){
        if ($before == 0){
            if ($now == 0) { return ['dir' => 'flat', 'percent' => 0.0]; }
            return ['dir' => 'up', 'percent' => 100.0];
        }

        $pc = (($now - $before) / abs($before)) * 100;
        $dir = 'flat';
        if ($pc > 0)      { $dir = 'up'; }
        elseif ($pc < 0)  { $dir = 'down'; }

        return ['dir' => $dir, 'percent' => abs(round($pc, 1))];
    }

    public function noPermission(){
        $this->render('admin/no-permission');
    }
}

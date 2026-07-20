<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KHO-3 — Biểu đồ biến động tồn theo ngày cho 1 phụ tùng. Chỉ xem.
 */
class Biendongton extends Controller {

    private $__data = [];
    private $__stock, $__warehouse, $__part, $__request;

    function __construct(){
        $this->__stock     = $this->model('StocksModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__part      = $this->model('PartsModel');
        $this->__request   = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = 'admin/bien-dong-ton/index';
        $this->__data['page_title']  = 'Biến động tồn';

        $f     = $this->__request->getFields();
        $partId= !empty($f['part_id']) ? (int) $f['part_id'] : 0;
        $whId  = !empty($f['warehouse_id']) ? (int) $f['warehouse_id'] : 0;
        $from  = !empty($f['from']) ? $f['from'] : date('Y-m-d', strtotime('-90 days'));
        $to    = !empty($f['to'])   ? $f['to']   : date('Y-m-d');

        $result = ['opening' => 0.0, 'rows' => []];
        $partRow = null;
        if ($partId > 0){
            $partRow = $this->__part->getDetail($partId);
            $result  = $this->__stock->getMovementByDay($partId, $whId, $from, $to);
        }

        // Tổng nhập/xuất trong kỳ
        $sumIn = 0.0; $sumOut = 0.0;
        foreach ($result['rows'] as $r){ $sumIn += (float) $r['in']; $sumOut += (float) $r['out']; }

        $this->__data['content']['page_name']    = 'Biến động tồn theo ngày';
        $this->__data['content']['parts']        = $this->__part->getForSelect();
        $this->__data['content']['warehouses']   = $this->__warehouse->getActive();
        $this->__data['content']['filterPart']   = $partId;
        $this->__data['content']['filterWh']     = $whId;
        $this->__data['content']['filterFrom']   = $from;
        $this->__data['content']['filterTo']     = $to;
        $this->__data['content']['partRow']      = $partRow;
        $this->__data['content']['opening']      = $result['opening'];
        $this->__data['content']['rows']         = $result['rows'];
        $this->__data['content']['sumIn']        = $sumIn;
        $this->__data['content']['sumOut']       = $sumOut;
        $this->__data['content']['chartSvg']     = $this->buildChart($result['rows']);
        $this->__data['content']['msg']          = Session::flash('msg');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /**
     * Dựng SVG tự chứa: đường số dư tồn + cột nhập (xanh) / xuất (đỏ) theo ngày.
     * Dữ liệu toàn số (server-side) nên an toàn khi in raw.
     */
    private function buildChart($rows){
        if (empty($rows)) return '';

        $W = 900; $H = 320; $padL = 56; $padR = 16; $padT = 16; $padB = 46;
        $plotW = $W - $padL - $padR;
        $plotH = $H - $padT - $padB;
        $n = count($rows);

        $maxBal = 0.0; $maxMove = 0.0;
        foreach ($rows as $r){
            $maxBal  = max($maxBal, (float) $r['balance']);
            $maxMove = max($maxMove, (float) $r['in'], (float) $r['out']);
        }
        $maxBal  = $maxBal  > 0 ? $maxBal  : 1;
        $maxMove = $maxMove > 0 ? $maxMove : 1;

        $x = function($i) use ($padL, $plotW, $n){
            return $padL + ($n <= 1 ? $plotW / 2 : $plotW * $i / ($n - 1));
        };
        $yBal = function($v) use ($padT, $plotH, $maxBal){
            return $padT + $plotH - ($plotH * $v / $maxBal);
        };

        $svg  = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" width="100%" preserveAspectRatio="xMidYMid meet" style="max-width:100%;height:auto;font-family:sans-serif;font-size:11px">';
        // lưới ngang + nhãn trục tồn
        for ($g = 0; $g <= 4; $g++){
            $val = $maxBal * $g / 4;
            $yy  = $padT + $plotH - ($plotH * $g / 4);
            $svg .= '<line x1="' . $padL . '" y1="' . round($yy, 1) . '" x2="' . ($W - $padR) . '" y2="' . round($yy, 1) . '" stroke="#eee"/>';
            $svg .= '<text x="' . ($padL - 6) . '" y="' . round($yy + 3, 1) . '" text-anchor="end" fill="#999">' . number_format($val, 0, ',', '.') . '</text>';
        }
        // cột nhập/xuất
        $bw = max(2, min(14, ($plotW / max(1, $n)) * 0.36));
        foreach ($rows as $i => $r){
            $cx = $x($i);
            $hIn  = $plotH * ((float) $r['in'])  / $maxMove;
            $hOut = $plotH * ((float) $r['out']) / $maxMove;
            if ($hIn > 0)  $svg .= '<rect x="' . round($cx - $bw - 1, 1) . '" y="' . round($padT + $plotH - $hIn, 1) . '" width="' . round($bw, 1) . '" height="' . round($hIn, 1) . '" fill="#28a745" opacity="0.55"/>';
            if ($hOut > 0) $svg .= '<rect x="' . round($cx + 1, 1) . '" y="' . round($padT + $plotH - $hOut, 1) . '" width="' . round($bw, 1) . '" height="' . round($hOut, 1) . '" fill="#dc3545" opacity="0.55"/>';
        }
        // đường số dư tồn
        $pts = [];
        foreach ($rows as $i => $r){ $pts[] = round($x($i), 1) . ',' . round($yBal((float) $r['balance']), 1); }
        $svg .= '<polyline points="' . implode(' ', $pts) . '" fill="none" stroke="#c0392b" stroke-width="2"/>';
        foreach ($rows as $i => $r){ $svg .= '<circle cx="' . round($x($i), 1) . '" cy="' . round($yBal((float) $r['balance']), 1) . '" r="2.6" fill="#c0392b"/>'; }
        // nhãn trục ngày (thưa để khỏi chồng)
        $step = (int) ceil($n / 8);
        foreach ($rows as $i => $r){
            if ($i % $step !== 0 && $i !== $n - 1) continue;
            $svg .= '<text x="' . round($x($i), 1) . '" y="' . ($H - 26) . '" text-anchor="middle" fill="#666" transform="rotate(0)">' . e(substr($r['date'], 5)) . '</text>';
        }
        // chú giải
        $svg .= '<rect x="' . $padL . '" y="' . ($H - 16) . '" width="10" height="10" fill="#28a745" opacity="0.55"/><text x="' . ($padL + 14) . '" y="' . ($H - 7) . '" fill="#666">Nhập</text>';
        $svg .= '<rect x="' . ($padL + 60) . '" y="' . ($H - 16) . '" width="10" height="10" fill="#dc3545" opacity="0.55"/><text x="' . ($padL + 74) . '" y="' . ($H - 7) . '" fill="#666">Xuất</text>';
        $svg .= '<line x1="' . ($padL + 120) . '" y1="' . ($H - 11) . '" x2="' . ($padL + 140) . '" y2="' . ($H - 11) . '" stroke="#c0392b" stroke-width="2"/><text x="' . ($padL + 144) . '" y="' . ($H - 7) . '" fill="#666">Tồn cuối ngày</text>';
        $svg .= '</svg>';
        return $svg;
    }
}

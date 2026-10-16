<?php

use App\core\Controller;
use App\core\Request;
use App\core\Session;

/**
 * KẾ TOÁN — BÁO CÁO. Chỉ xem, không ghi gì.
 *
 * Năm báo cáo tài liệu khách yêu cầu (14/10/2026), gộp vào MỘT màn bốn thẻ:
 *
 *   1+2. Thu / chi theo kỳ   — gom theo NGÀY, THÁNG hoặc NĂM. Gộp hai báo cáo
 *        đầu làm một bảng hai cột: người xem gần như luôn muốn so thu với chi
 *        trong cùng một kỳ, tách hai màn là bắt họ mở hai tab rồi nhìn qua lại.
 *   3.   Khách hàng còn nợ gara = tiền hàng đã bán − đã thu
 *   4.   Gara còn nợ nhà cung cấp = tiền hàng đã nhập mua − đã chi
 *   5.   Báo cáo kinh doanh
 *
 * ===================================================================
 *  LÃI GỘP VÀ DÒNG TIỀN LÀ HAI THỨ KHÁC NHAU — KHÔNG CỘNG VÀO NHAU.
 * ===================================================================
 *
 * Chỗ này dễ sai nhất và sai thì không nhìn ra: phiếu thu tiền khách KHÔNG
 * phải doanh thu mới — nó là thu hồi một khoản đã ghi doanh thu lúc xuất hoá
 * đơn. Cộng "lãi gộp + tổng thu − tổng chi" là đếm doanh thu hai lần, ra một
 * con số to đẹp và hoàn toàn sai.
 *
 * Nên thẻ Kinh doanh để HAI KHỐI RIÊNG, có chú thích ngay cạnh:
 *     Bán hàng:  doanh thu − giá vốn = lãi gộp
 *     Tiền mặt:  tổng thu − tổng chi = chênh lệch dòng tiền
 *
 * MỌI SỐ CHỈ TÍNH CHỨNG TỪ ĐÃ GHI SỔ / CÒN HIỆU LỰC. Hoá đơn nháp chưa phải
 * khoản phải thu, phiếu đã huỷ không còn là tiền.
 */
class Baocaoketoan extends Controller {

    private $__data = [];
    private $__voucher, $__invoice, $__receipt, $__request;

    private $routeBase = 'bao-cao-ke-toan';
    private $labelMany = 'Báo cáo kế toán';
    private $viewDir   = 'admin/bao-cao-ke-toan';

    /** Cách gom kỳ cho thẻ Thu / chi */
    public static $theoKy = ['ngay' => 'Theo ngày', 'thang' => 'Theo tháng', 'nam' => 'Theo năm'];

    function __construct(){
        $this->__voucher = $this->model('AccVouchersModel');
        $this->__invoice = $this->model('SalesInvoicesModel');
        $this->__receipt = $this->model('GoodsReceiptsModel');
        $this->__request = new Request();
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = $this->labelMany;

        $f = $this->__request->getFields();

        /* Mặc định NĂM NAY, gom theo tháng: mở ra là thấy ngay bức tranh cả
           năm. Mặc định theo ngày thì bảng dài ba trăm dòng ngay từ đầu. */
        $tu   = !empty($f['tu'])  ? $f['tu']  : date('Y-01-01');
        $den  = !empty($f['den']) ? $f['den'] : date('Y-12-31');
        $theo = isset($f['theo']) && isset(self::$theoKy[$f['theo']]) ? $f['theo'] : 'thang';
        $tab  = isset($f['tab']) ? $f['tab'] : 'ky';

        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['page_name'] = $this->labelMany;
        $c['tu'] = $tu; $c['den'] = $den; $c['theo'] = $theo; $c['tab'] = $tab;
        $c['dsTheoKy'] = self::$theoKy;
        $c['msg']      = Session::flash('msg');

        // --- 1 + 2. Thu / chi theo kỳ ---
        $c['theoKy']  = $this->gomTheoKy($theo, $tu, $den);
        $c['tongThu'] = $this->__voucher->tong('thu', $tu, $den);
        $c['tongChi'] = $this->__voucher->tong('chi', $tu, $den);

        // --- 3 + 4. Công nợ ---
        $c['khachNo'] = $this->congNo(
            $this->__invoice->tienBanTheoKhach($den),
            $this->__voucher->tongTheoDoiTuong('thu', $den), 'so_hd');
        $c['nccNo']   = $this->congNo(
            $this->__receipt->tienMuaTheoNcc($den),
            $this->__voucher->tongTheoDoiTuong('chi', $den), 'so_pn');

        // --- 5. Kinh doanh ---
        $c['kinhDoanh'] = $this->kinhDoanh($tu, $den);

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    // ===== Tính toán =====

    /**
     * Thu và chi của CÙNG một kỳ nằm trên CÙNG một dòng.
     *
     * Hai truy vấn riêng trả về hai danh sách kỳ KHÁC NHAU (tháng có thu chưa
     * chắc có chi), nên phải gộp khoá chứ không ghép theo thứ tự — ghép theo
     * thứ tự là số chi của tháng 3 nhảy sang dòng tháng 2.
     */
    private function gomTheoKy($theo, $tu, $den){
        $ra = [];
        foreach ($this->__voucher->tongTheoKy('thu', $theo, $tu, $den) as $r){
            $ra[$r['ky']] = ['ky' => $r['ky'], 'thu' => (float) $r['s'], 'chi' => 0.0,
                             'n_thu' => (int) $r['n'], 'n_chi' => 0];
        }
        foreach ($this->__voucher->tongTheoKy('chi', $theo, $tu, $den) as $r){
            if (!isset($ra[$r['ky']])){
                $ra[$r['ky']] = ['ky' => $r['ky'], 'thu' => 0.0, 'chi' => 0.0,
                                 'n_thu' => 0, 'n_chi' => 0];
            }
            $ra[$r['ky']]['chi']   = (float) $r['s'];
            $ra[$r['ky']]['n_chi'] = (int) $r['n'];
        }
        ksort($ra);
        foreach ($ra as $k => $v) $ra[$k]['chenh'] = $v['thu'] - $v['chi'];
        return array_values($ra);
    }

    /**
     * Công nợ = tiền chứng từ − tiền đã thanh toán, theo từng đối tượng.
     *
     * @param array  $tienHang [pid => ['ten','ma','dt','tien', $cotSo]]
     * @param array  $daTra    [pid => tổng tiền phiếu]
     * @param string $cotSo    tên cột đếm chứng từ ('so_hd' | 'so_pn')
     *
     * CHỈ GIỮ DÒNG CÒN NỢ (> 0). Trả thừa / trả trước ra số âm — đó là chuyện
     * có thật nhưng không thuộc câu hỏi "còn nợ bao nhiêu", bày lẫn vào là
     * người đọc cộng nhầm cả cột.
     */
    private function congNo(array $tienHang, array $daTra, $cotSo){
        $ra = ['dong' => [], 'tong' => 0.0, 'traThua' => []];

        foreach ($tienHang as $pid => $h){
            $tra = isset($daTra[$pid]) ? $daTra[$pid] : 0.0;
            $con = $h['tien'] - $tra;
            $d = ['pid' => $pid, 'ten' => $h['ten'], 'ma' => $h['ma'], 'dt' => $h['dt'],
                  'tien' => $h['tien'], 'tra' => $tra, 'con' => $con,
                  'so_ct' => isset($h[$cotSo]) ? $h[$cotSo] : 0];

            if ($con > 0.009){ $ra['dong'][] = $d; $ra['tong'] += $con; }
            elseif ($con < -0.009) $ra['traThua'][] = $d;
        }

        /* Đối tượng CÓ phiếu thanh toán mà KHÔNG có chứng từ hàng: tiền trả
           trước, hoặc phiếu gán nhầm người. Không im lặng bỏ đi — đó đúng là
           loại sai sót mà báo cáo công nợ phải lôi ra. */
        foreach ($daTra as $pid => $tra){
            if (isset($tienHang[$pid]) || $tra <= 0) continue;
            $ra['traThua'][] = ['pid' => $pid, 'ten' => '(đối tượng #' . $pid . ')', 'ma' => '',
                                'dt' => '', 'tien' => 0.0, 'tra' => $tra, 'con' => -$tra, 'so_ct' => 0];
        }

        usort($ra['dong'], function($a, $b){ return $b['con'] <=> $a['con']; });
        return $ra;
    }

    /** Thẻ Kinh doanh — xem ghi chú đầu lớp về việc KHÔNG cộng hai khối */
    private function kinhDoanh($tu, $den){
        $doanhThu = 0.0; $thue = 0.0; $giaVon = 0.0; $soHd = 0;
        foreach ($this->__invoice->getPostedForReport($tu, $den) as $r){
            $doanhThu += (float) $r['subtotal'];
            $thue     += (float) $r['tax_amount'];
            $giaVon   += (float) $r['cost_amount'];
            $soHd++;
        }
        $thu = $this->__voucher->tong('thu', $tu, $den);
        $chi = $this->__voucher->tong('chi', $tu, $den);

        return [
            'doanh_thu' => $doanhThu,
            'thue'      => $thue,
            'gia_von'   => $giaVon,
            'lai_gop'   => $doanhThu - $giaVon,
            'so_hd'     => $soHd,
            'thu'       => $thu,
            'chi'       => $chi,
            'chenh'     => $thu - $chi,
        ];
    }
}

<?php

require_once __DIR__ . '/AccVoucherBase.php';

use App\core\Session;

/**
 * KẾ TOÁN — PHIẾU THU.
 *
 * Toàn bộ nghiệp vụ nằm ở AccVoucherBase; ở đây chỉ đặt chiều tiền và mấy nhãn
 * chữ. Phiếu chi là lớp sinh đôi (Phieuchi) — xem ghi chú đầu AccVoucherBase
 * về việc vì sao hai màn này dùng lớp nền còn mấy màn danh mục thì không.
 *
 * Thêm một việc không thuộc lớp nền: lưu SỐ DƯ BAN ĐẦU của sổ quỹ
 * (postSoDuQuy) — xem ghi chú ở hàm đó vì sao nó nằm ở đây.
 *
 * Quyền do RoleMiddleware gác theo module `phieu-thu` (khai từ migration
 * 000082, cấp quyền ghi ở 000096).
 */
class Phieuthu extends AccVoucherBase {

    protected $loai      = 'thu';
    protected $routeBase = 'phieu-thu';
    protected $labelOne  = 'phiếu thu';
    protected $labelMany = 'Phiếu thu';

    /**
     * LƯU SỐ DƯ BAN ĐẦU CỦA MỘT QUỸ — form ở thẻ Sổ quỹ (Báo cáo kế toán).
     *
     * VÌ SAO NẰM Ở MÀN PHIẾU THU: màn Báo cáo kế toán chỉ có quyền `view` và cố
     * ý không có đường ghi nào (BaoCaoVaBaoHiemTest). Số dư ban đầu là con số
     * tiền của quỹ — người khai nó phải là người được ghi chứng từ tiền.
     *
     * QUYỀN: SỬA được CẢ phiếu thu LẪN phiếu chi. RoleMiddleware chỉ đòi "một
     * quyền ghi nào đó" trên `phieu-thu`, tức nhân viên được cấp `add` là qua —
     * mà đổi số dư ban đầu là đổi tồn quỹ của MỌI kỳ, nặng hơn lập một phiếu
     * mới. Kiểm ngay dòng đầu. Baocaoketoan dùng cùng điều kiện này để quyết
     * định có hiện form hay không.
     *
     * Ngày để trống = bỏ số dư ban đầu (sổ tính từ phiếu đầu tiên, từ 0).
     */
    public function postSoDuQuy(){
        if (!(route('admin/phieu-thu/edit/0') && route('admin/phieu-chi/edit/0'))){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        $soQuy = $this->model('SoQuyModel');   // nạp lớp trước khi gọi hàm tĩnh của nó

        $f   = $this->__request->getFields();
        $quy = isset($f['quy']) ? (string) $f['quy'] : '';
        $ve  = 'admin/bao-cao-ke-toan?' . http_build_query(array_filter([
            'tab' => 'quy',
            'quy' => SoQuyModel::quyHopLe($quy),
            'tu'  => SoQuyModel::ngayHopLe(isset($f['tu'])  ? $f['tu']  : ''),
            'den' => SoQuyModel::ngayHopLe(isset($f['den']) ? $f['den'] : ''),
        ]));

        if (!isset(SoQuyModel::$quy[$quy])){
            Session::flash('msgError', 'Không rõ quỹ nào — chưa lưu gì.');
            $this->__response->redirect($ve); return;
        }
        $ten  = SoQuyModel::$quy[$quy]['ten'];
        $ngay = isset($f['ngay']) ? trim((string) $f['ngay']) : '';

        if ($ngay === ''){
            $soQuy->luuSoDuBanDau($quy, 0, '');
            Session::flash('msg', 'Đã bỏ số dư ban đầu quỹ ' . $ten
                                . ' — sổ tính từ phiếu đầu tiên, bắt đầu từ 0.');
            $this->__response->redirect($ve); return;
        }

        if (SoQuyModel::ngayHopLe($ngay) === null){
            Session::flash('msgError', 'Ngày số dư ban đầu không hợp lệ — chưa lưu gì.');
            $this->__response->redirect($ve); return;
        }

        /* Đọc số kiểu Việt ("1.500.000") như ô số tiền của phiếu. Không âm. */
        $soDu = $this->doTien(isset($f['so_du']) ? $f['so_du'] : 0);
        if (!$soQuy->luuSoDuBanDau($quy, $soDu, $ngay)){
            Session::flash('msgError', 'Không lưu được số dư ban đầu (không xác định được gara).');
            $this->__response->redirect($ve); return;
        }

        Session::flash('msg', 'Đã lưu số dư ban đầu quỹ ' . $ten . ': '
                            . number_format($soDu, 0, ',', '.') . ' ₫, tính từ ngày '
                            . date('d/m/Y', strtotime($ngay)) . '.');
        $this->__response->redirect($ve);
    }
}

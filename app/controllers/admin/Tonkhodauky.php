<?php

use App\core\Controller;
use App\core\Request;
use App\core\Response;
use App\core\Session;

/**
 * KHO — TỒN KHO ĐẦU KỲ: số lượng gara đang có lúc BẮT ĐẦU dùng phần mềm.
 *
 * Theo mẫu Excel khách gửi 12/10/2026. Tám cột, mỗi dòng là MỘT mã hàng tại
 * MỘT kho:
 *     Mã hàng | Tên hàng hoá | Đơn vị tính | Kho | Số lượng tồn đầu kỳ
 *     | Đơn giá vốn (VNĐ) | Thành tiền (VNĐ) | Ghi chú
 *
 * ===================================================================
 *  MÀN NÀY KHÔNG GHI THẲNG VÀO BẢNG TỒN. Nó dựng PHIẾU NHẬP NHÁP.
 * ===================================================================
 *
 * Import xong là có một phiếu `goods_receipts` loại `ton_dau_ky`, trạng thái
 * NHÁP, cho mỗi kho. Người dùng mở phiếu ở màn Phiếu nhập kho, soát lại rồi
 * bấm Ghi sổ — lúc đó StocksModel::applyIn mới chạy.
 *
 * Ba lý do đi đường vòng đó thay vì cộng thẳng vào `stocks`:
 *
 *   1. ĐẢO NGƯỢC ĐƯỢC. Mục 9 của tờ hướng dẫn dặn "chỉ import một lần để tránh
 *      cộng trùng" — dặn thì vẫn có người bấm hai lần. Là phiếu thì bấm hai
 *      lần sinh hai phiếu NHÌN THẤY ĐƯỢC, huỷ ghi sổ một cái là xong. Cộng
 *      thẳng vào bảng tồn thì số tự nhân đôi và không ai biết để sửa.
 *
 *   2. CÓ BƯỚC SOÁT. Tồn đầu kỳ sai là sai toàn bộ số liệu kho về sau; bắt
 *      người ta nhìn một lượt trước khi chốt là rẻ hơn nhiều so với đi sửa sau.
 *
 *   3. KHÔNG VIẾT LẠI BỘ MÁY KHO. Ghi sổ phiếu nhập đã lo đủ: cập nhật
 *      `stocks`, tính lại giá vốn bình quân, ghi một dòng `stock_cards` để thẻ
 *      kho có dòng mở đầu. Tự cộng tay là dựng bản thứ hai của cùng một luật,
 *      rồi hai bản lệch nhau.
 *
 * CHỐT CHỐNG CỘNG TRÙNG THẬT nằm ở bước đối chiếu: mặt hàng nào ĐÃ CÓ PHÁT
 * SINH ở kho đó thì BỎ QUA dòng ấy và nói rõ. "Tồn đầu kỳ" chỉ có nghĩa khi
 * chưa có gì trước nó; chèn vào giữa một lịch sử đang chạy là làm sai thẻ kho
 * chứ không phải khai số dư.
 *
 * MÃ HÀNG CHƯA CÓ TRONG DANH MỤC THÌ BỎ QUA, KHÔNG TỰ TẠO (chốt 12/10/2026 với
 * anh Luân). Đúng mục 4 của tờ hướng dẫn: "nên chọn/đối chiếu mã hàng có sẵn,
 * không tạo tên hoặc mã trùng cho cùng một mặt hàng". Tự tạo từ file tồn kho
 * là cách nhanh nhất sinh ra hai bản ghi cho cùng một món — một của kho tổng,
 * một do file gõ khác mã.
 */
class Tonkhodauky extends Controller {

    /** Loại phiếu nhập dùng cho tồn đầu kỳ */
    const LOAI = 'ton_dau_ky';

    /** Trần số dòng đọc từ file — chặn file quá lớn làm treo request */
    const TOI_DA_DONG = 2000;

    private $__data = [];
    private $__model, $__itemModel, $__stock, $__warehouse, $__part, $__unit;
    private $__request, $__response;

    private $routeBase = 'ton-kho-dau-ky';
    private $labelMany = 'Tồn kho đầu kỳ';
    private $viewDir   = 'admin/ton-kho-dau-ky';

    function __construct(){
        $this->__model     = $this->model('GoodsReceiptsModel');
        $this->__itemModel = $this->model('GoodsReceiptItemsModel');
        $this->__stock     = $this->model('StocksModel');
        $this->__warehouse = $this->model('WarehousesModel');
        $this->__part      = $this->model('PartsModel');
        $this->__unit      = $this->model('ProductUnitsModel');
        $this->__request   = new Request();
        $this->__response  = new Response();
    }

    private function baseData(){
        $c = &$this->__data['content'];
        $c['routeBase'] = $this->routeBase;
        $c['msg']       = Session::flash('msg');
        $c['msgError']  = Session::flash('msgError');
    }

    public function index(){
        $this->__data['sub_content'] = $this->viewDir . '/index';
        $this->__data['page_title']  = $this->labelMany;

        $this->baseData();
        $c = &$this->__data['content'];
        $c['page_name'] = $this->labelMany;
        $c['dsPhieu']   = $this->__model->dsTonDauKy();
        $c['dsKho']     = $this->__warehouse->getActive();
        $c['ketQua']    = Session::flash('ketQua');

        $this->render('layouts/admin/master_admin', $this->__data);
    }

    /**
     * File mẫu — ĐÚNG tám cột của khách, kèm một dòng ví dụ.
     *
     * CSV chứ không .xlsx: hệ thống chỉ có bộ ĐỌC xlsx (core/SpreadsheetReader),
     * không có bộ ghi, mà kéo một thư viện vào chỉ để xuất một file mẫu thì
     * không đáng. Excel mở CSV có BOM UTF-8 là ra đúng tiếng Việt, và lưu lại
     * thành .xlsx thì bộ đọc vẫn nhận.
     */
    public function fileMau(){
        $ten = ['Mã hàng', 'Tên hàng hóa', 'Đơn vị tính', 'Kho', 'Số lượng tồn đầu kỳ',
                'Đơn giá vốn (VNĐ)', 'Thành tiền (VNĐ)', 'Ghi chú'];

        /* Dòng ví dụ lấy từ chính danh mục của gara nếu có — người dùng nhìn
           thấy mã THẬT của mình thì không phải đoán định dạng. Không có hàng
           nào thì dùng dòng mẫu của khách. */
        $kho = $this->__warehouse->getDefault();
        $vd  = ['DN001', 'Dầu động cơ 5W30', 'Chai',
                !empty($kho['name']) ? $kho['name'] : 'Kho chính', 20, 350000, 7000000, ''];

        $ds = $this->__part->getLists([], '', 1, 0);
        if (!empty($ds[0])){
            $p = $ds[0];
            $vd = [$p['code'], $p['name'], !empty($p['unit_name']) ? $p['unit_name'] : '',
                   !empty($kho['name']) ? $kho['name'] : 'Kho chính', 10, 100000, 1000000, 'dòng ví dụ — xoá trước khi import'];
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mau-ton-kho-dau-ky.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");            // BOM: Excel mới đọc đúng tiếng Việt
        fputcsv($out, $ten);
        fputcsv($out, $vd);
        fclose($out);
        exit;
    }

    public function postImport(){
        if (!route('admin/' . $this->routeBase . '/add')){
            $this->__response->redirect('admin/khong-co-quyen'); return;
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE){
            Session::flash('msgError', 'Chưa chọn file để import.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['error'] !== UPLOAD_ERR_OK || !in_array($ext, ['xlsx', 'csv'], true) || $file['size'] > 5242880){
            Session::flash('msgError', 'File không hợp lệ (chỉ .xlsx hoặc .csv, tối đa 5MB).');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $rows = \App\core\SpreadsheetReader::read($file['tmp_name'], $ext);
        if (count($rows) < 2){
            Session::flash('msgError', 'File rỗng hoặc không có dòng dữ liệu nào.');
            $this->__response->redirect('admin/' . $this->routeBase); return;
        }

        $f      = $this->__request->getFields();
        $ngay   = !empty($f['ngay']) ? $f['ngay'] : date('Y-m-d');
        $ketQua = $this->xuLy($rows, $ngay);

        Session::flash('ketQua', $ketQua);
        Session::flash('msg', sprintf('Đọc xong %d dòng: nhận %d, bỏ qua %d. %s',
            $ketQua['tong'], $ketQua['nhan'], count($ketQua['boQua']),
            empty($ketQua['phieu'])
                ? 'Chưa lập được phiếu nào.'
                : 'Đã lập ' . count($ketQua['phieu']) . ' phiếu NHÁP — soát lại rồi Ghi sổ.'));
        $this->__response->redirect('admin/' . $this->routeBase);
    }

    // ===== Xử lý =====

    /**
     * Đối chiếu từng dòng rồi dựng phiếu nháp theo kho.
     *
     * @return array ['tong', 'nhan', 'boQua' => [câu giải thích], 'phieu' => [id => số phiếu]]
     */
    private function xuLy($rows, $ngay){
        $header = array_shift($rows);
        $cot    = $this->doCot($header);

        $kq = ['tong' => 0, 'nhan' => 0, 'boQua' => [], 'phieu' => []];

        if ($cot === null){
            $kq['boQua'][] = 'Không nhận ra dòng tiêu đề. Cần ít nhất ba cột: Mã hàng, Kho, '
                           . 'Số lượng tồn đầu kỳ. Tải file mẫu ở trên để đối chiếu.';
            return $kq;
        }

        $lay = function($r, $ten) use ($cot){
            return isset($cot[$ten], $r[$cot[$ten]]) ? trim((string) $r[$cot[$ten]]) : '';
        };

        /* Gom theo KHO trước, ghi sau. Một phiếu cho một kho — không thì mỗi
           dòng một phiếu, và soát mười mấy phiếu một dòng thì không ai soát. */
        $theoKho = [];
        $dong    = 1;                      // đã bỏ dòng tiêu đề

        foreach ($rows as $r){
            $dong++;
            if (implode('', array_map('strval', (array) $r)) === '') continue;   // dòng trống
            $kq['tong']++;

            if ($kq['tong'] > self::TOI_DA_DONG){
                $kq['boQua'][] = 'Chỉ xử lý tối đa ' . self::TOI_DA_DONG . ' dòng; phần còn lại bỏ qua.';
                break;
            }

            $ma    = $lay($r, 'ma');
            $tenKho = $lay($r, 'kho');
            $sl    = $this->doSo($lay($r, 'sl'));

            if ($ma === ''){     $kq['boQua'][] = "Dòng $dong: thiếu mã hàng."; continue; }
            if ($tenKho === ''){ $kq['boQua'][] = "Dòng $dong ($ma): thiếu tên kho."; continue; }

            /* Số lượng phải DƯƠNG. Mục 5 của hướng dẫn: không nhập số âm, hàng
               không có tồn thì không cần tạo dòng. Dòng 0 lọt vào thì sinh ra
               một dòng thẻ kho không nói gì. */
            if ($sl === null || $sl <= 0){
                $kq['boQua'][] = "Dòng $dong ($ma): số lượng phải là số lớn hơn 0.";
                continue;
            }

            $part = $this->__part->findByCode($ma);
            if (empty($part)){
                $kq['boQua'][] = "Dòng $dong: mã \"$ma\" chưa có trong danh mục hàng hoá — "
                               . "khai mặt hàng này ở Hàng hoá › Quản lý hàng hoá rồi import lại.";
                continue;
            }

            /* DỊCH VỤ KHÔNG CÓ TỒN (PartsModel::coKho). Để lọt vào là sinh một
               dòng tồn cho "Thay dầu", rồi mọi hoá đơn chứa nó bị kiểm tồn. */
            if (!PartsModel::coKho($part['item_type'])){
                $kq['boQua'][] = "Dòng $dong ($ma): \"" . $part['name'] . "\" là dịch vụ, không có tồn kho.";
                continue;
            }

            $kho = $this->timKho($tenKho);
            if (empty($kho)){
                $kq['boQua'][] = "Dòng $dong ($ma): không tìm thấy kho \"$tenKho\" của gara. "
                               . "Tên kho phải trùng với Kho › Danh mục kho.";
                continue;
            }

            /* CHỐT CHỐNG CỘNG TRÙNG. Mặt hàng đã có phát sinh ở kho đó thì
               "tồn đầu kỳ" không còn nghĩa gì — chèn vào là làm sai thẻ kho.
               Đây cũng là thứ chặn thật việc import hai lần: lần thứ hai mọi
               dòng đều rơi vào đây. */
            if ($this->__stock->lastMoveDate((int) $kho['id'], (int) $part['id']) !== null){
                $kq['boQua'][] = "Dòng $dong ($ma): kho \"" . $kho['name'] . "\" đã có phát sinh "
                               . "nhập/xuất cho mặt hàng này — tồn đầu kỳ chỉ khai khi chưa có gì trước nó. "
                               . "(Có phải file đã import một lần rồi không?)";
                continue;
            }

            /* CÙNG MÃ, CÙNG KHO, HAI DÒNG trong một file — cộng lại chứ không
               bỏ: người lập file hay tách theo vị trí để trong kho. */
            $k  = (int) $kho['id'];
            $pid = (int) $part['id'];
            if (!isset($theoKho[$k])) $theoKho[$k] = ['kho' => $kho, 'dong' => []];
            if (isset($theoKho[$k]['dong'][$pid])){
                $theoKho[$k]['dong'][$pid]['quantity'] += $sl;
            } else {
                $gia = $this->doSo($lay($r, 'gia'));
                $theoKho[$k]['dong'][$pid] = [
                    'part_id'   => $pid,
                    'quantity'  => $sl,
                    /* Giá vốn để trống thì 0 — mục 3 nói giá vốn chỉ điền nếu
                       gara có số liệu. 0 là hợp lệ, applyIn vẫn chạy đúng. */
                    'unit_cost' => ($gia === null || $gia < 0) ? 0.0 : $gia,
                    'note'      => $lay($r, 'ghi_chu') !== '' ? mb_substr($lay($r, 'ghi_chu'), 0, 255) : null,
                ];
            }
            $kq['nhan']++;

            /* CẢNH BÁO, KHÔNG CHẶN: đơn vị tính trong file khác danh mục. Hay
               gặp ("Cái" vs "cái"), và không ảnh hưởng số tồn — nhưng lệch đơn
               vị là dấu hiệu gõ nhầm mã, nên phải nói ra. */
            $dv = $lay($r, 'dvt');
            if ($dv !== '' && !empty($part['unit_id'])){
                $u = $this->__unit->getDetail((int) $part['unit_id']);
                if (!empty($u['name']) && mb_strtolower(trim($u['name'])) !== mb_strtolower($dv)){
                    $kq['boQua'][] = "Dòng $dong ($ma): VẪN NHẬN, nhưng đơn vị trong file là \"$dv\" "
                                   . "còn danh mục ghi \"" . $u['name'] . "\" — kiểm lại xem có nhầm mã không.";
                }
            }
        }

        if (empty($theoKho)) return $kq;

        /* THÀNH TIỀN TÍNH LẠI, không lấy cột trong file. Cột đó là công thức
           Excel; người sửa số lượng mà không mở lại Excel để tính lại là nó
           lệch, và mình thì không có cách nào biết. */
        foreach ($theoKho as $k => $g){
            $id = $this->lapPhieu($g['kho'], array_values($g['dong']), $ngay);
            if ($id > 0) $kq['phieu'][$id] = $this->__model->getDetail($id)['receipt_no'];
        }
        return $kq;
    }

    /** Lập một phiếu nhập NHÁP cho một kho. Trả về id, hoặc 0 nếu hỏng. */
    private function lapPhieu($kho, array $dong, $ngay){
        return (int) $this->__model->transaction(function($db) use ($kho, $dong, $ngay){
            $id = $this->__model->add([
                'receipt_no'   => $this->__model->nextNo(),
                'receipt_type' => self::LOAI,
                'warehouse_id' => (int) $kho['id'],
                'receipt_date' => $ngay,
                'reason'       => 'Tồn kho đầu kỳ — import từ file',
                'total_amount' => 0,          // syncForReceipt() trả tổng thật, ghi lại ngay dưới
                'status'       => 0,          // NHÁP: người dùng soát rồi mới ghi sổ
                'created_by'   => nguoi_dang_nhap_id(),
            ]);

            /* syncForReceipt() chứ không chèn tay từng dòng: nó là MỘT nơi duy
               nhất biết cách dựng dòng phiếu nhập (làm tròn tiền, bỏ dòng
               quantity <= 0, cộng tổng). Chèn tay ở đây là bản thứ hai của cùng
               luật đó, rồi hai bản lệch nhau lúc nào không biết. */
            $tong = (float) $this->__itemModel->syncForReceipt($id, $dong);
            $this->__model->edit(['total_amount' => $tong], $id);

            return $id;
        });
    }

    // ===== Helper =====

    /**
     * Dò vị trí cột từ dòng tiêu đề. Trả về [khoá => chỉ số cột], hoặc null khi
     * thiếu cột bắt buộc.
     *
     * Dò theo TÊN chứ không theo thứ tự: người dùng hay chèn thêm cột, đổi chỗ,
     * hoặc xoá cột Ghi chú. Bám cứng vào thứ tự là một file hợp lệ nhìn bằng
     * mắt lại import ra số lượng nằm ở cột giá.
     */
    private function doCot($header){
        $biet = [
            'ma'      => ['ma hang', 'ma hang hoa', 'ma', 'ma vat tu', 'code'],
            'ten'     => ['ten hang hoa', 'ten hang', 'ten', 'name'],
            'dvt'     => ['don vi tinh', 'dvt', 'don vi', 'unit'],
            'kho'     => ['kho', 'ten kho', 'warehouse'],
            'sl'      => ['so luong ton dau ky', 'so luong', 'ton dau ky', 'sl', 'quantity', 'qty'],
            'gia'     => ['don gia von vnd', 'don gia von', 'gia von', 'don gia', 'unit cost'],
            'ghi_chu' => ['ghi chu', 'note'],
        ];

        $cot = [];
        foreach ((array) $header as $i => $tieuDe){
            $chuan = $this->chuanHoa($tieuDe);
            if ($chuan === '') continue;
            foreach ($biet as $khoa => $cacTen){
                if (isset($cot[$khoa])) continue;
                if (in_array($chuan, $cacTen, true)){ $cot[$khoa] = $i; break; }
            }
        }

        // Ba cột BẮT BUỘC — thiếu một trong ba thì không dựng được dòng nào
        foreach (['ma', 'kho', 'sl'] as $bb) if (!isset($cot[$bb])) return null;
        return $cot;
    }

    /** "Số lượng tồn đầu kỳ" -> "so luong ton dau ky" (bỏ dấu, bỏ ký tự lạ) */
    private function chuanHoa($s){
        $s = slugify((string) $s);                 // bỏ dấu tiếng Việt, hạ chữ thường
        return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
    }

    /**
     * Đọc số từ ô Excel/CSV. Trả về float, hoặc null nếu không phải số.
     *
     * Nhận cả "7.000.000", "7,000,000" và "12,5". Phân biệt dấu nghìn với dấu
     * thập phân bằng cách nhìn DẤU CUỐI CÙNG: người Việt gõ "350.000" là ba
     * trăm năm mươi nghìn, không phải 350 phẩy 0. Bỏ qua chỗ này thì một ô giá
     * vốn thành ra bé đi một nghìn lần mà vẫn trông hợp lệ.
     */
    private function doSo($s){
        $s = trim((string) $s);
        if ($s === '') return null;
        $s = preg_replace('/[^\d,.\-]/', '', $s);
        if ($s === '' || $s === '-') return null;

        $cham = strrpos($s, '.');
        $phay = strrpos($s, ',');
        if ($cham !== false && $phay !== false){
            // Dấu đứng SAU là dấu thập phân, dấu kia là dấu nghìn
            $tp = $cham > $phay ? '.' : ',';
            $s  = str_replace($tp === '.' ? ',' : '.', '', $s);
            $s  = str_replace($tp, '.', $s);
        } elseif ($phay !== false){
            /* Chỉ có dấu phẩy: "12,5" là thập phân, "7,000,000" là dấu nghìn.
               Phân biệt bằng số chữ số sau dấu phẩy cuối — đúng 3 thì là nghìn. */
            $s = (strlen($s) - $phay - 1) === 3 && substr_count($s, ',') >= 1 && strlen($s) > 4
               ? str_replace(',', '', $s)
               : str_replace(',', '.', $s);
        } elseif ($cham !== false){
            $s = (strlen($s) - $cham - 1) === 3 && strlen($s) > 4
               ? str_replace('.', '', $s)
               : $s;
        }

        return is_numeric($s) ? (float) $s : null;
    }

    /** Tìm kho của gara theo TÊN hoặc MÃ, không phân biệt hoa thường */
    private function timKho($ten){
        $ten = mb_strtolower(trim($ten));
        foreach ((array) $this->__warehouse->getActive() as $k){
            if (mb_strtolower(trim($k['name'])) === $ten) return $k;
            if (mb_strtolower(trim($k['code'])) === $ten) return $k;
        }
        return null;
    }
}

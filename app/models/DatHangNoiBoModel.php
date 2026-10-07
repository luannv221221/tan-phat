<?php

use App\core\Model;
use App\core\Load;

/**
 * ĐẶT HÀNG NỘI BỘ — gara mua hàng từ KHO TỔNG.
 *
 * Gara độc lập nhưng vẫn lấy hàng từ công ty. Trước đây việc này làm tay: kho
 * tổng tự lập phiếu xuất, gara tự lập phiếu nhập, hai bên gõ lại số liệu của
 * nhau và không có gì nối hai phiếu lại.
 *
 * CÁCH LÀM: KHÔNG dựng một loại chứng từ mới. Một lần đặt sinh ra ĐÚNG HAI
 * phiếu đã có sẵn trong hệ thống, trong CÙNG một giao dịch:
 *
 *     Kho tổng : phiếu XUẤT kho  (bán cho gara — gara là một khách của kho tổng)
 *     Gara     : phiếu NHẬP kho  (mua của kho tổng — kho tổng là một NCC của gara)
 *
 * Hai phiếu ghi số của nhau ở ô Lý do nên mở phiếu nào cũng lần ra phiếu kia.
 *
 * SINH RA Ở TRẠNG THÁI NHÁP, KHÔNG TỰ GHI SỔ — cố ý, và đây là điểm quan trọng
 * nhất của thiết kế:
 *
 *   - Ghi sổ là lúc tồn kho thực sự đổi. Để một gara bấm một nút mà trừ thẳng
 *     tồn của kho tổng là bỏ qua khâu duyệt: kho tổng mất quyền nói "hết hàng"
 *     hay "để đợt sau".
 *   - Kho tổng ghi sổ phiếu xuất CHÍNH LÀ bước duyệt, làm ngay trên màn Phiếu
 *     xuất kho quen thuộc, không phải học thêm màn mới.
 *   - Gara ghi sổ phiếu nhập khi hàng về tới nơi — đúng lúc tồn của gara tăng
 *     thật, chứ không phải lúc đặt.
 *
 * Nhờ vậy toàn bộ phần khó (bút toán kho, chặn tồn âm, chặn ghi sổ lùi ngày,
 * huỷ ghi sổ) dùng lại nguyên của phiếu nhập / xuất, không viết lại dòng nào.
 *
 * KHÔNG bật `$_theoGara`: model này cố ý làm việc với HAI gara trong một lần
 * gọi. Nó chuyển gara làm việc bằng Model::epGara() quanh từng thao tác ghi,
 * vì addNew() lấy gara từ đó — đó là cách duy nhất tạo chứng từ cho gara khác
 * mà vẫn đi qua đúng lớp kiểm tra.
 */
class DatHangNoiBoModel extends Model {

    /* CỐ Ý KHÔNG khai $_table. Model này không sở hữu bảng nào — nó chỉ điều
       phối hai model khác. Ban đầu có khai `goods_receipts` cho tiện lấy
       transaction(), và chốt chặn cách ly gara bắt ngay: "model của
       `goods_receipts` chưa bật _theoGara". Đúng — một model mang tên bảng đó
       mà không lọc theo gara là thứ không nên tồn tại. Nay mượn transaction
       của chính GoodsReceiptsModel. */

    /** Mã đối tượng dựng tự động cho quan hệ nội bộ */
    const MA_NCC_TONG = 'NB-KHOTONG';

    /**
     * Đặt hàng từ kho tổng.
     *
     * @param array $dong  [['part_id' => int, 'quantity' => float], ...]
     * @param string $ghiChu
     * @return array ['pxk' => ['id','no'], 'pnk' => ['id','no']]
     * @throws \RuntimeException khi không đủ điều kiện (nêu rõ lý do cho người dùng)
     */
    public function dat(array $dong, $ghiChu = ''){
        /* Gara mua lấy qua Model::garaLoc() chứ KHÔNG gọi gara_hien_tai():
           garaLoc() tôn trọng Model::epGara() — cách cả tầng Model và bộ test
           ép gara ở dòng lệnh — còn gara_hien_tai() ở dòng lệnh luôn trả gara
           tổng, nên hàm này sẽ từ chối ngay với câu "kho tổng không đặt hàng
           của chính mình". */
        $idMua = Model::garaLoc();
        if ($idMua === null || (int) $idMua <= 0){
            throw new \RuntimeException('Không xác định được gara làm việc.');
        }
        $garaMua = Load::model('GaragesModel')->getDetail((int) $idMua);
        if (empty($garaMua['id'])){
            throw new \RuntimeException('Không xác định được gara làm việc.');
        }
        if (!empty($garaMua['is_master'])){
            throw new \RuntimeException('Kho tổng không đặt hàng của chính mình.');
        }

        $garaTong = Load::model('GaragesModel')->getMaster();
        if (empty($garaTong['id'])){
            throw new \RuntimeException('Hệ thống chưa khai gara tổng.');
        }

        $dong = $this->locDong($dong);
        if (empty($dong)) throw new \RuntimeException('Chưa chọn mặt hàng nào.');

        $idMua  = (int) $garaMua['id'];
        $idTong = (int) $garaTong['id'];
        if ($idMua === $idTong){
            throw new \RuntimeException('Kho tổng không đặt hàng của chính mình.');
        }

        // --- Kho hai đầu ---
        $khoModel = Load::model('WarehousesModel');
        $khoXuat  = $this->duoiDanhNghia($idTong, function() use ($khoModel){ return $khoModel->getDefault(); });
        $khoNhap  = $this->duoiDanhNghia($idMua,  function() use ($khoModel){ return $khoModel->getDefault(); });
        if (empty($khoXuat['id'])) throw new \RuntimeException('Kho tổng chưa khai kho nào.');
        if (empty($khoNhap['id'])) throw new \RuntimeException('Gara chưa khai kho nào để nhận hàng.');

        // --- Giá và tên hàng lấy từ danh mục chung ---
        $partModel = Load::model('PartsModel');
        $gia = [];
        foreach ($dong as $d){
            $p = $partModel->getFirst((int) $d['part_id']);
            if (empty($p) || $p['garage_id'] !== null){
                throw new \RuntimeException('Chỉ đặt được hàng của kho tổng.');
            }
            $gia[(int) $d['part_id']] = (float) $p['price'];
        }

        /* Báo thiếu tồn NGAY LÚC ĐẶT, đừng để tới lúc kho tổng ghi sổ mới đổ.
           Đây chỉ là báo trước cho tử tế — khâu ghi sổ vẫn kiểm lần nữa, vì
           giữa lúc đặt và lúc duyệt có thể có phiếu khác đã lấy mất hàng. */
        $stock = Load::model('StocksModel');
        /* HỎI TỒN TRONG NGỮ CẢNH GARA TỔNG. StocksModel từ chối mọi kho không
           thuộc gara đang làm việc (khoThuocGara) — hỏi từ phía gara mua thì
           kho của kho tổng là "kho lạ" và lúc nào cũng trả về 0, nên món nào
           cũng bị báo hết hàng. */
        $thieu = $this->duoiDanhNghia($idTong, function() use ($dong, $khoXuat, $stock, $partModel){
            $ra = [];
            foreach ($dong as $d){
                $con = (float) $stock->available((int) $khoXuat['id'], (int) $d['part_id']);
                if ($con < (float) $d['quantity']){
                    $p = $partModel->getFirst((int) $d['part_id']);
                    $ra[] = (!empty($p['name']) ? $p['name'] : ('#' . $d['part_id']))
                          . ' (còn ' . rtrim(rtrim(number_format($con, 2, '.', ''), '0'), '.') . ')';
                }
            }
            return $ra;
        });
        if (!empty($thieu)){
            throw new \RuntimeException('Kho tổng không đủ hàng: ' . implode('; ', $thieu));
        }

        // --- Đối tượng hai đầu ---
        $nccTong   = $this->doiTuongKhoTong($idMua, $garaTong);
        $khachGara = $this->doiTuongGara($idTong, $garaMua);

        $ketQua = [];
        Load::model('GoodsReceiptsModel')->transaction(function() use (
            $dong, $gia, $ghiChu, $idMua, $idTong, $khoXuat, $khoNhap,
            $nccTong, $khachGara, $garaMua, $garaTong, &$ketQua
        ){
            $homNay = date('Y-m-d');
            $tong   = 0.0;
            foreach ($dong as $d) $tong += $gia[(int) $d['part_id']] * (float) $d['quantity'];

            // 1) Phiếu XUẤT ở kho tổng
            $px = $this->duoiDanhNghia($idTong, function() use (
                $dong, $gia, $homNay, $tong, $khoXuat, $khachGara, $garaMua, $ghiChu
            ){
                $m   = Load::model('GoodsIssuesModel');
                $no  = $m->nextNo();
                $id  = (int) $m->add([
                    'issue_no'     => $no,
                    'issue_type'   => 'ban',
                    'warehouse_id' => (int) $khoXuat['id'],
                    'partner_id'   => (int) $khachGara['id'],
                    'partner_name' => $garaMua['name'],
                    'issue_date'   => $homNay,
                    'reason'       => 'Đặt hàng nội bộ — gara ' . $garaMua['name']
                                    . ($ghiChu !== '' ? ' — ' . $ghiChu : ''),
                    /* PHIẾU XUẤT để tổng tiền = 0, cố ý. Phiếu xuất ghi GIÁ VỐN,
                       mà giá vốn bình quân chỉ biết được lúc ghi sổ — chính
                       Goodsissues::post() điền vào. Nhét giá bán vào đây là mâu
                       thuẫn với dòng hàng (syncForIssue luôn ghi đơn giá 0), và
                       tới lúc ghi sổ sẽ bị tính lại đè lên. */
                    'total_amount' => 0,
                    'status'       => 0,
                ]);
                Load::model('GoodsIssueItemsModel')->syncForIssue($id, $this->dongChungTu($dong, $gia));
                return ['id' => $id, 'no' => $no];
            });

            // 2) Phiếu NHẬP ở gara
            $pn = $this->duoiDanhNghia($idMua, function() use (
                $dong, $gia, $homNay, $tong, $khoNhap, $nccTong, $garaTong, $ghiChu, $px
            ){
                $m   = Load::model('GoodsReceiptsModel');
                $no  = $m->nextNo();
                $id  = (int) $m->add([
                    'receipt_no'   => $no,
                    'receipt_type' => 'mua',
                    'warehouse_id' => (int) $khoNhap['id'],
                    'partner_id'   => (int) $nccTong['id'],
                    'partner_name' => $garaTong['name'],
                    'receipt_date' => $homNay,
                    'reason'       => 'Đặt hàng nội bộ theo phiếu xuất ' . $px['no']
                                    . ($ghiChu !== '' ? ' — ' . $ghiChu : ''),
                    'total_amount' => $tong,
                    'status'       => 0,
                ]);
                Load::model('GoodsReceiptItemsModel')->syncForReceipt($id, $this->dongChungTu($dong, $gia));
                return ['id' => $id, 'no' => $no];
            });

            /* Nối ngược phiếu xuất về phiếu nhập — làm SAU vì lúc lập phiếu xuất
               chưa biết số phiếu nhập. Mở phiếu nào cũng lần ra phiếu kia. */
            $this->duoiDanhNghia($idTong, function() use ($px, $pn, $garaMua, $ghiChu){
                Load::model('GoodsIssuesModel')->edit([
                    'reason' => 'Đặt hàng nội bộ — gara ' . $garaMua['name']
                              . ' — phiếu nhập ' . $pn['no']
                              . ($ghiChu !== '' ? ' — ' . $ghiChu : ''),
                ], $px['id']);
                return true;
            });

            $ketQua = ['pxk' => $px, 'pnk' => $pn];
        });

        return $ketQua;
    }

    // ===== Helper =====

    /**
     * Chạy $viec dưới danh nghĩa gara $garaId, rồi trả lại ngay.
     *
     * Gọi thẳng Model::trongGara() — KHÔNG tự bật tắt bằng epGara(). epGara()
     * chỉ có tác dụng ở dòng lệnh, nên bản đầu tiên chạy thử dòng lệnh thì
     * đúng, mà qua web thì phiếu xuất rơi vào gara của người đang bấm.
     */
    private function duoiDanhNghia($garaId, callable $viec){
        return Model::trongGara((int) $garaId, $viec);
    }

    /** Bỏ dòng rỗng, gộp dòng trùng mặt hàng, ép số lượng > 0 */
    private function locDong(array $dong){
        $gom = [];
        foreach ($dong as $d){
            $pid = isset($d['part_id']) ? (int) $d['part_id'] : 0;
            $sl  = isset($d['quantity']) ? (float) $d['quantity'] : 0;
            if ($pid <= 0 || $sl <= 0) continue;
            if (!isset($gom[$pid])) $gom[$pid] = 0.0;
            $gom[$pid] += $sl;
        }
        $ra = [];
        foreach ($gom as $pid => $sl) $ra[] = ['part_id' => $pid, 'quantity' => $sl];
        return $ra;
    }

    private function dongChungTu(array $dong, array $gia){
        $ra = [];
        foreach ($dong as $d){
            $pid = (int) $d['part_id'];
            $sl  = (float) $d['quantity'];
            $ra[] = [
                'part_id'   => $pid,
                'quantity'  => $sl,
                'unit_cost' => $gia[$pid],
                'amount'    => $gia[$pid] * $sl,
                'note'      => null,
            ];
        }
        return $ra;
    }

    /** Kho tổng, nhìn từ phía gara: một NHÀ CUNG CẤP trong danh sách Đối tượng */
    private function doiTuongKhoTong($garaMuaId, array $garaTong){
        return $this->duoiDanhNghia($garaMuaId, function() use ($garaTong){
            $P  = Load::model('PartnersModel');
            $co = $P->findByCode(self::MA_NCC_TONG);
            if (!empty($co)) return $co;
            $P->add([
                'code'     => self::MA_NCC_TONG,
                'name'     => $garaTong['name'],
                'type'     => 'supplier',
                'tax_code' => !empty($garaTong['tax_code']) ? $garaTong['tax_code'] : null,
                'phone'    => !empty($garaTong['phone']) ? $garaTong['phone'] : null,
                'address'  => !empty($garaTong['address']) ? $garaTong['address'] : null,
                'status'   => 1,
            ]);
            return $P->findByCode(self::MA_NCC_TONG);
        });
    }

    /** Gara, nhìn từ phía kho tổng: một KHÁCH HÀNG trong danh sách Đối tượng */
    private function doiTuongGara($garaTongId, array $garaMua){
        $ma = 'NB-' . strtoupper(trim((string) $garaMua['code']));
        return $this->duoiDanhNghia($garaTongId, function() use ($ma, $garaMua){
            $P  = Load::model('PartnersModel');
            $co = $P->findByCode($ma);
            if (!empty($co)) return $co;
            $P->add([
                'code'     => $ma,
                'name'     => $garaMua['name'],
                'type'     => 'customer',
                'tax_code' => !empty($garaMua['tax_code']) ? $garaMua['tax_code'] : null,
                'phone'    => !empty($garaMua['phone']) ? $garaMua['phone'] : null,
                'address'  => !empty($garaMua['address']) ? $garaMua['address'] : null,
                'status'   => 1,
            ]);
            return $P->findByCode($ma);
        });
    }
}

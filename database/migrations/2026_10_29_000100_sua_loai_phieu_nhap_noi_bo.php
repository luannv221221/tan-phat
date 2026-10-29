<?php
/**
 * SỬA LOẠI PHIẾU NHẬP CỦA ĐẶT HÀNG NỘI BỘ: 'mua' -> 'nhap_mua'.
 *
 * Phát hiện 29/10/2026. Màn "Đặt hàng kho tổng" (migration 000091) sinh phiếu
 * nhập bên gara với `receipt_type` = 'mua' — một giá trị KHÔNG CÓ trong
 * GoodsReceiptsModel::$types. Hai hậu quả, cả hai đều im lặng:
 *
 *   1. Màn Phiếu nhập kho in ra chữ trần "mua". View rơi về
 *      `{{$types[...] ?? $item['receipt_type']}}` nên không chỗ nào vỡ ra,
 *      chỉ là cái nhãn trông lạ.
 *
 *   2. NẶNG HƠN: báo cáo "Gara còn nợ nhà cung cấp" (migration 000097) lọc
 *      đúng `receipt_type = 'nhap_mua'`, nên BỎ SÓT toàn bộ phiếu loại 'mua'.
 *      Gara đặt hàng kho tổng là nợ kho tổng thật, mà báo cáo không thấy —
 *      trên máy chạy thử lệch 3.460.000 đ.
 *
 * GARA MUA CỦA KHO TỔNG ĐÚNG LÀ NHẬP MUA: kho tổng là một nhà cung cấp của
 * gara (chính DatHangNoiBoModel đã ghi như vậy ở ghi chú đầu lớp, và nó tạo
 * sẵn một dòng `partners` cho kho tổng). Nên gộp về 'nhap_mua' chứ không khai
 * thêm một loại thứ tư — chốt với khách 29/10/2026.
 *
 * CHẠY LẠI ĐƯỢC: lần hai không còn dòng nào khớp, UPDATE sửa 0 dòng.
 */

use App\core\Migration;

return new class extends Migration {

    public function up(){
        if (!$this->hasTable('goods_receipts')){
            echo "  (chua co bang `goods_receipts`, bo qua)\n"; return;
        }

        $r = $this->db->firstRaw("SELECT COUNT(*) AS c FROM `goods_receipts` WHERE `receipt_type` = ?", ['mua']);
        $n = (int) (isset($r['c']) ? $r['c'] : 0);
        if ($n === 0){
            echo "  Khong con phieu nao loai 'mua'.\n"; return;
        }

        $this->db->query("UPDATE `goods_receipts` SET `receipt_type` = 'nhap_mua' WHERE `receipt_type` = 'mua'");
        echo "  Da doi $n phieu tu 'mua' sang 'nhap_mua' (dat hang kho tong).\n";
        echo "  Bao cao \"Gara con no NCC\" tu gio tinh ca may phieu nay.\n";
    }

    /**
     * KHÔNG quay lui.
     *
     * Trả về 'mua' là dựng lại đúng cái lỗi vừa sửa, mà lần này còn tệ hơn: sau
     * khi chạy up() thì KHÔNG PHÂN BIỆT ĐƯỢC phiếu nào vốn là 'mua' và phiếu
     * nào vốn đã là 'nhap_mua' — đổi ngược hết là gán nhầm cả những phiếu nhập
     * mua từ nhà cung cấp bên ngoài.
     *
     * Migration sửa dữ liệu hỏng thì một chiều là đúng. Cần quay lui thật thì
     * phục hồi từ bản sao lưu.
     */
    public function down(){
        echo "  (khong quay lui: doi nguoc se gan nham ca phieu nhap mua tu NCC ngoai)\n";
    }
};

<?php

use App\core\Controller;
use App\core\VietQr;

/**
 * TRANG THANH TOÁN CÔNG KHAI — /thanh-toan/<mã> (anh yêu cầu 18/11/2026: "cho anh
 * cái nút gửi link qrcode luôn không phải vào in").
 *
 * Gara bấm "Gửi" ở danh sách hoá đơn -> khách nhận link này qua Zalo / SMS, mở
 * ra (không cần đăng nhập) là thấy mã QR VietQR + số tài khoản + số tiền + nội
 * dung, kèm nút tải ảnh mã (trên điện thoại khách không quét được màn hình của
 * chính mình) và nút chép từng dòng.
 *
 * AI MỞ ĐƯỢC: ai có link. Mã là 32 hex ngẫu nhiên (SalesInvoicesModel::
 * maThanhToan), không đoán được. Vì link có thể bị chuyển tiếp, trang CHỈ in thứ
 * cần để trả tiền: gara, số hoá đơn, ngày, biển số xe, số tiền, tài khoản nhận.
 * KHÔNG in tên / số điện thoại / địa chỉ khách, không in dòng hàng.
 *
 * TIỀN VỀ GARA LẬP HOÁ ĐƠN: tài khoản đọc theo `garage_id` CỦA HOÁ ĐƠN
 * (cau_hinh_in_an), không theo tên miền đang mở. Đọc lại mỗi lần mở — gara đổi
 * tài khoản thì link đã gửi cũng đổi theo, không in mãi số tài khoản cũ.
 *
 * Ra HTML trọn vẹn bằng require (như mẫu in), không qua layout trang khách: đây
 * là một trang làm đúng một việc, mở nhanh trên điện thoại.
 */
class Thanhtoan extends Controller {

    public function hoaDon($ma = ''){
        /* Không lưu đệm, không lên Google, không lộ link (có mã) qua Referer
           khi trang nạp thư viện vẽ QR từ cdnjs. */
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');

        $hd = $this->model('SalesInvoicesModel')->theoMaThanhToan($ma);

        $cf = []; $qr = null;
        if (empty($hd)){
            http_response_code(404);
        } else {
            $cf = cau_hinh_in_an((int) $hd['garage_id']);
            $qr = VietQr::choHoaDon($cf, $hd['total_amount'], $hd['invoice_no']);
        }

        require __DIR__ . '/../views/storefront/thanh-toan.php';
    }
}

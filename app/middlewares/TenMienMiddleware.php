<?php

namespace App\app\middlewares;

use App\core\Middleware;
use App\core\Load;

/**
 * CHẶN HOST LẠ — chạy cho mọi request, trước khi vào controller.
 *
 * Mỗi gara có tên miền riêng (bảng `garage_domains`). Host nào không khai thì
 * không thuộc gara nào, và KHÔNG được rơi về gara tổng: rơi về nghĩa là bất kỳ
 * tên miền nào trỏ bừa vào máy chủ cũng xem được dữ liệu Tân Phát.
 *
 * NHƯNG CHẶN VÔ ĐIỀU KIỆN THÌ GÃY ĐÚNG LÚC ĐẨY CODE. Nên có hai cửa thoát,
 * và cả hai đều cố ý:
 *
 *   1. CHƯA KHAI TÊN MIỀN NÀO. Bảng rỗng = tính năng chưa bật. Giữ nguyên cách
 *      cũ. Nhờ vậy đẩy code lên máy chủ TRƯỚC khi trỏ DNS xong vẫn chạy bình
 *      thường, không phải canh hai việc khít nhau.
 *
 *   2. HOST NỘI BỘ (localhost, *.test, tên máy trong mạng LAN). Máy lập trình
 *      và bộ test không có tên miền gara nào trỏ về; chặn ở đây là không ai
 *      chạy thử được nữa.
 *
 * Ngoài hai cửa đó, host lạ nhận trang "gara không tồn tại" kèm mã 404 — không
 * phải 500: đây là địa chỉ không có thật, không phải máy chủ hỏng.
 */
class TenMienMiddleware extends Middleware {

    public function handle(){
        if (PHP_SAPI === 'cli') return true;

        // Host khớp một gara đang hoạt động -> xong
        if (gara_theo_ten_mien() !== null) return true;

        // Máy chạy thử
        if (la_host_noi_bo(host_hien_tai())) return true;

        // Chưa khai tên miền nào -> tính năng chưa bật
        if (!Load::model('GarageDomainsModel')->daKhaiTenMien()) return true;

        http_response_code(404);
        $host = host_hien_tai();
        require 'app/errors/gara-khong-ton-tai.php';
        exit;
    }
}

<?php

require_once __DIR__ . '/AccVoucherBase.php';

/**
 * KẾ TOÁN — PHIẾU THU.
 *
 * Toàn bộ nghiệp vụ nằm ở AccVoucherBase; ở đây chỉ đặt chiều tiền và mấy nhãn
 * chữ. Phiếu chi là lớp sinh đôi (Phieuchi) — xem ghi chú đầu AccVoucherBase
 * về việc vì sao hai màn này dùng lớp nền còn mấy màn danh mục thì không.
 *
 * Quyền do RoleMiddleware gác theo module `phieu-thu` (khai từ migration
 * 000082, cấp quyền ghi ở 000096).
 */
class Phieuthu extends AccVoucherBase {

    protected $loai      = 'thu';
    protected $routeBase = 'phieu-thu';
    protected $labelOne  = 'phiếu thu';
    protected $labelMany = 'Phiếu thu';
}

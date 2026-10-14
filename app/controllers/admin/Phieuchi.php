<?php

require_once __DIR__ . '/AccVoucherBase.php';

/**
 * KẾ TOÁN — PHIẾU CHI.
 *
 * Lớp sinh đôi của Phieuthu: toàn bộ nghiệp vụ nằm ở AccVoucherBase, ở đây chỉ
 * đặt chiều tiền và mấy nhãn chữ.
 *
 * Quyền do RoleMiddleware gác theo module `phieu-chi` (khai từ migration
 * 000082, cấp quyền ghi ở 000096).
 */
class Phieuchi extends AccVoucherBase {

    protected $loai      = 'chi';
    protected $routeBase = 'phieu-chi';
    protected $labelOne  = 'phiếu chi';
    protected $labelMany = 'Phiếu chi';
}

<?php
/**
 * Trang giữ chỗ cho hai màn Kế toán (Phiếu thu / Phiếu chi).
 *
 * Màn hình đã có mặt trên menu nhưng chưa có nghiệp vụ. Để trang này thay vì
 * bỏ trống route: route chưa khai thì RoleMiddleware không gác được (xem chú
 * thích ở routes/web.php), còn khai route mà không có view thì bấm vào ra 404 —
 * trông như hỏng chứ không phải như chưa làm.
 *
 * Xoá file này khi dựng nghiệp vụ thật.
 */
?>
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>{{$page_name}}</h3>
    </div>
    <div class="card-body text-center py-5">
        <div class="h1 text-muted mb-3"><i class="fas fa-tools"></i></div>
        <h4 class="mb-2">Đang xây dựng</h4>
        <p class="text-muted mb-4">
            Màn hình <strong>{{$page_name}}</strong> mới có chỗ đứng trên menu,
            chưa nhập liệu được.
        </p>
        <a href="{{_WEB_URL.'/admin'}}" class="btn btn-sm btn-default">
            <i class="fas fa-arrow-left mr-1"></i> Về Tổng quan
        </a>
    </div>
</div>

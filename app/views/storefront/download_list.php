<?php
/* TRANG TẢI VỀ (tài liệu khách 05/11/2026).
   Mỗi dòng chỉ dán LINK GIÁN TIẾP — link trực tiếp không bao giờ xuất hiện
   trên trang công khai, nó là thứ gara gửi riêng. Đưa nó lên đây là vô hiệu
   hoá luôn cờ "chỉ thành viên". */
$doCo = function($b){
    $b = (float) $b;
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return round($b / 1024) . ' KB';
    return (int) $b . ' B';
};
$bieuTuong = function($ten){
    $d = strtolower(pathinfo((string) $ten, PATHINFO_EXTENSION));
    if ($d === 'pdf') return 'fa-file-pdf text-danger';
    if (in_array($d, ['doc', 'docx'], true))  return 'fa-file-word text-primary';
    if (in_array($d, ['xls', 'xlsx', 'csv'], true)) return 'fa-file-excel text-success';
    if (in_array($d, ['ppt', 'pptx'], true))  return 'fa-file-powerpoint text-warning';
    if (in_array($d, ['zip', 'rar', '7z'], true)) return 'fa-file-zipper text-secondary';
    if (in_array($d, ['jpg', 'jpeg', 'png'], true)) return 'fa-file-image text-info';
    return 'fa-file text-muted';
};
?>
<div class="content">
<div class="sf-page">
<div class="container py-4">

    <h1 class="h3 mb-3">Tải về</h1>

    <?php if (!empty($msgError)): ?>
    <div class="alert alert-warning"><i class="fa fa-exclamation-circle mr-1"></i> <?php echo e($msgError); ?></div>
    <?php endif; ?>

    <?php if (empty($list)): ?>
        <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">
            <div style="font-size:42px">📂</div>
            <p class="mb-0">Chưa có tài liệu nào để tải.</p>
        </div></div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($list as $t): ?>
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm h-100"><div class="card-body d-flex">
                    <div class="mr-3" style="font-size:30px">
                        <i class="fa <?php echo e($bieuTuong($t['file_name'])); ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="h6 mb-1"><?php echo e($t['title']); ?></h2>
                        <?php if (!empty($t['description'])): ?>
                        <p class="text-muted small mb-2"><?php echo e($t['description']); ?></p>
                        <?php endif; ?>
                        <p class="text-muted small mb-2">
                            <?php echo e($doCo($t['file_size'])); ?>
                            <?php if ((int) $t['require_login'] === 1): ?>
                                · <span class="text-warning"><i class="fa fa-lock"></i> Dành cho thành viên</span>
                            <?php endif; ?>
                        </p>
                        <a class="btn btn-sm btn-primary"
                           href="<?php echo e(_WEB_URL . '/tai-ve/' . $t['slug']); ?>">
                            <i class="fa fa-download mr-1"></i> Tải về
                        </a>
                    </div>
                </div></div>
            </div>
        <?php endforeach; ?>
        </div>

        <?php if (empty($daDangNhap)): ?>
        <p class="text-muted small mt-3 mb-0">
            Tài liệu có dấu <i class="fa fa-lock"></i> cần
            <a href="<?php echo e(_WEB_URL . '/thanh-vien/dang-nhap'); ?>">đăng nhập</a>
            hoặc <a href="<?php echo e(_WEB_URL . '/thanh-vien/dang-ky'); ?>">đăng ký tài khoản</a>.
        </p>
        <?php endif; ?>
    <?php endif; ?>

</div>
</div>
</div>

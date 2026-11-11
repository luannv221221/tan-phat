<?php
/**
 * Thanh trên cùng.
 *
 * Bên trái: tên hệ thống + tên trang hiện tại (lấy từ $page_name/$page_title).
 * Bên phải: avatar -> dropdown đăng xuất.
 */
$__pageLabel = '';
if (!empty($page_name))       { $__pageLabel = $page_name; }
elseif (!empty($page_title))  { $__pageLabel = $page_title; }
?>
<header class="adm-topbar">

    <button type="button" class="adm-topbar__burger" aria-label="Mở menu">
        <?php echo icon('menu'); ?>
    </button>

    <span class="adm-topbar__title">Tân Phát</span>
    <?php if ($__pageLabel !== ''): ?>
        <span class="adm-topbar__sep"><?php echo e($__pageLabel); ?></span>
    <?php endif; ?>

    <?php
    /* THAO TÁC NHANH — tài liệu khách 05/11/2026, CRM mục 5: "Thao tác nhanh,
       gắn link của báo giá, đơn mua, danh sách khách hàng", kèm ảnh mẫu thanh
       trên ghi "Bán hang -- mua hang --chăm sóc".

       Ba việc mở đầu ba luồng chính của một gara. Trỏ thẳng vào màn LẬP mới
       chứ không vào danh sách: vào danh sách thì vẫn phải tìm nút "Thêm", mà
       nút đó nằm ở chỗ khác nhau trên từng màn.

       LỌC THEO QUYỀN bằng route(). Thiếu bước này thì nhân viên kho vẫn thấy
       "Bán hàng" trên thanh trên, bấm vào ăn trang từ chối — một cái bẫy bày
       ngay chỗ dễ bấm nhất. CSKH trỏ vào danh sách (không phải màn thêm) vì
       chăm sóc là xem lại khách đã có; khai khách mới là màn Đối tượng. */
    $__nhanh = [];
    foreach ([['admin/quotations/add',      'shopping-cart', 'Bán hàng'],
              ['admin/purchase-orders/add', 'truck',         'Mua hàng'],
              ['admin/customers',           'headset',       'Chăm sóc']] as $__m){
        $__u = route($__m[0]);              // false nếu không có quyền
        if ($__u) $__nhanh[] = [$__u, $__m[1], $__m[2]];
    }
    ?>
    <?php if (!empty($__nhanh)): ?>
    <nav class="adm-topbar__nhanh" aria-label="Thao tác nhanh">
        <?php foreach ($__nhanh as $__n): ?>
            <a href="<?php echo e($__n[0]); ?>" title="<?php echo e($__n[2]); ?>">
                <?php echo icon($__n[1]); ?><span><?php echo e($__n[2]); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <div class="adm-topbar__right">

        <?php
        /* Gara của tài khoản — chỉ để đọc.
           Không còn ô đổi gara (22/09/2026): các gara là doanh nghiệp độc lập,
           "đổi gara" chính là xem dữ liệu của gara khác. Muốn làm ở gara khác
           thì dùng tài khoản của gara đó. */
        $garaHienTai = isset($garaHienTai) ? $garaHienTai : null;
        ?>
        <?php if (!empty($garaHienTai['name'])): ?>
        <span class="adm-user-toggle px-2 adm-topbar__gara" title="Gara của tài khoản">
            <?php echo icon('map-pin'); ?>
            <span class="d-none d-md-inline ml-1"><?php echo e($garaHienTai['name']); ?></span>
        </span>
        <?php endif; ?>

        <div class="dropdown">
            <a href="#" class="adm-user-toggle" data-toggle="dropdown" aria-expanded="false">
                <span class="adm-avatar"><?php echo icon('circle-user'); ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <span class="dropdown-header">
                    <?php echo e(!empty($infoUser['name']) ? $infoUser['name'] : 'Tài khoản'); ?>
                    <?php if (!empty($infoUser['email'])): ?>
                        <br><?php echo e($infoUser['email']); ?>
                    <?php endif; ?>
                </span>
                <div class="dropdown-divider"></div>
                <a href="<?php echo _WEB_URL; ?>" target="_blank" class="dropdown-item">
                    <?php echo icon('external-link'); ?> Xem trang chủ
                </a>
                <?php /* ĐỔI MẬT KHẨU — khách nhắn 11/11/2026 "thiếu đổi
                         password em nhé". Không hỏi route() như các mục khác:
                         màn này cố ý không có module nên không có quyền nào
                         để hỏi, và mọi tài khoản đăng nhập được đều phải thấy
                         nó. Xem app/controllers/admin/Doimatkhau.php. */ ?>
                <a href="<?php echo _WEB_URL.'/admin/doi-mat-khau'; ?>" class="dropdown-item">
                    <?php /* `lock` chứ không phải `key`: bộ sprite Lucide nhúng
                             trong dự án không có biểu tượng `key`, mà icon()
                             thiếu hình thì in ra một ô trống chứ không báo lỗi. */ ?>
                    <?php echo icon('lock'); ?> Đổi mật khẩu
                </a>
                <a href="<?php echo _WEB_URL.'/dang-xuat'; ?>" class="dropdown-item">
                    <?php echo icon('log-out'); ?> Đăng xuất
                </a>
            </div>
        </div>
    </div>
</header>

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
    /* THAO TÁC NHANH — mỗi người TỰ ĐẶT (khách yêu cầu 12/11/2026).

       Gốc: tài liệu khách 05/11/2026, CRM mục 5 ("thao tác nhanh, gắn link của
       báo giá, đơn mua, danh sách khách hàng") kèm ảnh mẫu ghi "Bán hàng — mua
       hàng — chăm sóc". Lúc đó em viết cứng đúng ba nút. Nay ba nút đó chỉ còn
       là BỘ MẶC ĐỊNH cho người chưa tuỳ chỉnh (cột `users`.`shortcuts` NULL),
       còn ai đặt rồi thì thấy đúng những gì họ chọn — xem loi_tat_cua_toi()
       và màn admin/loi-tat.

       LỌC THEO QUYỀN bằng route() NGAY LÚC VẼ, không chỉ lúc lưu: quyền đổi
       được sau khi họ ghim, và nút bấm vào ăn trang từ chối là cái bẫy bày
       đúng chỗ dễ bấm nhất. */
    $__nhanh = loi_tat_cua_toi(
        isset($infoUser['shortcuts']) ? $infoUser['shortcuts'] : null,
        isset($listModules) ? $listModules : []
    );
    ?>
    <?php /* LUÔN vẽ thanh, kể cả khi không còn nút nào: nút bánh răng là cách DUY
             NHẤT để người đã bỏ hết lối tắt tìm lại chỗ đặt. Ẩn cả thanh khi
             rỗng thì họ không còn lối nào quay lại. */ ?>
    <nav class="adm-topbar__nhanh" aria-label="Thao tác nhanh">
        <?php foreach ($__nhanh as $__n): ?>
            <a href="<?php echo e($__n[0]); ?>" title="<?php echo e($__n[2]); ?>">
                <?php echo icon($__n[1]); ?><span><?php echo e($__n[2]); ?></span>
            </a>
        <?php endforeach; ?>
        <a href="<?php echo e(_WEB_URL . '/admin/loi-tat'); ?>" class="adm-topbar__nhanh-cfg"
           title="Tuỳ chỉnh lối tắt" aria-label="Tuỳ chỉnh lối tắt">
            <?php echo icon('settings'); ?>
        </a>
    </nav>

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
                <?php /* "Lối tắt của tôi" cũng nằm ở đây vì thanh lối tắt ẩn dưới 992px
                         (xem admin-theme.css): trên điện thoại đây là lối DUY NHẤT tới
                         màn đặt lối tắt. */ ?>
                <a href="<?php echo _WEB_URL.'/admin/loi-tat'; ?>" class="dropdown-item">
                    <?php echo icon('star'); ?> Lối tắt của tôi
                </a>
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

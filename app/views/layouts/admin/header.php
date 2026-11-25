<?php
/**
 * Thanh trên cùng.
 *
 * Bên trái: lối tắt. Bên phải: công ty của tài khoản + avatar -> dropdown.
 *
 * KHÔNG còn chữ "Tân Phát <tên trang>" (anh khoanh trên ảnh chụp 25/11/2026: "bỏ
 * 2 cái này đi"). Chữ "Tân Phát" viết cứng nên công ty khác đăng nhập cũng thấy;
 * tên trang đã có ngay trong thẻ nội dung bên dưới và trên tab trình duyệt.
 * Trước đó (13/11) mới chỉ ẩn dưới 992px.
 */
?>
<header class="adm-topbar">

    <button type="button" class="adm-topbar__burger" aria-label="Mở menu">
        <?php echo icon('menu'); ?>
    </button>

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
    <?php /* Không còn nút bánh răng "Tuỳ chỉnh lối tắt" ở cuối dải (anh gạch chân
             trên ảnh chụp 25/11/2026: "bỏ 2 cái này đi"). Chỗ đặt lối tắt vẫn ở
             menu tài khoản -> "Lối tắt của tôi", kể cả khi đã bỏ hết lối tắt.
             Dải vẫn vẽ khi rỗng để cụm tài khoản giữ nguyên chỗ bên phải. */ ?>
    <nav class="adm-topbar__nhanh" aria-label="Thao tác nhanh">
        <?php foreach ($__nhanh as $__n): ?>
            <a href="<?php echo e($__n[0]); ?>" title="<?php echo e($__n[2]); ?>">
                <?php echo icon($__n[1]); ?><span><?php echo e($__n[2]); ?></span>
            </a>
        <?php endforeach; ?>
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
        <span class="adm-user-toggle px-2 adm-topbar__gara" title="Công ty của tài khoản">
            <?php echo icon('map-pin'); ?>
            <span class="d-none d-md-inline ml-1"><?php echo e($garaHienTai['name']); ?></span>
        </span>
        <?php endif; ?>

        <div class="dropdown">
            <a href="#" class="adm-user-toggle" data-toggle="dropdown" aria-expanded="false" aria-label="Tài khoản">
                <?php /* ẢNH ĐẠI DIỆN tự đặt ở Hồ sơ của tôi (25/11/2026); chưa có
                         ảnh thì vẫn là biểu tượng người như trước. */ ?>
                <?php if (!empty($infoUser['avatar'])): ?>
                <span class="adm-avatar adm-avatar--anh"><img src="<?php echo e(media_url($infoUser['avatar'])); ?>" alt=""/></span>
                <?php else: ?>
                <span class="adm-avatar"><?php echo icon('circle-user'); ?></span>
                <?php endif; ?>
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
                <?php /* HỒ SƠ CỦA TÔI — khách ghi 25/11/2026 "cho sửa profile
                         thay cho đổi mật khẩu, ảnh, tên, địa chỉ…". Thay mục
                         "Đổi mật khẩu" cũ (11/11/2026): đổi mật khẩu nay là một
                         khung trong trang hồ sơ. Không hỏi route() như các mục
                         khác: màn này cố ý không có module nên không có quyền
                         nào để hỏi, và mọi tài khoản đăng nhập được đều phải
                         thấy nó. Xem app/controllers/admin/Hoso.php. */ ?>
                <?php /* "Lối tắt của tôi": lối DUY NHẤT tới màn đặt lối tắt, từ khi bỏ
                         nút bánh răng trên thanh (25/11/2026). Đừng gỡ. */ ?>
                <a href="<?php echo _WEB_URL.'/admin/loi-tat'; ?>" class="dropdown-item">
                    <?php echo icon('star'); ?> Lối tắt của tôi
                </a>
                <a href="<?php echo _WEB_URL.'/admin/ho-so'; ?>" class="dropdown-item">
                    <?php /* `user` có trong bộ sprite Lucide nhúng trong dự án;
                             icon() thiếu hình thì in ra một ô trống chứ không báo lỗi. */ ?>
                    <?php echo icon('user'); ?> Hồ sơ của tôi
                </a>
                <a href="<?php echo _WEB_URL.'/dang-xuat'; ?>" class="dropdown-item">
                    <?php echo icon('log-out'); ?> Đăng xuất
                </a>
            </div>
        </div>
    </div>
</header>

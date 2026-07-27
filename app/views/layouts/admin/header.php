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

    <div class="adm-topbar__right">
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
                <a href="<?php echo _WEB_URL.'/dang-xuat'; ?>" class="dropdown-item">
                    <?php echo icon('log-out'); ?> Đăng xuất
                </a>
            </div>
        </div>
    </div>
</header>

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

        <?php
        /* Ô đổi gara.
           Chỉ hiện với người có quyền xem module gara — đổi gara là việc của
           người quản lý nhiều chi nhánh, thợ không cần. route() trả về false
           khi nhóm không có quyền, nên điều kiện này vừa là quyền vừa là giao
           diện, không phải kiểm hai lần hai chỗ.
           Ẩn luôn khi cả hệ thống chỉ có một gara: một ô thả xuống chỉ có đúng
           một lựa chọn là thứ vô nghĩa chiếm chỗ. */
        $dsGara      = isset($dsGara) && is_array($dsGara) ? $dsGara : [];
        $garaHienTai = isset($garaHienTai) ? $garaHienTai : null;
        $__doiGara   = route('admin/garages') && count($dsGara) > 1;
        ?>
        <?php if ($__doiGara): ?>
        <div class="dropdown adm-topbar__gara">
            <a href="#" class="adm-user-toggle px-2" data-toggle="dropdown" aria-expanded="false" title="Gara đang làm việc">
                <?php echo icon('map-pin'); ?>
                <span class="d-none d-md-inline ml-1">
                    <?php echo e(!empty($garaHienTai['name']) ? $garaHienTai['name'] : 'Chọn gara'); ?>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <span class="dropdown-header">Đang làm việc tại</span>
                <div class="dropdown-divider"></div>
                <?php foreach ($dsGara as $__g): ?>
                <a href="<?php echo _WEB_URL . '/admin/garages/doi/' . (int) $__g['id']; ?>" class="dropdown-item">
                    <?php if (!empty($garaHienTai['id']) && $garaHienTai['id'] == $__g['id']): ?>
                        <i class="fas fa-check text-success mr-1"></i>
                    <?php else: ?>
                        <i class="fas fa-fw mr-1"></i>
                    <?php endif; ?>
                    <?php echo e($__g['name']); ?>
                    <?php if ((int) $__g['is_master'] === 1): ?>
                        <span class="badge badge-info">tổng</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
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
                <a href="<?php echo _WEB_URL.'/dang-xuat'; ?>" class="dropdown-item">
                    <?php echo icon('log-out'); ?> Đăng xuất
                </a>
            </div>
        </div>
    </div>
</header>

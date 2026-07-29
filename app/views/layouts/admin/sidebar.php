<?php
/**
 * Menu dọc bên trái.
 *
 * - $listModules : do AppServiceProvider share ra (mọi module có trong bảng modules)
 * - route('admin/<link>') : trả truthy nếu user có quyền view -> dùng để lọc link
 * - Highlight: so link với URL hiện tại ($_GET['module'])
 *
 * Icon dùng Lucide (sprite nhúng trong master_admin.php), gọi qua icon().
 */

$currentUrl = isset($_GET['module']) ? trim($_GET['module'], '/') : '';

$isActive = function ($link) use ($currentUrl) {
    return $currentUrl === 'admin/' . $link
        || strpos($currentUrl, 'admin/' . $link . '/') === 0;
};

// Nhóm menu (thứ tự hiển thị) => các link thuộc nhóm
$menuGroups = [
    'Danh mục xe'        => ['car-brands', 'car-models', 'car-years', 'car-body-types', 'car-fuels', 'car-colors'],
    'Danh mục hàng hoá'  => ['part-categories', 'attributes', 'product-brands', 'product-origins', 'product-manufacturers', 'product-units'],
    'Nội dung'           => ['products', 'news', 'news-categories', 'du-an', 'galleries', 'banners', 'menus'],
    'Bán hàng'           => ['orders', 'quotations', 'sales-invoices', 'partners', 'bao-cao-ban-hang'],
    'Kho'                => ['goods-receipts', 'goods-issues', 'transfers', 'stock-takes', 'ton-kho', 'ton-kho-lau', 'bien-dong-ton', 'the-kho', 'warehouses', 'warehouse-locations'],
    'CSKH'               => ['customers', 'chat', 'contact-messages', 'newsletter', 'warranty', 'lich-bao-hanh', 'nhac-bao-tri', 'customer-groups', 'reviews', 'bao-cao-cskh'],
    'Hệ thống'           => ['users', 'groups', 'settings', 'thong-ke'],
];

$groupIcons = [
    'Danh mục xe'        => 'car',
    'Danh mục hàng hoá'  => 'cog',
    'Nội dung'           => 'folder-open',
    'Bán hàng'           => 'shopping-cart',
    'Kho'                => 'warehouse',
    'CSKH'               => 'headset',
    'Hệ thống'           => 'sliders-horizontal',
];

// Mục con trong nhóm không có icon (giống mẫu), nên không cần bảng icon riêng.

// Chỉ giữ module user có quyền, index theo link
$allowed = [];
if (!empty($listModules)) {
    foreach ($listModules as $m) {
        if (route('admin/' . $m['link'])) {
            $allowed[$m['link']] = $m;
        }
    }
}

// KHÔNG có nhóm "Khác". Module nào không nằm trong $menuGroups thì không lên menu.
// Hiện đó là 9 module kế toán (accounts, vouchers, journal, so-cai, ...) — đã bỏ
// khỏi giao diện từ commit 12b18ac. Bảng `modules` và quyền vẫn giữ nguyên, nên
// muốn dùng lại chỉ cần thêm link vào $menuGroups ở trên.
?>
<aside class="adm-sidebar">

    <a href="<?php echo _WEB_URL.'/admin'; ?>" class="adm-brand">
        <span class="adm-brand__tile"><?php echo icon('house'); ?></span>
        <div class="adm-brand__name">Tân Phát</div>
        <div class="adm-brand__role"><?php echo e(!empty($infoUser['name']) ? $infoUser['name'] : 'Quản trị viên'); ?></div>
    </a>

    <nav class="adm-nav">
        <ul>

            <li>
                <a href="<?php echo _WEB_URL.'/admin'; ?>"
                   class="adm-nav__link <?php echo ($currentUrl === 'admin' || $currentUrl === '') ? 'is-active' : ''; ?>">
                    <?php echo icon('gauge'); ?>
                    <span class="adm-nav__text">Tổng quan</span>
                </a>
            </li>

            <?php foreach ($menuGroups as $groupName => $links): ?>
                <?php
                // các link trong nhóm mà user có quyền
                $visible = [];
                foreach ($links as $l) { if (isset($allowed[$l])) { $visible[] = $l; } }
                if (empty($visible)) { continue; }

                // nhóm mở nếu có 1 link đang active
                $groupActive = false;
                foreach ($visible as $l) { if ($isActive($l)) { $groupActive = true; break; } }
                ?>
                <li class="adm-group <?php echo $groupActive ? 'is-open' : ''; ?>">
                    <a href="#" class="adm-nav__link <?php echo $groupActive ? 'is-active' : ''; ?>">
                        <?php echo icon($groupIcons[$groupName]); ?>
                        <span class="adm-nav__text"><?php echo e($groupName); ?></span>
                        <?php echo icon('chevron-down', 'adm-nav__caret'); ?>
                    </a>
                    <ul class="adm-sub">
                        <?php foreach ($visible as $l): $m = $allowed[$l]; ?>
                            <li>
                                <a href="<?php echo e(_WEB_URL.'/admin/'.$l); ?>"
                                   class="adm-nav__link <?php echo $isActive($l) ? 'is-active' : ''; ?>">
                                    <span class="adm-nav__text"><?php echo e($m['name']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>

        </ul>
    </nav>

    <div class="adm-sidebar__foot">
        <button type="button" class="adm-collapse-btn" aria-label="Thu gọn menu">
            <?php echo icon('chevron-left'); ?>
        </button>
    </div>
</aside>

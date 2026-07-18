<?php
/**
 * Sidebar menu (AdminLTE).
 *
 * - $listModules : do AppServiceProvider share ra (mọi module có trong bảng modules)
 * - route('admin/<link>') : trả truthy nếu user có quyền view -> dùng để lọc link
 * - Highlight: so link với URL hiện tại ($_GET['module'])
 */

$currentUrl = isset($_GET['module']) ? trim($_GET['module'], '/') : '';

$isActive = function ($link) use ($currentUrl) {
    return $currentUrl === 'admin/' . $link
        || strpos($currentUrl, 'admin/' . $link . '/') === 0;
};

// Nhóm menu (thứ tự hiển thị) => các link thuộc nhóm
$menuGroups = [
    'Danh mục xe'        => ['car-brands', 'car-models', 'car-years', 'car-body-types', 'car-fuels', 'car-colors'],
    'Danh mục phụ tùng'  => ['part-categories', 'attributes', 'product-brands', 'product-origins', 'product-manufacturers', 'product-units'],
    'Nội dung'           => ['products', 'news'],
    'Kế toán'            => ['vouchers', 'journal', 'cash-book', 'debt', 'nhat-ky-chung', 'so-cai', 'partners', 'accounts', 'cost-items', 'projects'],
    'Hệ thống'           => ['users', 'groups'],
];

$groupIcons = [
    'Danh mục xe'        => 'fa-car',
    'Danh mục phụ tùng'  => 'fa-cogs',
    'Nội dung'           => 'fa-folder-open',
    'Kế toán'            => 'fa-calculator',
    'Hệ thống'           => 'fa-sliders-h',
];

$itemIcons = [
    'car-brands'            => 'fa-trademark',
    'car-models'            => 'fa-car-side',
    'car-years'             => 'fa-calendar-alt',
    'part-categories'       => 'fa-sitemap',
    'attributes'            => 'fa-ruler-horizontal',
    'car-body-types'        => 'fa-truck-pickup',
    'car-fuels'             => 'fa-gas-pump',
    'car-colors'            => 'fa-palette',
    'product-brands'        => 'fa-tags',
    'product-origins'       => 'fa-globe',
    'product-manufacturers' => 'fa-industry',
    'product-units'         => 'fa-ruler-combined',
    'products'              => 'fa-box',
    'news'                  => 'fa-newspaper',
    'users'                 => 'fa-users',
    'groups'               => 'fa-user-shield',
    'vouchers'              => 'fa-receipt',
    'journal'               => 'fa-file-invoice',
    'debt'                  => 'fa-hand-holding-usd',
    'nhat-ky-chung'         => 'fa-book-open',
    'so-cai'                => 'fa-book',
    'partners'              => 'fa-address-book',
    'cash-book'             => 'fa-book',
    'accounts'              => 'fa-landmark',
    'cost-items'            => 'fa-coins',
    'projects'              => 'fa-briefcase',
];

// Chỉ giữ module user có quyền, index theo link
$allowed = [];
if (!empty($listModules)) {
    foreach ($listModules as $m) {
        if (route('admin/' . $m['link'])) {
            $allowed[$m['link']] = $m;
        }
    }
}

// Gom module chưa thuộc nhóm nào vào "Khác"
$grouped = [];
foreach ($menuGroups as $links) { foreach ($links as $l) { $grouped[$l] = true; } }
$others = [];
foreach ($allowed as $link => $m) { if (empty($grouped[$link])) { $others[] = $link; } }
if (!empty($others)) { $menuGroups['Khác'] = $others; $groupIcons['Khác'] = 'fa-ellipsis-h'; }
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="<?php echo _WEB_URL.'/admin'; ?>" class="brand-link text-center">
        <span class="brand-text"><b>TÂN</b> PHÁT</span>
    </a>

    <div class="sidebar">

        <div class="user-panel mt-3 pb-3 mb-2 d-flex align-items-center">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-white-50"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block"><?php echo e(!empty($infoUser['name']) ? $infoUser['name'] : 'Tài khoản'); ?></a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <li class="nav-item">
                    <a href="<?php echo _WEB_URL.'/admin'; ?>" class="nav-link <?php echo e($currentUrl === 'admin' || $currentUrl === '' ? 'active' : ''); ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Bảng điều khiển</p>
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
                    <li class="nav-item has-treeview <?php echo e($groupActive ? 'menu-open' : ''); ?>">
                        <a href="#" class="nav-link <?php echo e($groupActive ? 'active' : ''); ?>">
                            <i class="nav-icon fas <?php echo e($groupIcons[$groupName]); ?>"></i>
                            <p>
                                <?php echo e($groupName); ?>
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($visible as $l): $m = $allowed[$l]; $icon = isset($itemIcons[$l]) ? $itemIcons[$l] : 'fa-circle'; ?>
                                <li class="nav-item">
                                    <a href="<?php echo e(_WEB_URL.'/admin/'.$l); ?>" class="nav-link <?php echo e($isActive($l) ? 'active' : ''); ?>">
                                        <i class="nav-icon fas <?php echo e($icon); ?>"></i>
                                        <p><?php echo e($m['name']); ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>

            </ul>
        </nav>
    </div>
</aside>

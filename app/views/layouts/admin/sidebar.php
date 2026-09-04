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

/**
 * Bố cục menu — xếp theo TẦN SUẤT DÙNG THẬT của một gara.
 *
 * Gara vận hành thủ công là chính, web chỉ là kênh bán thêm. Nên thứ tự là:
 * việc làm hằng ngày ở quầy trước (bán hàng, kho), rồi tới dữ liệu nền
 * (hàng hoá, danh mục xe), rồi khách hàng. Ba nhóm nội dung web bị đẩy hẳn
 * xuống một khu riêng vì cả tuần mới đụng tới một lần.
 *
 * `products` ("Quản lý hàng hoá") trước đây nằm chung nhóm với tin tức/banner.
 * Đó là danh mục sản phẩm lõi, không phải nội dung website — nay về nhóm
 * "Hàng hoá", đứng cạnh chính các danh mục phụ trợ của nó.
 *
 * `services` ("Dịch vụ") nằm NGAY DƯỚI `products` trong cùng nhóm chứ không
 * tách thành nhóm riêng: dịch vụ vẫn là dòng trong bảng `parts`, và một nhóm
 * chỉ chứa đúng một mục thì bấm hai lần mới tới nơi.
 */
$menuGroups = [
    // --- Khu 1: việc hằng ngày ở quầy ---
    'Bán hàng'           => ['quotations', 'sales-invoices', 'orders', 'partners', 'bao-cao-ban-hang'],
    'Kho'                => ['goods-receipts', 'goods-issues', 'transfers', 'stock-takes', 'ton-kho', 'ton-kho-lau', 'bien-dong-ton', 'the-kho', 'warehouses', 'warehouse-locations'],
    // `garage-catalog` đứng ngay sau `services`: nó là danh mục hàng hoá nhìn
    // từ phía một gara, nên thuộc nhóm Hàng hoá chứ không phải nhóm Hệ thống.
    'Hàng hoá'           => ['products', 'services', 'garage-catalog', 'part-categories', 'attributes', 'product-brands', 'product-origins', 'product-manufacturers', 'product-units'],
    'Danh mục xe'        => ['car-brands', 'car-models', 'car-years', 'car-body-types', 'car-fuels', 'car-colors'],
    'CSKH'               => ['customers', 'customer-groups', 'warranty', 'lich-bao-hanh', 'nhac-bao-tri', 'chat', 'contact-messages', 'reviews', 'newsletter', 'bao-cao-cskh'],

    // --- Khu 2: trang bán hàng trên web ---
    'Quản lý website'    => ['news', 'news-categories', 'du-an', 'galleries', 'banners', 'menus'],

    // --- Khu 3: quản trị ---
    // `modules` đứng NGAY SAU `groups`: hai màn hình này đi liền một mạch —
    // đăng ký màn hình ở Quản lý module rồi mới cấp quyền cho nhóm ở Nhóm >
    // Phân quyền. Tách xa nhau thì người dùng không thấy được mạch đó.
    // `garages` đứng TRƯỚC `users`: gara là đơn vị, nhân viên được gán vào gara
    // ở màn Người dùng — nên phải khai gara xong mới có gì mà chọn.
    'Hệ thống'           => ['garages', 'users', 'groups', 'modules', 'settings', 'thong-ke'],
];

$groupIcons = [
    'Bán hàng'           => 'shopping-cart',
    'Kho'                => 'warehouse',
    'Hàng hoá'           => 'cog',
    'Danh mục xe'        => 'car',
    'CSKH'               => 'headset',
    'Quản lý website'    => 'folder-open',
    'Hệ thống'           => 'sliders-horizontal',
];

// Nhóm nào MỞ ĐẦU một khu vực thì vẽ vạch ngăn phía trên. Để dạng danh sách
// chứ không viết cứng một tên, sau này tách thêm khu chỉ cần thêm vào đây.
$groupSeparators = ['Quản lý website', 'Hệ thống'];

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
                   class="adm-nav__link <?php echo ($currentUrl === 'admin' || $currentUrl === '') ? 'is-active' : ''; ?>"
                   title="Tổng quan">
                    <?php echo icon('gauge'); ?>
                    <span class="adm-nav__text">Tổng quan</span>
                </a>
            </li>

            <?php $drawn = 0; ?>
            <?php foreach ($menuGroups as $groupName => $links): ?>
                <?php
                // các link trong nhóm mà user có quyền
                $visible = [];
                foreach ($links as $l) { if (isset($allowed[$l])) { $visible[] = $l; } }
                if (empty($visible)) { continue; }

                // nhóm mở nếu có 1 link đang active
                $groupActive = false;
                foreach ($visible as $l) { if ($isActive($l)) { $groupActive = true; break; } }

                // Vạch ngăn khu vực. Chỉ vẽ khi ĐÃ có nhóm nào hiện phía trên —
                // user quyền hẹp có thể không thấy nhóm nào ở khu trước, lúc đó
                // vạch sẽ nằm chỏng chơ trên đỉnh menu.
                $sep = ($drawn > 0 && in_array($groupName, $groupSeparators, true));
                $drawn++;
                ?>
                <li class="adm-group <?php echo $groupActive ? 'is-open' : ''; ?> <?php echo $sep ? 'adm-group--sep' : ''; ?>">
                    <?php /* title: sidebar hẹp nên nhãn dài bị cắt còn "Quản lý hàng ...",
                             và ở chế độ thu gọn thì nhãn bị ẩn hẳn. Dùng title chứ không
                             vẽ tooltip bằng CSS vì .adm-nav có overflow-x:hidden — tooltip
                             tràn ra ngoài sẽ bị cắt cụt. */ ?>
                    <a href="#" class="adm-nav__link <?php echo $groupActive ? 'is-active' : ''; ?>"
                       title="<?php echo e($groupName); ?>">
                        <?php echo icon($groupIcons[$groupName]); ?>
                        <span class="adm-nav__text"><?php echo e($groupName); ?></span>
                        <?php echo icon('chevron-down', 'adm-nav__caret'); ?>
                    </a>
                    <ul class="adm-sub">
                        <?php foreach ($visible as $l): $m = $allowed[$l]; ?>
                            <li>
                                <a href="<?php echo e(_WEB_URL.'/admin/'.$l); ?>"
                                   class="adm-nav__link <?php echo $isActive($l) ? 'is-active' : ''; ?>"
                                   title="<?php echo e($m['name']); ?>">
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

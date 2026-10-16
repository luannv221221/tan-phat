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
    /* `partners` (Đối tượng khách/NCC) ĐÃ ẩn khỏi menu 02/10/2026 rồi KHÔI PHỤC
       ngay trong ngày. Đây là nơi KHAI khách và nhà cung cấp — thêm mới, sửa,
       xoá, đặt mã và MST. Màn CSKH › Khách hàng là nơi CHĂM SÓC: xem lại danh
       sách đó, xếp nhóm và liên hệ (Zalo, gọi, nhắn tin, email).

       Hai màn cùng sửa MỘT dòng `partners`, nhưng phân vai rõ chứ không trùng
       nhau: khai ở đây, chăm sóc ở kia. */
    /* `insurers` (danh muc bao hiem) dung trong nhom Ban hang, canh `partners`:
       no la MOT DANH MUC DOI TAC nua, va cho duy nhat dung toi no la luc lap
       bao gia. Dat thanh nhom rieng cho mot muc thi bam hai lan moi toi noi. */
    'Bán hàng'           => ['quotations', 'sales-invoices', 'orders', 'partners', 'insurers', 'bao-cao-ban-hang'],
    // Kế toán đứng NGAY SAU Bán hàng: phiếu thu sinh ra từ hoá đơn bán,
    // phiếu chi từ phiếu nhập — tiền vào tiền ra là bước kế tiếp của việc
    // bán hàng, chưa phải một khu quản trị riêng.
    /* Hai danh mục nền (`acc-accounts`, `acc-voucher-types`) đứng SAU hai màn
       phiếu, dù phải khai chúng trước mới lập được phiếu: khai một lần rồi
       gần như không đụng nữa, còn phiếu thu / chi thì ngày nào cũng vào. Menu
       này xếp theo tần suất dùng thật, không theo thứ tự cài đặt. */
    'Kế toán'            => ['phieu-thu', 'phieu-chi', 'bao-cao-ke-toan', 'acc-voucher-types', 'acc-accounts'],
    /* `dat-hang-kho-tong` dung TRUOC `goods-receipts`: dat hang xong moi co
       phieu nhap de ghi so, di dung mot mach. */
    /* `ton-kho-dau-ky` xếp CUỐI nhóm Kho, cạnh Danh mục kho: nó là việc làm
       đúng MỘT LẦN lúc mở sổ, không phải việc hằng ngày — mà menu này xếp theo
       tần suất dùng thật. Đặt lên đầu thì ngày nào cũng phải đọc lướt qua một
       mục không bao giờ bấm nữa. Đúng thứ tự khách vẽ trong cây Kho hàng. */
    'Kho'                => ['dat-hang-kho-tong', 'goods-receipts', 'goods-issues', 'transfers', 'stock-takes', 'ton-kho', 'ton-kho-lau', 'bien-dong-ton', 'the-kho', 'ton-kho-dau-ky', 'warehouses', 'warehouse-locations'],
    // `garage-catalog` đứng ngay sau `services`: nó là danh mục hàng hoá nhìn
    // từ phía một gara, nên thuộc nhóm Hàng hoá chứ không phải nhóm Hệ thống.
    'Hàng hoá'           => ['products', 'services', 'garage-catalog', 'part-categories', 'attributes', 'product-brands', 'product-origins', 'product-manufacturers', 'product-units'],
    'Danh mục xe'        => ['car-brands', 'car-models', 'car-years', 'car-body-types', 'car-fuels', 'car-colors'],
    'CSKH'               => ['customers', 'customer-groups', 'vehicles', 'receptions', 'warranty', 'lich-bao-hanh', 'nhac-bao-tri', 'chat', 'contact-messages', 'reviews', 'newsletter', 'tai-khoan-web', 'bao-cao-cskh'],

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

/**
 * THỬ NGHIỆM 16/10/2026 — MENU GỌN CHO KHO TỔNG (_KHO_TONG_MENU_GON).
 *
 * Kho tổng không bán lẻ, không thu chi với khách, không chăm sóc khách và
 * không chạy trang web bán hàng — bốn nhóm đó là việc của GARA. Để nguyên
 * trên menu của Tân Phát thì mỗi lần vào phải đọc lướt qua bốn nhóm không
 * bao giờ bấm, và người mới nhìn vào không biết đâu mới là việc của mình.
 *
 * Đổi `_KHO_TONG_MENU_GON` về false ở config.php là trở lại menu cũ.
 *
 * CHỈ ẨN TRÊN MENU. Đường dẫn vẫn vào được — xem ghi chú ở config.php.
 */
$anNhom  = [];
$menuDon = [];
if (defined('_KHO_TONG_MENU_GON') && _KHO_TONG_MENU_GON && !empty($garaHienTai['is_master'])){
    $anNhom = ['Bán hàng', 'Kế toán', 'CSKH', 'Quản lý website'];

    /* "Quản lý các gara" ra MỘT MỤC RIÊNG ngoài menu, không nằm trong Hệ thống.
       Với kho tổng thì đây là màn hình chính của họ — danh sách gara đang dùng
       phần mềm — chứ không phải một mục cài đặt bấm vài tháng một lần.

       Rút khỏi nhóm Hệ thống luôn: để cả hai chỗ thì cùng một màn hiện hai lần
       trên một menu, người dùng tưởng là hai thứ khác nhau. */
    $menuDon['garages'] = 'factory';
    $menuGroups['Hệ thống'] = array_values(array_diff($menuGroups['Hệ thống'], ['garages']));
}
foreach ($anNhom as $g) unset($menuGroups[$g]);

$groupIcons = [
    'Bán hàng'           => 'shopping-cart',
    'Kế toán'            => 'receipt',
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
// Chiều ngược lại cũng đúng: tên có trong $menuGroups mà bảng `modules` không có
// dòng tương ứng thì nút vẫn không mọc ra — phải khai module bằng migration.
//
// Phân hệ kế toán cũ (accounts, vouchers, journal, so-cai...) đã bị gỡ HẲN: mã
// nguồn từ commit 12b18ac, còn 9 bảng + 13 dòng `modules` + 40 dòng quyền thì từ
// migration 000048. Nhóm "Kế toán" ở trên là làm LẠI TỪ ĐẦU (migration 000082),
// hiện mới có chỗ đứng trên menu, chưa có nghiệp vụ.
?>
<aside class="adm-sidebar">

    <a href="<?php echo _WEB_URL.'/admin'; ?>" class="adm-brand">
        <span class="adm-brand__tile"><?php echo icon('house'); ?></span>
        <?php /* TÊN GARA ĐANG LÀM VIỆC, không viết cứng "Tân Phát": từ
                 07/10/2026 mỗi gara có trang quản trị riêng theo tên miền, và
                 chủ gara Sài Gòn mở admin của mình ra thấy chữ "Tân Phát" ở
                 góc trên thì không hiểu mình đang ở đâu.
                 $garaHienTai do AppServiceProvider::boot() chia sẻ. */ ?>
        <div class="adm-brand__name"><?php echo e(!empty($garaHienTai['name']) ? $garaHienTai['name'] : 'Tân Phát'); ?></div>
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

            <?php /* MỤC ĐƠN — màn hình đứng thẳng ngoài menu, không gói trong
                     nhóm nào. Hiện chỉ dùng cho "Quản lý các gara" ở bản menu
                     gọn của kho tổng; $menuDon rỗng thì khối này không vẽ gì. */ ?>
            <?php $veDon = 0; ?>
            <?php foreach ($menuDon as $link => $ic): if (!isset($allowed[$link])) continue; ?>
            <?php $veDon++; ?>
            <li>
                <a href="<?php echo e(_WEB_URL.'/admin/'.$link); ?>"
                   class="adm-nav__link <?php echo $isActive($link) ? 'is-active' : ''; ?>"
                   title="<?php echo e($allowed[$link]['name']); ?>">
                    <?php echo icon($ic); ?>
                    <span class="adm-nav__text"><?php echo e($allowed[$link]['name']); ?></span>
                </a>
            </li>
            <?php endforeach; ?>

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
                /* Mục đơn vẽ ở trên thì nhóm ĐẦU TIÊN phải có vạch ngăn, nếu không
                   "Quản lý các gara" dính liền vào nhóm Kho như thể là mục con. */
                $sep = ($drawn > 0 && in_array($groupName, $groupSeparators, true))
                    || ($drawn === 0 && $veDon > 0);
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

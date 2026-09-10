<?php
/**
 * Test PHÂN QUYỀN CHO NHÓM Manager VÀ Staff.
 *
 * Chạy:  C:\xampp\php\php.exe tests\PhanQuyenNhomTest.php
 *
 * Trước migration 000068, hai nhóm này gần như rỗng: Manager 3 dòng quyền,
 * Staff 0. Cấp tài khoản Staff cho thợ xong họ đăng nhập vào không thấy gì.
 *
 * BỐN CHỖ HỎNG SẼ ÂM THẦM:
 *
 *   1. LEO THANG ĐẶC QUYỀN. Nhóm Manager có sẵn view/add/edit trên module
 *      `groups` từ bản dump gốc — mở được màn Phân quyền và TỰ CẤP CHO MÌNH
 *      mọi quyền. Ai sửa được bảng phân quyền thì mọi phân quyền khác chỉ còn
 *      là trang trí. Đây là khẳng định quan trọng nhất của file này.
 *
 *   2. Thiếu `view` thì ba role kia vô nghĩa — RoleMiddleware chặn ngay ở
 *      cửa, không vào được màn hình thì add/edit/delete không bao giờ chạy tới.
 *
 *   3. Tên module viết sai trong bảng cấp quyền -> màn hình đó không bao giờ
 *      được cấp, mà migration vẫn báo OK.
 *
 *   4. Cấp nhầm làm hụt quyền Admin. Admin phải nguyên vẹn.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg = glob($goc . 'database/migrations/*_cap_quyen_manager_va_staff.php');
ok(!empty($mg), 'Co migration cap quyen Manager/Staff');
$src = !empty($mg) ? file_get_contents($mg[0]) : '';

ok(strpos($src, "'Staff'") !== false && strpos($src, "'Manager'") !== false,
   'Migration cap cho ca hai nhom');
ok(strpos($src, 'phaiGo') !== false,
   'Migration co phan GO quyen nguy hiem',
   'Chi cap them ma khong go thi Manager van tu nang quyen duoc');
ok(strpos($src, "'Admin'") === false,
   'Migration KHONG dung vao nhom Admin',
   'Admin la nhom duy nhat con lai neu hai nhom kia bi cau hinh hong');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

/** [role => true] cua mot nhom tren mot module */
$quyen = function($nhom, $link) use ($pdo){
    $st = $pdo->prepare("SELECT p.role FROM permissions p
        JOIN `groups` g ON g.id = p.group_id
        JOIN modules m ON m.id = p.module_id
        WHERE g.name = ? AND m.link = ?");
    $st->execute([$nhom, $link]);
    $r = [];
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $x) $r[$x] = true;
    return $r;
};

$coNhom = function($ten) use ($pdo){
    $st = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE name = ?");
    $st->execute([$ten]);
    return (int) $st->fetchColumn() > 0;
};

foreach (['Admin', 'Manager', 'Staff'] as $n){
    ok($coNhom($n), "Co nhom $n");
}
if (!$coNhom('Manager') || !$coNhom('Staff')){
    echo "\n[SKIP] Thieu nhom de kiem.\n"; exit(summary());
}

/* --- 1. LEO THANG ĐẶC QUYỀN — khẳng định quan trọng nhất --- */
foreach (['Manager', 'Staff'] as $n){
    ok(empty($quyen($n, 'groups')),
       "$n KHONG co quyen nao tren man hinh Nhom (chong tu nang quyen)",
       'Dang co: ' . implode(',', array_keys($quyen($n, 'groups')))
       . ' — sua duoc bang phan quyen thi moi phan quyen khac la trang tri');
    ok(empty($quyen($n, 'users')),
       "$n KHONG quan ly duoc nguoi dung",
       'Tao duoc tai khoan Admin moi la vong qua duoc moi han che');
    ok(empty($quyen($n, 'modules')),
       "$n KHONG vao duoc Quan ly module");
    ok(empty($quyen($n, 'settings')),
       "$n KHONG sua duoc cau hinh website");
}

/* --- 2. Staff: lam duoc viec hang ngay --- */
foreach (['quotations' => 'Bao gia', 'sales-invoices' => 'Hoa don ban',
          'warranty' => 'Phieu bao hanh', 'customers' => 'Khach hang'] as $link => $ten){
    $q = $quyen('Staff', $link);
    ok(isset($q['view']) && isset($q['add']) && isset($q['edit']),
       "Staff lam duoc $ten (xem/them/sua)",
       'Dang co: ' . implode(',', array_keys($q)));
}
foreach (['products' => 'Hang hoa', 'ton-kho' => 'Ton kho'] as $link => $ten){
    $q = $quyen('Staff', $link);
    ok(isset($q['view']), "Staff XEM duoc $ten");
    ok(!isset($q['edit']) && !isset($q['add']),
       "Staff KHONG sua duoc $ten",
       'Tho can biet con hang khong, khong can sua danh muc');
}

/* Staff khong duoc xoa o BAT CU dau: sai thi sua, khong xoa. Xoa mot hoa don
   da ghi so la mat dau vet ke toan. */
$st = $pdo->query("SELECT m.link FROM permissions p
    JOIN `groups` g ON g.id = p.group_id
    JOIN modules m ON m.id = p.module_id
    WHERE g.name = 'Staff' AND p.role = 'delete'")->fetchAll(PDO::FETCH_COLUMN);
ok(empty($st), 'Staff KHONG co quyen `delete` o bat cu man hinh nao',
   'Dang co tren: ' . implode(', ', $st));

/* --- 3. Manager: trong coi chi nhanh --- */
$q = $quyen('Manager', 'garages');
ok(isset($q['view']),
   'Manager XEM duoc module gara (dieu kien de hien O DOI GARA)',
   'Thieu view thi o doi gara khong bao gio hien — header hoi route(admin/garages)');
ok(!isset($q['add']) && !isset($q['delete']),
   'Manager KHONG them/xoa duoc gara',
   'Mo chi nhanh moi la viec cua Admin');

$q = $quyen('Manager', 'garage-catalog');
ok(isset($q['view']) && isset($q['edit']),
   'Manager dung duoc danh muc rieng cua chi nhanh minh');

foreach (['quotations', 'sales-invoices', 'warranty'] as $link){
    ok(isset($quyen('Manager', $link)['delete']),
       "Manager xoa duoc `$link` (khac Staff)");
}
ok(isset($quyen('Manager', 'goods-receipts')['add']), 'Manager lam duoc phieu nhap kho');
ok(isset($quyen('Manager', 'bao-cao-ban-hang')['view']), 'Manager xem duoc bao cao ban hang');

/* --- 4. Co `view` thi cac role kia moi co nghia --- */
$thieuView = $pdo->query("SELECT CONCAT(g.name, ' / ', m.link) AS x
    FROM permissions p
    JOIN `groups` g ON g.id = p.group_id
    JOIN modules m ON m.id = p.module_id
    WHERE g.name IN ('Manager','Staff')
    GROUP BY g.id, m.id
    HAVING SUM(p.role = 'view') = 0")->fetchAll(PDO::FETCH_COLUMN);
ok(empty($thieuView),
   'Moi man hinh duoc cap deu co `view`',
   'Thieu view thi RoleMiddleware chan ngay o cua: ' . implode(', ', $thieuView));

/* --- 5. Khong co dong quyen nao tro vao module da bien mat --- */
$moCoi = (int) $pdo->query("SELECT COUNT(*) FROM permissions p
    LEFT JOIN modules m ON m.id = p.module_id WHERE m.id IS NULL")->fetchColumn();
ok($moCoi === 0, 'Khong co dong quyen mo coi (tro vao module khong ton tai)',
   "Dang co $moCoi dong");

/* --- 6. Admin phai nguyen ven --- */
$soAdmin = (int) $pdo->query("SELECT COUNT(*) FROM permissions p
    JOIN `groups` g ON g.id = p.group_id WHERE g.name = 'Admin'")->fetchColumn();
ok($soAdmin > 150, 'Admin van giu du quyen (' . $soAdmin . ' dong)',
   'Cap quyen cho nhom khac ma lam hut quyen Admin la khoa cua chinh minh');

foreach (['users', 'groups', 'modules', 'settings', 'garages'] as $link){
    ok(isset($quyen('Admin', $link)['view']), "Admin van vao duoc `$link`");
}

/* --- 7. Manager/Staff phai la TAP CON cua Admin --- */
$vuot = $pdo->query("SELECT DISTINCT CONCAT(g.name, ' / ', m.link, ' / ', p.role) AS x
    FROM permissions p
    JOIN `groups` g ON g.id = p.group_id
    JOIN modules m ON m.id = p.module_id
    WHERE g.name IN ('Manager','Staff')
      AND NOT EXISTS (
        SELECT 1 FROM (SELECT * FROM permissions) p2
        JOIN `groups` g2 ON g2.id = p2.group_id
        WHERE g2.name = 'Admin' AND p2.module_id = p.module_id AND p2.role = p.role)")
    ->fetchAll(PDO::FETCH_COLUMN);
ok(empty($vuot),
   'Manager/Staff khong co quyen nao ma Admin khong co',
   'Vuot mat Admin: ' . implode(', ', $vuot));

exit(summary());

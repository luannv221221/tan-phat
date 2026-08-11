<?php
/**
 * Test PHAN LOAI HANG HOA: phu tung / thiet bi / dich vu (chot 05/08/2026).
 *
 * Chay:  C:\xampp\php\php.exe tests\ItemTypeTest.php
 *
 * Trong tam: DICH VU khong co ton kho. Neu khong tach ra thi moi hoa don co
 * dong dich vu deu bi chan o buoc kiem ton — "thay dau" ton luon bang 0.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(0);
}

foreach (['LookupModel','ProductUnitsModel','PartsModel','StocksModel','WarehousesModel',
          'PartCategoriesModel'] as $m){
    require_once __DIR__ . '/../app/models/' . $m . '.php';
}

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

$pdo->exec("DELETE FROM parts WHERE code LIKE 'IT-TEST-%'");

// ================================================================
section('Cot item_type');

$cols = [];
foreach ($pdo->query('SHOW COLUMNS FROM parts') as $c) $cols[$c['Field']] = $c;
ok(isset($cols['item_type']), 'parts.item_type ton tai');
ok(strpos($cols['item_type']['Type'], "'part'") !== false
   && strpos($cols['item_type']['Type'], "'equipment'") !== false
   && strpos($cols['item_type']['Type'], "'service'") !== false,
   'ENUM du 3 gia tri', $cols['item_type']['Type'] ?? '');
ok($cols['item_type']['Default'] === 'part',
   'Mac dinh la "part" — hang cu tu dong la phu tung, khong phai va tay',
   var_export($cols['item_type']['Default'], true));

// ================================================================
section('Cay danh muc 3 nhanh');

$goc = $pdo->query("SELECT slug, name FROM part_categories WHERE parent_id IS NULL ORDER BY sort_order")
           ->fetchAll(PDO::FETCH_KEY_PAIR);
ok(count($goc) === 3, 'Dung 3 danh muc goc', implode(', ', array_keys($goc)));
ok(array_keys($goc) === ['phu-tung', 'thiet-bi', 'dich-vu'],
   'Dung thu tu Phu tung -> Thiet bi -> Dich vu', implode(' | ', array_keys($goc)));

// 4 nhom phu tung cu phai nam DUOI "Phu tung", khong con la goc
$idPhuTung = (int) $pdo->query("SELECT id FROM part_categories WHERE slug='phu-tung'")->fetchColumn();
foreach (['he-thong-phanh','dong-co','he-thong-dien','he-thong-treo'] as $slug){
    $pid = $pdo->query("SELECT parent_id FROM part_categories WHERE slug='$slug'")->fetchColumn();
    ok((int) $pid === $idPhuTung, "$slug da tut xuong lam con cua Phu tung");
}

$soDv = (int) $pdo->query("SELECT COUNT(*) FROM part_categories
                           WHERE parent_id = (SELECT id FROM part_categories WHERE slug='dich-vu')")->fetchColumn();
ok($soDv >= 4, 'Da gieo danh muc dich vu', $soDv . ' muc');

// ================================================================
section('coKho() — ai di qua kho, ai khong');

ok(PartsModel::coKho(PartsModel::LOAI_PHU_TUNG) === true,  'Phu tung: CO kho');
ok(PartsModel::coKho(PartsModel::LOAI_THIET_BI) === true,  'Thiet bi: CO kho (khach chot)');
ok(PartsModel::coKho(PartsModel::LOAI_DICH_VU)  === false, 'Dich vu: KHONG kho');

// ================================================================
section('loaiTheoId() tra ve dung loai');

$unit = (new ProductUnitsModel())->findBySlug('cai');
$pm   = new PartsModel();
$idPT = $pm->add(['code' => 'IT-TEST-PT', 'name' => 'IT Ma phanh', 'slug' => 'it-test-pt',
                  'item_type' => 'part', 'unit_id' => $unit['id'], 'price' => 500000, 'status' => 1]);
$idTB = $pm->add(['code' => 'IT-TEST-TB', 'name' => 'IT Cau nang', 'slug' => 'it-test-tb',
                  'item_type' => 'equipment', 'unit_id' => $unit['id'], 'price' => 90000000, 'status' => 1]);
$idDV = $pm->add(['code' => 'IT-TEST-DV', 'name' => 'IT Thay dau', 'slug' => 'it-test-dv',
                  'item_type' => 'service', 'unit_id' => $unit['id'], 'price' => 200000, 'status' => 1]);

$map = $pm->loaiTheoId([$idPT, $idTB, $idDV]);
ok(($map[$idPT] ?? '') === 'part',      'Tra dung loai phu tung');
ok(($map[$idTB] ?? '') === 'equipment', 'Tra dung loai thiet bi');
ok(($map[$idDV] ?? '') === 'service',   'Tra dung loai dich vu');
ok($pm->loaiTheoId([]) === [], 'Mang rong -> khong query, tra rong');

// ================================================================
section('getForSelect(true) LOAI dich vu khoi man hinh kho');

$tatCa = array_column($pm->getForSelect(false), 'id');
$coKho = array_column($pm->getForSelect(true),  'id');

ok(in_array($idDV, $tatCa), 'Man hinh ban hang: dich vu CO trong danh sach (gara co ban dich vu)');
ok(!in_array($idDV, $coKho), 'Man hinh kho: dich vu BI LOAI (nhap "lan thay dau" vao kho la vo nghia)');
ok(in_array($idTB, $coKho), 'Man hinh kho: thiet bi VAN CON (van theo doi ton)');
ok(in_array($idPT, $coKho), 'Man hinh kho: phu tung van con');

// ================================================================
section('Dich vu co ton = 0 — day chinh la ly do phai tach');

$st = new StocksModel();
$wh = (int) (new WarehousesModel())->getDefault()['id'];
ok($st->available($wh, $idDV) == 0.0, 'Dich vu ton = 0 (khong bao gio nhap kho)');
ok($st->sellableByPart($idDV) == 0.0, 'Ton kha dung cung = 0');

// Neu KHONG tach loai thi buoc kiem ton se chan -> chung minh bang chinh phep so
$canBan = 2;
$biChanNeuKhongTach = ($st->available($wh, $idDV) + 1e-9 < $canBan);
ok($biChanNeuKhongTach, 'Khong tach loai thi hoa don dich vu BI CHAN — dung nhu du doan');

// ================================================================
section('Cac luong ban hang deu biet bo qua dich vu');

$inv = codeOnly(__DIR__ . '/../app/controllers/admin/Salesinvoices.php');
ok(strpos($inv, 'loaiTheoId') !== false, 'Hoa don ban: co tra loai truoc khi kiem ton');
ok(substr_count($inv, 'PartsModel::coKho') >= 3,
   'Hoa don ban: bo qua dich vu o CA kiem ton, ghi so, va huy ghi so',
   substr_count($inv, 'PartsModel::coKho') . ' cho');

$cart = codeOnly(__DIR__ . '/../app/controllers/Cart.php');
ok(strpos($cart, 'PartsModel::coKho') !== false, 'Dat hang web: bo qua dich vu khi kiem ton');

$ord = codeOnly(__DIR__ . '/../app/controllers/admin/Orders.php');
ok(substr_count($ord, 'PartsModel::coKho') >= 2,
   'Don hang: bo qua dich vu ca luc tru kho lan luc hoan hang',
   substr_count($ord, 'PartsModel::coKho') . ' cho');

// Don TOAN dich vu van phai hoan thanh duoc
ok(strpos($ord, 'toan dịch vụ') !== false || strpos($ord, 'toàn dịch vụ') !== false,
   'Don chi co dich vu van hoan thanh duoc (khong bao "khong co dong hang")');

$prod = codeOnly(__DIR__ . '/../app/controllers/admin/Products.php');
ok(strpos($prod, "'item_type'") !== false, 'Form hang hoa co luu item_type');
ok(strpos($prod, 'LOAI_PHU_TUNG') !== false,
   'Gia tri la -> ve "part" (doan nham thanh dich vu la hang that thoat kiem ton)');

foreach (['add', 'edit'] as $v){
    $src = file_get_contents(__DIR__ . '/../app/views/admin/products/' . $v . '.php');
    ok(strpos($src, 'name="item_type"') !== false, "View products/$v co o chon loai");
}

// ================================================================
section('Admin cung hien thi 3 nhom');

$prodCtrl = codeOnly(__DIR__ . '/../app/controllers/admin/Products.php');
ok(strpos($prodCtrl, "'parts.item_type'") !== false, 'Danh sach hang hoa loc duoc theo nhom');
ok(strpos($prodCtrl, 'demTheoLoai') !== false, 'Co dem so luong tung nhom cho tab');

// So tren tab phai dem theo DUNG bo loc dang ap (tu khoa, danh muc...), tru
// chinh dieu kien nhom — khong thi bam vao tab ra so dong khac voi so tren tab.
ok(preg_match('~demTheoLoai.*?countLists\(\$fl, \$keyword, \$promo~s', $prodCtrl) === 1,
   'Dem tung nhom van giu cac bo loc khac (so tren tab khop so dong)');

$listView = file_get_contents(__DIR__ . '/../app/views/admin/products/lists.php');
ok(strpos($listView, 'nav-tabs') !== false, 'View co dai tab 3 nhom');
ok(strpos($listView, '$tabUrl') !== false, 'Tab giu nguyen cac bo loc khac khi chuyen');
ok(strpos($listView, 'item_type') !== false, 'View co cot/nhan loai');

// Them 1 cot vao bang thi colspan cua dong "khong co du lieu" phai tang theo,
// khong thi dong do bi hut vao mot ben.
preg_match_all('~<th[\s>]~', $listView, $ths);
preg_match('~colspan="(\d+)"~', $listView, $cs);
ok(count($ths[0]) === (int) ($cs[1] ?? 0),
   'So cot <th> khop colspan cua dong rong',
   count($ths[0]) . ' cot vs colspan=' . ($cs[1] ?? '?'));

// ================================================================
section('Website: bo loc danh muc phai voi toi tang co hang');

/* Cay danh muc nay sau 3 tang (Phu tung > He thong phanh > Ma phanh) va HANG
   HOA GAN VAO LA. Neu view chi hien depth <= 1 thi khach khong loc xuong duoc
   toi nhom hang cu the — dung luc dung lai cay 05/08/2026 da dinh dung loi nay,
   ca 10/10 danh muc co hang deu bi an. */
$listSrc = file_get_contents(__DIR__ . '/../app/views/storefront/list.php');
ok(preg_match("~\\\$c\['depth'\]\s*<=\s*(\d+)~", $listSrc, $mm) === 1,
   'Doc duoc muc depth toi da ma view chiu hien');
$depthView = (int) ($mm[1] ?? 0);

// Tinh do sau that cua moi danh muc DANG CO HANG
$par = [];
foreach ($pdo->query('SELECT id, parent_id FROM part_categories') as $r){
    $par[(int) $r['id']] = $r['parent_id'] === null ? 0 : (int) $r['parent_id'];
}
$doSau = function($id) use (&$par){ $n = 0; $c = $par[$id] ?? 0; while ($c){ $n++; $c = $par[$c] ?? 0; } return $n; };

$sauNhat = -1; $soCoHang = 0;
foreach ($pdo->query('SELECT DISTINCT category_id FROM parts WHERE status=1 AND category_id IS NOT NULL') as $r){
    $soCoHang++;
    $sauNhat = max($sauNhat, $doSau((int) $r['category_id']));
}

if ($soCoHang === 0){
    echo "  [SKIP] Chua co hang nao gan danh muc\n";
} else {
    ok($sauNhat <= $depthView,
       'Moi danh muc co hang deu nam trong tam hien cua bo loc',
       "sau nhat = $sauNhat, view hien toi depth $depthView");
}

// Trang chu chi lay depth 0 -> dung bang so danh muc goc (3 nhom)
$homeSrc = file_get_contents(__DIR__ . '/../app/views/storefront/home.php');
ok(strpos($homeSrc, "=== 0") !== false,
   'Trang chu lay danh muc goc -> tu dung 3 nhom, khong phai sua tay');

// ================================================================
section('show_on_web tach roi khoi status');

$c2 = [];
foreach ($pdo->query('SHOW COLUMNS FROM parts') as $c) $c2[$c['Field']] = $c;
ok(isset($c2['show_on_web']), 'parts.show_on_web ton tai');
ok($c2['show_on_web']['Default'] === '1', 'Mac dinh 1 — hang moi tao la co len web');

// (int) ngay tu day: add() tra ve lastId() dang CHUOI, so === voi so nguyen
// ben duoi thi luon false — va cac assertion dang PHU DINH se pass gia.
$idWeb = (int) $pm->add(['code' => 'IT-TEST-WEB', 'name' => 'IT Hang len web', 'slug' => 'it-test-web',
                   'item_type' => 'part', 'unit_id' => $unit['id'], 'price' => 100000,
                   'status' => 1, 'show_on_web' => 1]);

$coTrenWeb = function($id) use ($pm){
    foreach ($pm->storefront([]) as $r){ if ((int) $r['id'] === $id) return true; }
    return false;
};
$coTrongOChon = function($id) use ($pm){
    foreach ($pm->getForSelect(false) as $r){ if ((int) $r['id'] === $id) return true; }
    return false;
};

ok($coTrenWeb($idWeb),    'Bat ca hai co -> hien tren web');
ok($coTrongOChon($idWeb), 'Bat ca hai co -> co trong o chon hoa don');

// TAT rieng co web: phai bien mat khoi web NHUNG con trong o chon.
// Day la ca quan trong nhat — truoc day chi co mot co `status` gac ca hai
// duong, tat de go khoi web la mat luon kha nang xuat hoa don.
$pdo->prepare('UPDATE parts SET show_on_web=0 WHERE id=?')->execute([$idWeb]);
ok(!$coTrenWeb($idWeb),   'Tat show_on_web -> BIEN MAT khoi web');
ok($coTrongOChon($idWeb), 'Tat show_on_web -> VAN xuat hoa don / nhap xuat kho duoc');
ok($pm->getBySlugFull('it-test-web') === false || empty($pm->getBySlugFull('it-test-web')),
   'Trang chi tiet cung khong vao duoc');

// TAT status: phai bien mat khoi CA HAI
$pdo->prepare('UPDATE parts SET show_on_web=1, status=0 WHERE id=?')->execute([$idWeb]);
ok(!$coTrenWeb($idWeb),    'Tat status -> mat khoi web');
ok(!$coTrongOChon($idWeb), 'Tat status -> mat khoi ca o chon (ngung kinh doanh han)');

$prodSrc = codeOnly(__DIR__ . '/../app/controllers/admin/Products.php');
ok(strpos($prodSrc, "'show_on_web'") !== false, 'Form hang hoa co luu show_on_web');

foreach (['add', 'edit'] as $v){
    $s = file_get_contents(__DIR__ . '/../app/views/admin/products/' . $v . '.php');
    ok(strpos($s, 'name="show_on_web"') !== false, "View products/$v co o Hien thi website");
    ok(strpos($s, 'Đang kinh doanh') !== false,
       "View products/$v doi nhan o cu thanh 'Dang kinh doanh' (khong con mo ho)");
}

$pdo->prepare('DELETE FROM parts WHERE id=?')->execute([$idWeb]);

// ================================================================
section('Thong so ky thuat DONG theo loai hang');

require_once __DIR__ . '/../app/models/AttributesModel.php';
$am = new AttributesModel();
$pdo->exec("DELETE FROM part_attributes WHERE slug LIKE 'it-attr-%'");

$nowA = date('Y-m-d H:i:s');
$themAttr = function($ten, $slug, $loai) use ($pdo, $nowA){
    $pdo->prepare("INSERT INTO part_attributes (name,slug,item_types,sort_order,status,create_at)
                   VALUES (?,?,?,0,1,?)")->execute([$ten, $slug, $loai, $nowA]);
    return (int) $pdo->lastInsertId();
};

$aDv = $themAttr('IT Thoi gian thuc hien', 'it-attr-tg', 'service');
$aTb = $themAttr('IT Tai trong',           'it-attr-tt', 'equipment');
$aAll= $themAttr('IT Ap ca ba',            'it-attr-all', 'part,equipment,service');

$coAttr = function($loai, $id) use ($am){
    foreach ($am->getForItemType($loai) as $a){ if ((int) $a['id'] === $id) return true; }
    return false;
};

ok($coAttr('service', $aDv),   'Thong so tick Dich vu -> hien o Dich vu');
ok(!$coAttr('part', $aDv),     'Thong so tick Dich vu -> KHONG lot sang Phu tung');
ok(!$coAttr('equipment', $aDv),'Thong so tick Dich vu -> KHONG lot sang Thiet bi');
ok($coAttr('equipment', $aTb) && !$coAttr('service', $aTb), 'Thong so tick Thiet bi chi o Thiet bi');
ok($coAttr('part', $aAll) && $coAttr('equipment', $aAll) && $coAttr('service', $aAll),
   'Thong so tick ca ba -> hien o ca ba');

// Thong so co truoc migration mac dinh 'part,equipment,service' -> khong duoc mat
$soCu  = (int) $pdo->query("SELECT COUNT(*) FROM part_attributes WHERE slug NOT LIKE 'it-attr-%' AND status=1")->fetchColumn();
$thayCu = 0;
foreach ($am->getForItemType('part') as $a){ if (strpos($a['slug'], 'it-attr-') !== 0) $thayCu++; }
ok($soCu === $thayCu, 'Thong so co san van hien du (migration khong lam mat cai nao)', "$thayCu/$soCu");

// Don vi tinh cho dich vu
$dv = $pdo->query("SELECT COUNT(*) FROM part_units WHERE slug IN ('lan','gio','goi')")->fetchColumn();
ok((int) $dv === 3, 'Da gieo don vi Lan / Gio / Goi cho dich vu', $dv);

// --- Chot phia SERVER: an bang JS khong ngan duoc ai gui thang du lieu len ---
$prodSrc2 = codeOnly(__DIR__ . '/../app/controllers/admin/Products.php');
ok(strpos($prodSrc2, 'boTruongKhongThuocLoai') !== false,
   'Server tu bo truong khong thuoc loai (khong tin vao viec JS da an)');
ok(preg_match('~boTruongKhongThuocLoai.*?oem_code.*?null~s', $prodSrc2) === 1,
   'Ma OEM bi xoa khi khong phai phu tung');
ok(preg_match('~function syncFitments.*?LOAI_PHU_TUNG.*?syncForPart\(\$partId, \[\]\)~s', $prodSrc2) === 1,
   'Doi loai khoi phu tung -> XOA lien ket doi xe cu (khong chi bo qua)');
ok(preg_match('~function syncAttrs.*?getForItemType~s', $prodSrc2) === 1,
   'Chi luu thong so co ap cho loai dang chon');

$attrCtrl = codeOnly(__DIR__ . '/../app/controllers/admin/Attributes.php');
ok(strpos($attrCtrl, 'loaiApDung') !== false, 'Man hinh thong so cho chon loai ap dung');
ok(strpos($attrCtrl, 'empty($chon) ? $hopLe') !== false,
   'Khong tick gi -> hieu la ca ba (khong tao thong so vo hinh)');

$js = file_get_contents(__DIR__ . '/../public/assets/js/admin.js');
ok(strpos($js, 'js-theo-loai') !== false, 'admin.js co bo an/hien theo loai');
foreach (['add', 'edit'] as $v){
    $s = file_get_contents(__DIR__ . '/../app/views/admin/products/' . $v . '.php');
    ok(substr_count($s, 'js-theo-loai') >= 3,
       "View products/$v da gan nhan theo loai cho OEM / thuong hieu / lap cho doi xe",
       substr_count($s, 'js-theo-loai') . ' khoi');
}

$pdo->exec("DELETE FROM part_attributes WHERE slug LIKE 'it-attr-%'");

// ---- Don dep ----
$pdo->exec("DELETE FROM parts WHERE code LIKE 'IT-TEST-%'");

exit(summary());

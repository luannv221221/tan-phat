<?php
/**
 * Test BỘ LỌC XE Ở HEADER — hãng / dòng xe / model / đời xe.
 *
 * Chạy:  C:\xampp\php\php.exe tests\CarFilterTest.php
 *
 * Dựng riêng một cây xe test rồi kiểm tra 4 mức lọc trả về đúng phụ tùng,
 * không trùng dòng, và storefrontCount() khớp với số dòng storefront() trả về.
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
    echo "\n[SKIP] Khong ket noi duoc MySQL. Dien DB_* trong .env de chay test nay.\n";
    exit(0);
}

foreach (['LookupModel','CarBrandsModel','CarBodyTypesModel','CarModelsModel','CarYearsModel',
          'ProductBrandsModel','ProductOriginsModel','ProductUnitsModel',
          'PartsModel','PartFitmentsModel','PartCategoriesModel'] as $m){
    require_once __DIR__ . '/../app/models/' . $m . '.php';
}

echo 'PHP ' . PHP_VERSION . ' | MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";

// ---- Don du lieu test cu (model truoc body_type vi model tro toi body_type) ----
$pdo->exec("DELETE FROM parts WHERE code LIKE 'CF-TEST-%'");
$pdo->exec("DELETE FROM car_models WHERE slug LIKE 'cf-test-%'");
$pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'cf-test-%'");
$pdo->exec("DELETE FROM car_body_types WHERE slug LIKE 'cf-test-%'");
// Xoa CON truoc: part_categories.parent_id tro vao chinh bang nay va FK
// khong co ON DELETE, xoa cha khi con con song la bi chan (loi 1451).
$pdo->exec("DELETE FROM part_categories WHERE slug LIKE 'cf-test-%' AND parent_id IS NOT NULL");
$pdo->exec("DELETE FROM part_categories WHERE slug LIKE 'cf-test-%'");

// ================================================================
// Kich ban:
//   Hang CF-A: Vios (sedan) + Fortuner (suv)
//   Hang CF-B: City (sedan)
//   P1 Loc gio Vios   -> Vios doi cu
//   P2 Ma phanh chung -> Vios doi cu + Vios doi moi + Fortuner   (3 doi!)
//   P3 Loc gio Fortuner -> Fortuner
//   P4 Loc gio City   -> City
// ================================================================
$sedanId = (new CarBodyTypesModel())->add(['name' => 'CF Sedan', 'slug' => 'cf-test-sedan', 'status' => 1]);
$suvId   = (new CarBodyTypesModel())->add(['name' => 'CF SUV',   'slug' => 'cf-test-suv',   'status' => 1]);

$brandA = (new CarBrandsModel())->add(['name' => 'CF Toyota', 'slug' => 'cf-test-a', 'status' => 1]);
$brandB = (new CarBrandsModel())->add(['name' => 'CF Honda',  'slug' => 'cf-test-b', 'status' => 1]);

$vios     = (new CarModelsModel())->add(['brand_id' => $brandA, 'body_type_id' => $sedanId, 'name' => 'CF Vios',     'slug' => 'cf-test-vios',     'status' => 1]);
$fortuner = (new CarModelsModel())->add(['brand_id' => $brandA, 'body_type_id' => $suvId,   'name' => 'CF Fortuner', 'slug' => 'cf-test-fortuner', 'status' => 1]);
$city     = (new CarModelsModel())->add(['brand_id' => $brandB, 'body_type_id' => $sedanId, 'name' => 'CF City',     'slug' => 'cf-test-city',     'status' => 1]);

$viosCu   = (new CarYearsModel())->add(['model_id' => $vios,     'year_from' => 2014, 'year_to' => 2017, 'name' => 'CF Vios 2014-2017', 'status' => 1]);
$viosMoi  = (new CarYearsModel())->add(['model_id' => $vios,     'year_from' => 2018, 'year_to' => null, 'name' => 'CF Vios 2018-nay',  'status' => 1]);
$fortNay  = (new CarYearsModel())->add(['model_id' => $fortuner, 'year_from' => 2016, 'year_to' => null, 'name' => 'CF Fortuner 2016-nay', 'status' => 1]);
$cityNay  = (new CarYearsModel())->add(['model_id' => $city,     'year_from' => 2018, 'year_to' => null, 'name' => 'CF City 2018-nay',  'status' => 1]);

$unit = (new ProductUnitsModel())->findBySlug('cai');
$mk = function($code, $name, $slug) use ($unit){
    return (new PartsModel())->add([
        'code' => $code, 'name' => $name, 'slug' => $slug,
        'unit_id' => $unit['id'], 'price' => 100000, 'status' => 1,
    ]);
};
$p1 = $mk('CF-TEST-001', 'CF Lọc gió Vios',     'cf-test-loc-gio-vios');
$p2 = $mk('CF-TEST-002', 'CF Má phanh dùng chung', 'cf-test-ma-phanh');
$p3 = $mk('CF-TEST-003', 'CF Lọc gió Fortuner', 'cf-test-loc-gio-fortuner');
$p4 = $mk('CF-TEST-004', 'CF Lọc gió City',     'cf-test-loc-gio-city');

$fm = new PartFitmentsModel();
$fm->syncForPart($p1, [$viosCu]);
$fm->syncForPart($p2, [$viosCu, $viosMoi, $fortNay]);
$fm->syncForPart($p3, [$fortNay]);
$fm->syncForPart($p4, [$cityNay]);

/** Lấy mã phụ tùng test khớp bộ lọc, đã sắp xếp — bỏ qua hàng thật trong DB */
$codes = function($filters){
    $rows = (new PartsModel())->storefront($filters);
    $out = [];
    foreach ($rows as $r){
        if (strpos($r['code'], 'CF-TEST-') === 0) $out[] = $r['code'];
    }
    sort($out);
    return $out;
};

// ================================================================
section('Loc theo HANG XE (car_brand)');

ok($codes(['carBrandId' => $brandA]) === ['CF-TEST-001', 'CF-TEST-002', 'CF-TEST-003'],
   'Hang CF-A -> ra ca phu tung cua Vios lan Fortuner',
   implode(',', $codes(['carBrandId' => $brandA])));

ok($codes(['carBrandId' => $brandB]) === ['CF-TEST-004'],
   'Hang CF-B -> chi ra phu tung City');

// ================================================================
section('Loc theo DONG XE (car_body = kieu dang than xe)');

ok($codes(['carBodyTypeId' => $sedanId]) === ['CF-TEST-001', 'CF-TEST-002', 'CF-TEST-004'],
   'Sedan -> phu tung cua Vios va City, khong co Fortuner',
   implode(',', $codes(['carBodyTypeId' => $sedanId])));

ok($codes(['carBrandId' => $brandA, 'carBodyTypeId' => $sedanId]) === ['CF-TEST-001', 'CF-TEST-002'],
   'Hang CF-A + Sedan -> chi con Vios (hai dieu kien cung ap dung)');

ok($codes(['carBrandId' => $brandB, 'carBodyTypeId' => $suvId]) === [],
   'Hang CF-B + SUV -> rong (CF-B khong co xe SUV)');

// ================================================================
section('Loc theo MODEL va DOI XE — muc hep hon thang the');

ok($codes(['carModelId' => $vios]) === ['CF-TEST-001', 'CF-TEST-002'],
   'Model Vios -> phu tung cua ca hai doi Vios');

ok($codes(['carYearId' => $viosMoi]) === ['CF-TEST-002'],
   'Doi Vios 2018-nay -> chi ma phanh (loc gio chi lap doi cu)');

// Doi xe da ham y model/hang nen ba o kia khong duoc lam lech ket qua
ok($codes(['carBrandId' => $brandB, 'carBodyTypeId' => $suvId, 'carModelId' => $city, 'carYearId' => $viosMoi])
   === ['CF-TEST-002'],
   'Chon ca 4 muc -> lay muc hep nhat (doi xe), bo qua ba muc con lai');

ok($codes(['carBrandId' => $brandB, 'carModelId' => $vios]) === ['CF-TEST-001', 'CF-TEST-002'],
   'Co model thi bo qua hang (model da ham y hang)');

// ================================================================
section('Khong nhan ban dong khi phu tung lap nhieu doi xe');

// P2 lap 3 doi xe -> join part_fitments ra 3 dong cho cung 1 phu tung
$maPhanh = array_filter($codes(['carBrandId' => $brandA]), function($c){ return $c === 'CF-TEST-002'; });
ok(count($maPhanh) === 1, 'Ma phanh lap 3 doi nhung chi hien 1 lan', 'dem=' . count($maPhanh));

// ================================================================
section('storefrontCount() khop voi so dong storefront()');

// Truoc day applyStorefront() them GROUP BY vao ca cau dem, ma cau dem dung
// COUNT(DISTINCT parts.id) -> moi nhom dem ra 1, first() tra ve tong = 1.
// Phan trang vi the luon bao dung 1 trang khi loc theo xe.
$pm = new PartsModel();
foreach ([
    ['carBrandId'    => $brandA],
    ['carBodyTypeId' => $sedanId],
    ['carModelId'    => $vios],
    ['carYearId'     => $viosMoi],
] as $f){
    $rows  = $pm->storefront($f);
    $total = $pm->storefrontCount($f);
    ok($total === count($rows),
       'Dem khop danh sach voi bo loc ' . key($f),
       "count=$total, rows=" . count($rows));
}

// ================================================================
section('Loc theo xe ket hop tu khoa');

ok($codes(['carBrandId' => $brandA, 'keyword' => 'Lọc gió']) === ['CF-TEST-001', 'CF-TEST-003'],
   'Hang CF-A + tu khoa "Loc gio" -> bo ma phanh ra');

ok($codes(['carModelId' => $vios, 'keyword' => 'khong-co-gi-ten-nay']) === [],
   'Tu khoa khong khop -> rong du xe dung');

// ================================================================
section('Khong loc xe thi khong dong toi truy van');

$all = (new PartsModel())->storefront([]);
ok(count($all) >= 4, 'Khong chon xe -> tra ve toan bo hang dang ban', 'so dong=' . count($all));

// ================================================================
section('Shop::index() doc du 4 tham so tren URL');

$shop = codeOnly(__DIR__ . '/../app/controllers/Shop.php');
foreach ([
    "car_brand"  => 'carBrandId',
    "car_body"   => 'carBodyTypeId',
    "car_model"  => 'carModelId',
    "car_year"   => 'carYearId',
] as $param => $key){
    ok(strpos($shop, "'$param'") !== false && strpos($shop, "'$key'") !== false,
       "Doc tham so $param -> $key");
}

// `brand` da la thuong hieu phu tung (Bosch/Denso) nen hang xe BAT BUOC
// phai dung ten khac, khong duoc de trung.
ok(strpos($shop, "'brandIds'") !== false, 'Van giu bo loc thuong hieu phu tung rieng');

// ================================================================
section('Partial bo loc duoc header goi');

$partial = __DIR__ . '/../app/views/layouts/storefront/partials/car-filter.php';
ok(file_exists($partial), 'Co file partial car-filter.php');

$master = file_get_contents(__DIR__ . '/../app/views/layouts/storefront/master.php');
ok(strpos($master, 'partials/car-filter') !== false, 'master.php co goi partial');
ok(strpos($master, 'car-filter.css') !== false, 'master.php co nap car-filter.css');

$src = file_get_contents($partial);
ok(strpos($src, 'JSON_HEX_TAG') !== false, 'JSON nhung vao <script> co escape the HTML');
foreach (['car_brand', 'car_body', 'car_model', 'car_year'] as $n){
    ok(strpos($src, 'name="' . $n . '"') !== false, "Form co o $n");
}

// ================================================================
section('O DANH MUC tren thanh loc');

ok(strpos($src, 'name="category[]"') !== false,
   'Form co o danh muc');
ok(strpos($src, 'name="category"') === false,
   'Ten o phai co ngoac vuong',
   'Khong co [] thi khong khop voi o tick category[] o sidebar /san-pham, '
   . 'hai cho se khong nhin thay lua chon cua nhau');

// Day chuyen loc xe dung ba lai tung o theo data-cf. O danh muc lot vao do la
// bi xoa sach danh sach moi lan doi hang xe.
if (preg_match('/<select[^>]*car-filter__select--cat[^>]*>/', $src, $m)){
    ok(strpos($m[0], 'data-cf') === false,
       'O danh muc KHONG mang data-cf (day chuyen loc xe khong dung toi)',
       $m[0]);
} else {
    ok(false, 'Tim thay the <select> cua o danh muc');
}
ok(preg_match("/\\['brand',\\s*'body',\\s*'model',\\s*'year'\\]/", $src) === 1,
   'JSON day chuyen chi dung lai dung 4 o xe');

$cfCss = file_get_contents(__DIR__ . '/../public/assets/storefront/css/car-filter.css');
ok(strpos($cfCss, '.car-filter__select--cat') !== false,
   'CSS co luat rieng cho o danh muc',
   'O thu 5 la o LE cua luoi 2 cot o kho dien thoai — khong cho an tron hang '
   . 'thi no nam nua hang, chua han mot o trong ben phai');

$shopCtrl = file_get_contents(__DIR__ . '/../app/controllers/Shop.php');
ok(strpos($shopCtrl, "\$g['category']") !== false,
   'Shop::index() doc tham so category tren URL');

// ---- Loc that tren MySQL ----
// Hang hoa gan vao danh muc LA. Chon danh muc cha ma khong mo rong ca nhanh
// thi luon ra rong — day la cho de hong nhat cua o nay.
$cfCatCha = (new PartCategoriesModel())->add(['name' => 'CF Cha', 'slug' => 'cf-test-cha', 'status' => 1]);
$cfCatCon = (new PartCategoriesModel())->add(['name' => 'CF Con', 'slug' => 'cf-test-con', 'parent_id' => $cfCatCha, 'status' => 1]);
$cfCatKhac = (new PartCategoriesModel())->add(['name' => 'CF Khac', 'slug' => 'cf-test-khac', 'status' => 1]);

(new PartsModel())->edit(['category_id' => $cfCatCon],  $p1);   // Loc gio Vios
(new PartsModel())->edit(['category_id' => $cfCatKhac], $p3);   // Loc gio Fortuner

ok($codes(['categoryIds' => [$cfCatCon]]) === ['CF-TEST-001'],
   'Chon danh muc la -> ra dung hang cua no',
   implode(',', $codes(['categoryIds' => [$cfCatCon]])));

$mo = (new PartCategoriesModel())->expandWithDescendants([$cfCatCha]);
ok(in_array((int) $cfCatCon, array_map('intval', $mo), true),
   'expandWithDescendants() keo theo danh muc con');
ok($codes(['categoryIds' => $mo]) === ['CF-TEST-001'],
   'Chon danh muc CHA van ra hang cua danh muc con',
   'Khong co buoc mo rong thi chon "Phu tung" o thanh loc luon ra rong');

// Xe VA danh muc phai AND voi nhau, khong phai OR.
ok($codes(['carBrandId' => $brandA]) === ['CF-TEST-001', 'CF-TEST-002', 'CF-TEST-003'],
   'Chi loc xe: ra 3 hang');
ok($codes(['carBrandId' => $brandA, 'categoryIds' => [$cfCatCon]]) === ['CF-TEST-001'],
   'Loc xe + danh muc cung luc -> AND, chi con hang thoa CA HAI',
   implode(',', $codes(['carBrandId' => $brandA, 'categoryIds' => [$cfCatCon]])));

// Tra lai nhu cu de cac phan test sau khong bi anh huong
(new PartsModel())->edit(['category_id' => null], $p1);
(new PartsModel())->edit(['category_id' => null], $p3);

// ================================================================
section('Sidebar /san-pham khong lam mat lua chon xe');

// Form facet o sidebar la form GET rieng: no chi gui cac o gan form="facetForm".
// Neu khong chep bo loc xe vao do duoi dang hidden thi tick 1 danh muc se
// am tham xoa mat xe khach dang chon o header.
$list = file_get_contents(__DIR__ . '/../app/views/storefront/list.php');
foreach (['car_brand', 'car_body', 'car_model', 'car_year'] as $n){
    ok(strpos($list, "'$n'") !== false, "Form facet giu lai $n qua input hidden");
}

// O "Xe tuong thich" cu trong sidebar chi loc duoc theo model, yeu hon thanh
// loc o header va dung chung tham so car_model -> hai o danh nhau.
ok(strpos($list, 'Xe tương thích') === false, 'Da bo o chon xe trung lap trong sidebar');

$shopSrc = file_get_contents(__DIR__ . '/../app/controllers/Shop.php');
ok(strpos($shopSrc, "carModels'") === false && strpos($shopSrc, "carBrands'") === false,
   'Shop::index() thoi truy van danh muc xe khong con ai dung');

// ================================================================
section('Bat/tat thanh loc tu admin');

$seeded = $pdo->query("SELECT svalue FROM site_settings WHERE skey='show_car_filter'")->fetchColumn();
ok($seeded === '1', 'Migration 000050 gieo show_car_filter = 1', var_export($seeded, true));

$settingsCtrl = codeOnly(__DIR__ . '/../app/controllers/admin/Settings.php');
ok(strpos($settingsCtrl, "'show_car_filter'") !== false,
   'show_car_filter nam trong whitelist cua admin Settings');

$formSrc = file_get_contents(__DIR__ . '/../app/views/admin/settings/form.php');
// O tick khong tick thi trinh duyet khong gui gi -> phai co hidden cung ten
// dung truoc, neu khong thi khong bao gio tat duoc.
ok(preg_match('~type="hidden"\s+name="show_car_filter"\s+value="0"~', $formSrc) === 1,
   'Form admin co input hidden = 0 di kem o tick');
ok(strpos($formSrc, 'type="checkbox"') !== false && strpos($formSrc, 'id="show_car_filter"') !== false,
   'Form admin co o tick show_car_filter');

// Chi an khi bi TAT han. Thieu khoa (chua chay migration) van phai hien,
// khong thi day code len truoc migration la mat thanh loc tren web khach.
ok(strpos($master, "'show_car_filter'") !== false && strpos($master, "!== '0'") !== false,
   'master.php an thanh loc khi = 0, thieu khoa thi van hien');

// ================================================================
section('Bat/tat thanh xanh tren cung — khong duoc mat loi dang nhap');

$seededTop = $pdo->query("SELECT svalue FROM site_settings WHERE skey='show_topbar'")->fetchColumn();
ok($seededTop === '1', 'Migration 000051 gieo show_topbar = 1', var_export($seededTop, true));

ok(strpos($settingsCtrl, "'show_topbar'") !== false,
   'show_topbar nam trong whitelist cua admin Settings');
ok(preg_match('~type="hidden"\s+name="show_topbar"\s+value="0"~', $formSrc) === 1,
   'Form admin co input hidden = 0 di kem o tick topbar');

ok(strpos($master, '$showTopbar') !== false && strpos($master, "'show_topbar'") !== false,
   'master.php doc show_topbar');

// Bang `menus` KHONG co muc dang nhap nao, nen an topbar ma khong doi cho link
// tai khoan la khach het loi vao tai khoan, va nguoi dang dang nhap khong con
// nut dang xuat. Day la rang buoc nghiep vu, khong phai chi tiet giao dien.
$menuUrls = $pdo->query("SELECT url FROM menus WHERE status=1")->fetchAll(PDO::FETCH_COLUMN);
$menuCoDangNhap = false;
foreach ($menuUrls as $u){
    if (strpos((string) $u, 'thanh-vien') !== false) $menuCoDangNhap = true;
}
ok(!$menuCoDangNhap, 'Menu website van khong co muc dang nhap (nen rang buoc duoi day con y nghia)');

ok(substr_count($master, '$renderAccountLinks()') >= 2,
   'Link tai khoan duoc in o CA thanh xanh LAN hang logo (khong mat khi tat topbar)');

// $this->render() dung require_once nen goi lan hai se im lang khong in gi.
// Vi vay link tai khoan phai la closure, khong duoc tach thanh partial.
ok(strpos($master, '$renderAccountLinks = function') !== false,
   'Link tai khoan viet bang closure (partial + require_once se mat ban mobile)');

// ================================================================
section('Goi y tim kiem (ajax) o header');

$shopAll = file_get_contents(__DIR__ . '/../app/controllers/Shop.php');
ok(strpos($shopAll, 'public function suggest()') !== false, 'Shop::suggest() ton tai');
ok(strpos($shopAll, 'application/json') !== false, 'suggest() tra Content-Type json');

// Ton kho chi hien cho thanh vien (TASK_79) ma endpoint nay CONG KHAI.
// Phai cat DUNG than ham suggest() roi moi kiem: quet ca file thi se trung
// sellableByPart cua detail() nam ngay duoi va bao fail oan.
$body = '';
if (preg_match('~function suggest\(\)\s*\{(.*?)\n    \}~s', $shopAll, $mm)){
    $body = $mm[1];
}
ok($body !== '', 'Tach duoc than ham suggest() de kiem tra');
ok(strpos($body, 'sellableByPart') === false && strpos($body, 'StocksModel') === false,
   'Than ham suggest() KHONG dung toi ton kho');

$routes = codeOnly(__DIR__ . '/../routes/web.php');
ok(strpos($routes, "'tim-kiem/goi-y'") !== false, 'Da dang ky route tim-kiem/goi-y');

// Route chi tiet san pham bat MOI chuoi sau 'san-pham/'. Neu endpoint goi y
// dat cung tien to thi phai canh thu tu khai bao — tach tien to rieng thi
// khong bao gio dung nhau.
ok(strpos($routes, "'san-pham/goi-y'") === false,
   'Endpoint goi y khong nam duoi tien to san-pham/ (tranh dung route chi tiet)');

$cf = file_get_contents($partial);
ok(strpos($cf, 'car-filter__suggest') !== false, 'Partial co khung goi y');
ok(strpos($cf, 'setTimeout(fetchSuggest') !== false, 'Co debounce truoc khi goi server');
ok(strpos($cf, 'mine !== seq') !== false, 'Bo qua phan hoi ve tre (chong nhay ket qua cu)');
ok(strpos($cf, 'textContent = it.name') !== false,
   'Ten hang do vao textContent, khong dung innerHTML (chong chen the)');

$css = file_get_contents(__DIR__ . '/../public/assets/storefront/css/car-filter.css');
// overflow:hidden o .car-filter__form se cat sach khung goi y (position:absolute
// do xuong duoi form). Da dinh mot lan roi.
ok(!preg_match('~\.car-filter__form\s*\{[^}]*overflow:\s*hidden~s', $css),
   'form KHONG co overflow:hidden (se cat mat khung goi y)');

// ---- Kiem tra that qua HTTP neu Apache dang chay ----
$base = 'http://localhost:88/tan-phat/tim-kiem/goi-y';
$ctx  = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
$hit  = @file_get_contents($base . '?q=loc', false, $ctx);

if ($hit === false){
    echo "  [SKIP] Apache khong chay — bo qua phan goi HTTP that\n";
} else {
    $d = json_decode($hit, true);
    ok(is_array($d) && isset($d['items']), 'Tra ve JSON co khoa items');

    $one = json_decode(@file_get_contents($base . '?q=l', false, $ctx), true);
    ok(isset($one['items']) && count($one['items']) === 0, 'Duoi 2 ky tu -> rong');

    ok(count($d['items']) > 0, 'Tu khoa "loc" ra ket qua', count($d['items']) . ' dong');

    $keys = !empty($d['items']) ? array_keys($d['items'][0]) : [];
    sort($keys);
    ok($keys === ['code', 'image', 'name', 'price', 'url'],
       'Chi tra dung 5 truong can cho goi y', implode(',', $keys));
    ok(strpos($hit, 'quantity') === false && strpos($hit, 'stock') === false,
       'Khong lo du lieu ton kho');

    // Bo loc xe phai an vao goi y: dung xe ra, sai xe khong ra
    $dung = json_decode(@file_get_contents($base . '?q=test&car_brand=2', false, $ctx), true);
    $sai  = json_decode(@file_get_contents($base . '?q=test&car_brand=1', false, $ctx), true);
    ok(count($dung['items']) > count($sai['items']),
       'Chon dung xe ra nhieu ket qua hon chon sai xe',
       'Honda=' . count($dung['items']) . ' Toyota=' . count($sai['items']));

    // Tu khoa doc hai khong duoc pha truy van
    @file_get_contents($base . "?q=" . rawurlencode("' OR 1=1 --"), false, $ctx);
    $conParts = $pdo->query('SELECT COUNT(*) FROM parts')->fetchColumn();
    ok($conParts > 0, 'Bang parts VAN CON sau khi thu injection', $conParts . ' dong');
}

// ---- Don dep ----
$pdo->exec("DELETE FROM parts WHERE code LIKE 'CF-TEST-%'");
$pdo->exec("DELETE FROM car_models WHERE slug LIKE 'cf-test-%'");
$pdo->exec("DELETE FROM car_brands WHERE slug LIKE 'cf-test-%'");
$pdo->exec("DELETE FROM car_body_types WHERE slug LIKE 'cf-test-%'");
// Xoa CON truoc: part_categories.parent_id tro vao chinh bang nay va FK
// khong co ON DELETE, xoa cha khi con con song la bi chan (loi 1451).
$pdo->exec("DELETE FROM part_categories WHERE slug LIKE 'cf-test-%' AND parent_id IS NOT NULL");
$pdo->exec("DELETE FROM part_categories WHERE slug LIKE 'cf-test-%'");

exit(summary());

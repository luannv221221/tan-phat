<?php
/**
 * Test HAI VIỆC 09/11/2026 — hai thứ khách chưa yêu cầu nhưng sẽ cần.
 *
 * Chạy:  C:\xampp\php\php.exe tests\VatMuaHangVaGiaWebTest.php
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  A. ĐƠN MUA CÓ VAT VÀ CHIẾT KHẤU
 * ──────────────────────────────────────────────────────────────────────────
 * Chỗ nguy hiểm duy nhất của việc này, và cũng là chỗ không ai nhìn ra:
 *
 *   VAT KHÔNG ĐƯỢC CHẢY VÀO GIÁ VỐN. Thuế GTGT đầu vào là khoản được khấu
 *   trừ, không phải giá trị hàng tồn. Cộng nó vào phiếu nhập là thổi giá vốn
 *   lên 8–10% ở MỌI mặt hàng, kéo theo lãi gộp của mọi hoá đơn bán sau này
 *   sai — và không có dòng nào báo lỗi. Chỉ phát hiện khi kiểm kho, muộn
 *   vài tuần.
 *
 *   CHIẾT KHẤU thì NGƯỢC LẠI: giảm giá là tiền thật trả cho hàng giảm đi, nên
 *   nó PHẢI đi vào giá vốn. Bỏ sót là lãi gộp bị tính thiếu.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  B. GIÁ RIÊNG CỦA GARA LÊN TRANG KHÁCH
 * ──────────────────────────────────────────────────────────────────────────
 * Trong quản trị, gara đặt giá riêng cho hàng kho tổng từ lâu rồi. Gian hàng
 * công khai thì vẫn đọc `parts`.`price`, nên khách thấy GIÁ KHO TỔNG còn gara
 * báo giá ra số khác.
 *
 * Sửa chỗ hiển thị thôi là CHƯA ĐỦ, và nửa vời còn tệ hơn không làm:
 *   - LỌC theo giá phải dùng giá hiệu lực, không thì lọc "dưới 500k" vẫn ra
 *     món gara bán 900k
 *   - SẮP XẾP theo giá cũng vậy, không thì danh sách lộn xộn ngay trước mắt
 *   - "ĐANG KHUYẾN MÃI" phải hỏi giá KM hiệu lực
 *   - GIỎ HÀNG phải tính cùng một giá, không thì khách nhìn một giá rồi bị
 *     tính một giá khác lúc đặt
 *
 * Và một cái bẫy ngược chiều:
 *   getDetail() PHẢI GIỮ NGUYÊN giá gốc. Nó đổ dữ liệu vào form Sửa hàng hoá
 *   bên quản trị — trả giá đã đè ở đó là bấm Lưu một cái, giá riêng của gara
 *   ghi đè lên danh mục chung của TẤT CẢ các gara.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;
use App\core\Load;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ===========================================================================
section('A — migration 000106');

$mg = glob($goc . 'database/migrations/*_000106_*.php');
ok(count($mg) === 1, 'Co dung mot migration 000106', 'Tim thay ' . count($mg));
$sql = count($mg) === 1 ? file_get_contents($mg[0]) : '';

foreach ([['purchase_orders', 'vat_rate'], ['purchase_orders', 'subtotal'],
          ['purchase_orders', 'tax_amount'],
          ['purchase_order_items', 'discount_percent']] as $c){
    ok(strpos($sql, '`' . $c[1] . '`') !== false, 'Migration them `' . $c[0] . '`.`' . $c[1] . '`');
}
ok(substr_count($sql, 'hasColumn') >= 5, 'Co kiem cot truoc khi them — chay lai duoc');
ok(strpos($sql, 'DEFAULT 0') !== false,
   'Cac cot moi mac dinh 0 — don cu chay y nhu cu, khong lech mot dong nao');

// ===========================================================================
section('A — VAT khong duoc chay vao gia von');

$cPo = codeOnly($goc . 'app/controllers/admin/Purchaseorders.php');

$viTao = strpos($cPo, 'function taoPhieuNhap');
$than  = $viTao !== false ? substr($cPo, $viTao, 3000) : '';

ok($than !== '' && strpos($than, 'discount_percent') !== false,
   'taoPhieuNhap() co doc chiet khau');
ok(preg_match('~giaSauCk~', $than) === 1,
   'Gia von la gia DA TRU chiet khau');
ok(strpos($than, 'vat_rate') === false && strpos($than, 'tax_amount') === false,
   'taoPhieuNhap() KHONG dung toi vat_rate / tax_amount',
   'Cham vao la som muon thue chay vao gia von — sai 8-10% o moi mat hang, '
   . 'khong dong nao bao loi');

$cItem = codeOnly($goc . 'app/models/PurchaseOrderItemsModel.php');
ok(preg_match('~discount_percent.*?(100|min|max)~s', $cItem) === 1,
   'syncForPo() kep chiet khau trong 0-100',
   'Am thi thanh tien phinh ra, tren 100 thi thanh tien am');
ok(preg_match('~1\s*-\s*\$ck\s*/\s*100~', $cItem) === 1,
   'Thanh tien = SL x gia x (1 - CK%)');

// ===========================================================================
section('B — gia rieng cua gara tren trang khach');

$cParts = codeOnly($goc . 'app/models/PartsModel.php');

ok(strpos($cParts, 'GIA_WEB') !== false && strpos($cParts, 'GIA_KM_WEB') !== false,
   'Co bieu thuc gia hieu luc dung chung');
ok(preg_match('~GIA_WEB\s*=\s*\x27COALESCE~', $cParts) === 1,
   'Gia = COALESCE(gia rieng, gia kho tong)');
ok(preg_match('~garaGianHang.*?is_master~s', $cParts) === 1,
   'Kho tong khong bi de gia — danh muc chung chinh la cua no');
ok(preg_match('~chiHangLenWeb.*?leftJoin\(\x27garage_part_prices~s', $cParts) === 1,
   'Noi bang gia rieng tai chiHangLenWeb() — chokepoint duy nhat',
   'Noi o noi khac la som muon co mot duong quen noi, va duong do am tham '
   . 'hien gia kho tong');
ok(preg_match('~leftJoin\(\x27garage_part_prices.*?garage_id.*?=~s', $cParts) === 1,
   'Dieu kien gara nam trong ON chu khong phai WHERE',
   'Day xuong WHERE thi LEFT JOIN thanh INNER JOIN, va moi mat hang gara CHUA '
   . 'dat gia rieng bien mat khoi gian hang');

/* Lọc, sắp xếp, khuyến mãi — ba chỗ dễ bỏ sót nhất */
ok(preg_match('~priceMin.*?whereRaw~s', $cParts) === 1, 'Loc gia dung giá hieu luc');
ok(preg_match('~promo.*?whereRaw~s', $cParts) === 1, 'Loc khuyen mai dung giá KM hieu luc');
ok(preg_match("~price_asc.*?orderByRaw~s", $cParts) === 1, 'Sap xep dung giá hieu luc');

/* Cái bẫy ngược chiều */
ok(strpos($cParts, 'function chiTietWeb') !== false,
   'Co chiTietWeb() rieng cho trang khach');
$viGd = strpos($cParts, 'function getDetail');
$thanGd = $viGd !== false ? substr($cParts, $viGd, 260) : '';
ok($thanGd !== '' && strpos($thanGd, 'selectWithJoinsWeb') === false
   && strpos($thanGd, 'GIA_WEB') === false,
   'getDetail() KHONG de gia — form sua hang hoa phai thay gia goc',
   'De gia o day la bam Luu mot cai, gia rieng cua gara ghi de len danh muc '
   . 'chung cua TAT CA cac gara');

$cCart = codeOnly($goc . 'app/controllers/Cart.php');
ok(preg_match('~buildRows.*?chiTietWeb~s', $cCart) === 1,
   'Gio hang tinh tien bang chiTietWeb()',
   'Dung getDetail() la khach nhin mot gia tren trang san pham roi bi tinh '
   . 'mot gia khac luc dat hang');

/* QueryBuilder — hai hàm mới */
$cQb = codeOnly($goc . 'core/QueryBuilder.php');
ok(strpos($cQb, 'function whereRaw') !== false, 'QueryBuilder co whereRaw()');
ok(strpos($cQb, 'function orderByRaw') !== false, 'QueryBuilder co orderByRaw()');
ok(preg_match('~function whereRaw.*?bindings~s', $cQb) === 1,
   'whereRaw() nhan tham so bind — gia tri KHONG noi thang vao SQL');

// ===========================================================================
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$cot = function($bang) use ($pdo){
    $ra = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$bang`") as $r) $ra[$r['Field']] = $r;
    return $ra;
};
$cpo = $cot('purchase_orders');
$cpi = $cot('purchase_order_items');
ok(isset($cpo['vat_rate']) && isset($cpo['subtotal']) && isset($cpo['tax_amount']),
   'CSDL co purchase_orders.vat_rate / subtotal / tax_amount');
ok(isset($cpi['discount_percent']), 'CSDL co purchase_order_items.discount_percent');

/* Đơn cũ (nếu còn) phải có subtotal khớp total — migration đã chép sang */
$lech = (int) $pdo->query(
    'SELECT COUNT(*) FROM `purchase_orders`
      WHERE `tax_amount` = 0 AND `subtotal` <> `total_amount`')->fetchColumn();
ok($lech === 0, 'Don khong thue thi subtotal = total_amount',
   $lech . ' don lech — man hinh se hien "Cong chua thue: 0" canh "Tong cong: 12.000.000"');

// --- A. Chạy thật: VAT ra ngoài giá vốn, chiết khấu đi vào -----------------
$gara = (int) $pdo->query('SELECT `id` FROM `garages` WHERE `status` = 1 ORDER BY `id` LIMIT 1')->fetchColumn();
$part = (int) $pdo->query('SELECT `id` FROM `parts` ORDER BY `id` LIMIT 1')->fetchColumn();

if ($gara > 0 && $part > 0){
    $DAU = 'ZZV0911-';
    $kq = Model::trongGara($gara, function() use ($DAU, $part){
        $PO = Load::model('PurchaseOrdersModel');
        $IT = Load::model('PurchaseOrderItemsModel');

        $id = (int) $PO->add(['po_no' => $DAU . 'PO', 'partner_name' => $DAU . 'NCC',
                              'order_date' => date('Y-m-d'), 'status' => 0]);
        // 10 cái x 100.000, chiết khấu 10%  ->  900.000 chưa thuế
        $IT->syncForPo($id, [['part_id' => $part, 'quantity' => 10,
                              'unit_price' => 100000, 'discount_percent' => 10]]);
        return ['id' => $id, 'tong' => (float) $IT->tongTheoPo($id),
                'dong' => $IT->getByPo($id)];
    });

    ok(abs($kq['tong'] - 900000) < 0.01,
       'Chiet khau 10% vao thanh tien: 10 x 100.000 - 10% = 900.000',
       'Ra ' . $kq['tong']);

    $d = !empty($kq['dong']) ? reset($kq['dong']) : [];
    ok(!empty($d) && abs((float) $d['discount_percent'] - 10) < 0.01,
       'Chiet khau luu dung 10%');

    /* Kẹp 0–100 */
    $kep = Model::trongGara($gara, function() use ($kq, $part){
        $IT = Load::model('PurchaseOrderItemsModel');
        $IT->syncForPo($kq['id'], [['part_id' => $part, 'quantity' => 1,
                                    'unit_price' => 1000, 'discount_percent' => 250]]);
        $d = $IT->getByPo($kq['id']);
        return !empty($d) ? reset($d) : [];
    });
    ok(!empty($kep) && abs((float) $kep['discount_percent'] - 100) < 0.01,
       'Chiet khau 250% bi kep xuong 100', 'Ra ' . ($kep['discount_percent'] ?? '?'));
    ok(!empty($kep) && abs((float) $kep['amount']) < 0.01,
       'Chiet khau 100% -> thanh tien 0, khong am');

    $pdo->exec('DELETE FROM `purchase_orders` WHERE `po_no` LIKE ' . $pdo->quote($DAU . '%'));
    ok((int) $pdo->query('SELECT COUNT(*) FROM `purchase_orders` WHERE `po_no` LIKE '
       . $pdo->quote($DAU . '%'))->fetchColumn() === 0, 'Da don don mua thu');
}

// --- B. Chạy thật: giá riêng lên web --------------------------------------
$mau = $pdo->query(
    'SELECT g.`garage_id`, g.`part_id`, g.`price` AS gia_rieng, p.`price` AS gia_tong
       FROM `garage_part_prices` g
       JOIN `parts` p ON p.`id` = g.`part_id`
       JOIN `garages` ga ON ga.`id` = g.`garage_id`
      WHERE g.`price` IS NOT NULL AND g.`status` = 1
        AND p.`show_on_web` = 1 AND p.`status` = 1
        AND (ga.`is_master` = 0 OR ga.`is_master` IS NULL)
      LIMIT 1')->fetch(PDO::FETCH_ASSOC);

if (empty($mau)){
    echo "  [SKIP] Khong co gara nao dat gia rieng cho hang len web.\n";
    exit(summary());
}

$gid  = (int) $mau['garage_id'];
$pid  = (int) $mau['part_id'];
$rieng = (float) $mau['gia_rieng'];
$tong  = (float) $mau['gia_tong'];

$web = Model::trongGara($gid, function() use ($pid){
    return Load::model('PartsModel')->chiTietWeb($pid);
});
ok(!empty($web) && abs((float) $web['price'] - $rieng) < 0.01,
   'Trang khach cua gara hien GIA RIENG (' . $rieng . ')',
   'Hien ' . (isset($web['price']) ? $web['price'] : 'khong thay'));
ok(!empty($web) && $web['sale_price'] === null,
   'Gara tu dinh gia thi KM cua kho tong khong con ap dung',
   'Khong thi gan nhan giam gia cho mon gara dang ban nguyen gia');
ok(!empty($web) && abs((float) $web['gia_goc'] - $tong) < 0.01,
   'Van giu lai gia kho tong o cot gia_goc');

/* Lọc theo giá phải bắt được theo giá hiệu lực */
$trong = Model::trongGara($gid, function() use ($rieng){
    return Load::model('PartsModel')->storefront(
        ['priceMin' => $rieng - 1, 'priceMax' => $rieng + 1], 500);
});
$thay = false;
foreach ((array) $trong as $r) if ((int) $r['id'] === $pid) $thay = true;
ok($thay, 'Loc theo khoang GIA RIENG tim thay mat hang do');

/* Và KHÔNG được bắt theo giá kho tổng (khi hai giá khác nhau đủ xa) */
if (abs($tong - $rieng) > 2){
    $ngoai = Model::trongGara($gid, function() use ($tong){
        return Load::model('PartsModel')->storefront(
            ['priceMin' => $tong - 1, 'priceMax' => $tong + 1], 500);
    });
    $lot = false;
    foreach ((array) $ngoai as $r) if ((int) $r['id'] === $pid) $lot = true;
    ok(!$lot, 'Loc theo khoang GIA KHO TONG KHONG con tim thay',
       'Con tim thay nghia la van dang loc theo cot goc');
}

/* Kho tổng vẫn thấy giá gốc */
$master = (int) $pdo->query('SELECT `id` FROM `garages` WHERE `is_master` = 1 LIMIT 1')->fetchColumn();
if ($master > 0){
    $mWeb = Model::trongGara($master, function() use ($pid){
        return Load::model('PartsModel')->chiTietWeb($pid);
    });
    if (!empty($mWeb)){
        ok(abs((float) $mWeb['price'] - $tong) < 0.01,
           'Kho tong van hien gia goc, khong bi de');
    }
}

/* Bẫy ngược chiều: getDetail() giữ nguyên */
$ad = Model::trongGara($gid, function() use ($pid){
    return Load::model('PartsModel')->getDetail($pid);
});
ok(!empty($ad) && abs((float) $ad['price'] - $tong) < 0.01,
   'getDetail() van tra gia GOC cho form sua hang hoa',
   'Tra gia da de la bam Luu mot cai, gia rieng ghi de len danh muc chung');

exit(summary());

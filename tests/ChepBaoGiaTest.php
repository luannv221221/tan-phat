<?php
/**
 * Test NÚT "CHÉP TỪ BÁO GIÁ CŨ" ở form Lập báo giá.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChepBaoGiaTest.php
 *
 * Gara hay lặp lại đúng một đơn: chi nhánh mới mở đặt y hệt chi nhánh cũ, khách
 * cũ đặt lại combo bảo dưỡng. Gõ lại từng dòng là việc thừa.
 *
 * BỐN CHỖ DỄ HỎNG, mỗi chỗ một phần test:
 *   1. Dòng phải về ĐÚNG TAB. Dịch vụ lọt sang tab Hàng hoá là ô chọn bên đó
 *      không có mặt hàng ấy -> dòng trống, lưu một cái là mất luôn.
 *   2. GIÁ. Báo giá cũ giữ giá lúc lập; chép nguyên si là chào lại giá lỗi thời.
 *      Phải trả về CẢ HAI giá để người lập tự chọn.
 *   3. Mặt hàng đã XOÁ / NGỪNG BÁN. Bỏ qua thì phải BÁO, không được im lặng —
 *      người lập tưởng chép đủ rồi gửi khách một báo giá thiếu mục.
 *   4. Khách hàng. Chép dòng của đơn khách khác là chuyện bình thường; không
 *      được nhân đó mà đổi luôn khách của phiếu đang lập.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Route + giao dien');

$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "Route::get('quotations/copy-list'") !== false, 'Co route copy-list');
ok(strpos($routes, "Route::get('quotations/copy-lines/(\\d+)'") !== false, 'Co route copy-lines');

$v = file_get_contents($goc . 'app/views/admin/quotations/add.php');
ok(strpos($v, 'id="btn-chep"') !== false,      'Form co nut "Chep tu bao gia cu"');
ok(strpos($v, 'id="chep-hop"') !== false,      'Co hop chon bao gia');
ok(strpos($v, 'id="chep-tim"') !== false,      'Co o loc theo so bao gia / ten khach');
ok(strpos($v, 'id="chep-gia-moi"') !== false,  'Co tick "Lay gia hien tai"');
ok(strpos($v, 'GOC_ADMIN') !== false,
   'PHP nhet duong dan goc admin xuong cho JS',
   'JS khong tu biet /tan-phat/admin nam o dau');

ok(strpos($v, 'xoaHet: xoaHet') !== false && strpos($v, 'napLai: napLaiTatCa') !== false,
   'taoBang mo ra xoaHet + napLai cho nut chep dung');

ok(strpos($v, 'THAY TOAN BO') !== false,
   'Hoi truoc khi thay sach dong dang co',
   'Chep la ghi de toan bo — bam nham ma mat het thi rat kho chiu');

ok(strpos($v, 'credentials:') !== false,
   'fetch gui kem cookie phien',
   'Thieu thi endpoint tuong chua dang nhap va da ve trang login');

// ---------------------------------------------------------------------------
section('Chot trong controller');

$c = codeOnly($goc . 'app/controllers/admin/Quotations.php');
ok(strpos($c, 'function copyList') !== false,  'Co copyList()');
ok(strpos($c, 'function copyLines') !== false, 'Co copyLines()');
ok(substr_count($c, "route('admin/' . \$this->routeBase . '/add')") >= 2,
   'Ca hai endpoint deu kiem quyen `add`',
   'Endpoint JSON khong duoc de ho hon chinh man hinh no phuc vu');
ok(strpos($c, 'LOAI_DICH_VU') !== false, 'Tach dong theo item_type (hang / dich vu)');
ok(strpos($c, "'gia_cu'") !== false && strpos($c, "'gia_moi'") !== false,
   'Tra ve CA HAI gia: gia cu va gia hien tai');
ok(strpos($c, "'bo_qua'") !== false, 'Tra ve so dong bi bo qua');

// ---------------------------------------------------------------------------
section('Truy van that tren MySQL');

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(summary());
}

require_once $goc . 'app/models/QuotationsModel.php';
require_once $goc . 'app/models/QuotationItemsModel.php';

$don = function() use ($pdo){
    $ids = $pdo->query("SELECT id FROM quotations WHERE quote_no LIKE 'CB-TEST-%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id){ $pdo->exec("DELETE FROM quotation_items WHERE quotation_id = " . (int) $id); }
    $pdo->exec("DELETE FROM quotations WHERE quote_no LIKE 'CB-TEST-%'");
    $pdo->exec("DELETE FROM parts WHERE code LIKE 'CB-TEST-%'");
};
$don();

$now = date('Y-m-d H:i:s');

// Ba mặt hàng: 1 phụ tùng còn bán, 1 dịch vụ còn bán, 1 phụ tùng ĐÃ NGỪNG BÁN
$taoHang = function($ma, $ten, $loai, $gia, $status) use ($pdo, $now){
    $pdo->prepare("INSERT INTO parts (code,name,slug,item_type,price,status,show_on_web,create_at)
                   VALUES (?,?,?,?,?,?,1,?)")
        ->execute([$ma, $ten, strtolower($ma), $loai, $gia, $status, $now]);
    return (int) $pdo->lastInsertId();
};
$pt   = $taoHang('CB-TEST-PT', 'CB Phu tung',  'part',    100000, 1);
$dv   = $taoHang('CB-TEST-DV', 'CB Dich vu',   'service',  50000, 1);
$tat  = $taoHang('CB-TEST-OFF','CB Ngung ban', 'part',     70000, 0);

// Báo giá cũ: 3 dòng, giá lúc lập KHÁC giá hiện tại
// customer_id để NULL chứ không phải 0: cột này có khoá ngoại sang `partners`,
// số 0 không trỏ tới dòng nào nên MySQL chặn ngay (lỗi 1452).
$pdo->prepare("INSERT INTO quotations (quote_no,customer_id,quote_date,vat_rate,subtotal,tax_amount,total_amount,status,create_at)
               VALUES (?,?,?,?,?,?,?,?,?)")
    ->execute(['CB-TEST-001', null, date('Y-m-d'), 10, 0, 0, 0, 'draft', $now]);
$bg = (int) $pdo->lastInsertId();

$themDong = function($partId, $sl, $gia, $ck) use ($pdo, $bg){
    $pdo->prepare("INSERT INTO quotation_items (quotation_id,part_id,quantity,unit_price,discount_percent,amount)
                   VALUES (?,?,?,?,?,0)")
        ->execute([$bg, $partId, $sl, $gia, $ck]);
};
$themDong($pt,  2, 88000, 5);    // giá cũ 88.000, hiện tại 100.000
$themDong($dv,  1, 50000, 0);
$themDong($tat, 3, 70000, 0);    // mặt hàng đã ngừng bán

$im = new QuotationItemsModel();
$dong = $im->dongDeChep($bg);

ok($im->demDong($bg) === 3, 'demDong() dem du 3 dong');
ok(count($dong) === 3, 'dongDeChep() doc du 3 dong (mat hang deu con trong bang parts)');

$theoMa = [];
foreach ($dong as $d){ $theoMa[$d['part_code']] = $d; }

ok(isset($theoMa['CB-TEST-PT']) && $theoMa['CB-TEST-PT']['item_type'] === 'part',
   'Phu tung duoc danh dau item_type=part -> vao tab Hang hoa');
ok(isset($theoMa['CB-TEST-DV']) && $theoMa['CB-TEST-DV']['item_type'] === 'service',
   'Dich vu duoc danh dau item_type=service -> vao tab Dich vu',
   'Lot sang tab Hang hoa la o chon ben do khong co mat hang ay -> dong trong');

ok((int) $theoMa['CB-TEST-PT']['unit_price'] === 88000,
   'Giu duoc gia CU cua bao gia (88.000)');
ok((int) $theoMa['CB-TEST-PT']['gia_bay_gio'] === 100000,
   'Kem theo gia HIEN TAI (100.000)',
   'Chep nguyen gia cu la chao lai muc gia da loi thoi');
ok((float) $theoMa['CB-TEST-PT']['discount_percent'] === 5.0, 'Giu nguyen % chiet khau');
ok((float) $theoMa['CB-TEST-PT']['quantity'] === 2.0, 'Giu nguyen so luong');

ok((int) $theoMa['CB-TEST-OFF']['con_ban'] === 0,
   'Mat hang ngung ban duoc danh dau con_ban=0',
   'Controller dua vao co nay de loai truoc, thay vi de toi luc luu moi bao loi');

// ---------------------------------------------------------------------------
section('Danh sach bao gia de chon');

$qm = new QuotationsModel();
$ds = $qm->danhSachDeChep(0, 50);
$soMa = array_map(function($x){ return $x['quote_no']; }, $ds);
ok(in_array('CB-TEST-001', $soMa, true), 'Bao gia test co trong danh sach');

$hang = null;
foreach ($ds as $x){ if ($x['quote_no'] === 'CB-TEST-001') $hang = $x; }
ok($hang && (int) $hang['so_dong'] === 3,
   'Kem so dong hang de biet don nao dang chep',
   'Don 0 dong chep ve cung chang duoc gi');

// Ưu tiên đơn của khách đang chọn
$kh = (int) $pdo->query("SELECT customer_id FROM quotations WHERE customer_id > 0 LIMIT 1")->fetchColumn();
if ($kh > 0){
    $ds2 = $qm->danhSachDeChep($kh, 50);
    ok(!empty($ds2) && (int) $ds2[0]['uu_tien'] === 0,
       'Bao gia CUA KHACH DANG CHON duoc day len dau',
       'Gara hay lap lai don cua chinh minh; de lan vao danh sach chung thi phai do');

    $uu = array_map(function($x){ return (int) $x['uu_tien']; }, $ds2);
    ok($uu === array_values(array_merge(array_filter($uu, function($x){ return $x === 0; }),
                                        array_filter($uu, function($x){ return $x === 1; }))),
       'Nhom uu tien nam lien mach o dau danh sach');
} else {
    echo "  [SKIP] Khong co bao gia nao gan khach de thu uu tien.\n";
}

$don();

exit(summary());

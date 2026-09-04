<?php
/**
 * Test CHÉP DÒNG HÀNG TỪ CHỨNG TỪ CŨ — ở màn Hoá đơn bán.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChepHoaDonTest.php
 *
 * Giống nút đã có ở màn Báo giá, nhưng KHÁC hai điểm và cả hai đều dễ hỏng:
 *
 *   1. HOÁ ĐƠN TRỪ TỒN KHO. Chép về một mặt hàng kho không còn thì lúc ghi sổ
 *      mới bị chặn — người lập đã in phiếu đưa khách rồi. Nên kết quả chép phải
 *      kèm tồn của KHO ĐANG CHỌN, và đếm ra bao nhiêu mặt hàng thiếu.
 *
 *      Tồn phải theo kho người dùng chọn trên form, không phải kho mặc định:
 *      báo nhầm số của kho khác còn tệ hơn không báo gì.
 *
 *   2. QUYỀN PHẢI GÁC THEO `sales-invoices/add`. Chép nguyên hàm từ màn Báo giá
 *      sang là rất dễ để nguyên `route('admin/quotations/add')` — người chỉ được
 *      lập hoá đơn sẽ bị từ chối, còn người chỉ được lập báo giá lại đọc được
 *      danh sách hoá đơn.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Duong dan va quyen');

$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "'sales-invoices/copy-list'") !== false, 'Co route sales-invoices/copy-list');
ok(strpos($routes, "'sales-invoices/copy-lines/(\\d+)'") !== false, 'Co route sales-invoices/copy-lines/{id}');

$ctl = codeOnly($goc . 'app/controllers/admin/Salesinvoices.php');

foreach (['copyList', 'copyLines'] as $ham){
    $than = '';
    if (preg_match('~public function ' . $ham . '\([^)]*\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $than = $m[1];
    ok($than !== '', "Doc duoc than ham $ham()");
    ok(strpos($than, "\$this->routeBase . '/add'") !== false,
       "$ham() gac quyen theo routeBase cua CHINH man Hoa don",
       'Chep nguyen tu man Bao gia sang la de con nguyen quotations/add');
    ok(strpos($than, 'quotations/add') === false,
       "$ham() KHONG gac nham theo quyen cua man Bao gia");
}

// ---------------------------------------------------------------------------
section('Model co du ham tra cuu');

ok(strpos(codeOnly($goc . 'app/models/SalesInvoicesModel.php'), 'function danhSachDeChep') !== false,
   'SalesInvoicesModel co danhSachDeChep()');
$mItem = codeOnly($goc . 'app/models/SalesInvoiceItemsModel.php');
ok(strpos($mItem, 'function dongDeChep') !== false, 'SalesInvoiceItemsModel co dongDeChep()');
ok(strpos($mItem, 'function demDong') !== false,    'SalesInvoiceItemsModel co demDong()');
ok(strpos($mItem, 'gia_bay_gio') !== false,
   'dongDeChep() tra ve CA gia hien tai',
   'Chep mu gia cu thi ban lo ma khong ai biet');
ok(strpos(codeOnly($goc . 'app/models/StocksModel.php'), 'function tonTheoNhieuHang') !== false,
   'StocksModel co tonTheoNhieuHang()');

// ---------------------------------------------------------------------------
section('Giao dien');

$v = file_get_contents($goc . 'app/views/admin/sales-invoices/add.php');
ok(strpos($v, 'id="btn-chep"') !== false,  'Co nut Chep tren form');
ok(strpos($v, 'id="chep-hop"') !== false,  'Co hop thoai chep');
ok(strpos($v, 'data-tu="hoadon"') !== false && strpos($v, 'data-tu="baogia"') !== false,
   'Hop thoai co du hai nguon: hoa don cu va bao gia');
ok(strpos($v, 'warehouse_id=') !== false,
   'Form gui kem kho DANG CHON khi goi copy-lines',
   'Khong gui thi may chu bao ton cua kho khac — sai con te hon khong bao');
ok(strpos($v, 'thieu_hang') !== false, 'Giao dien co canh bao thieu ton');

/* taoBang() phải trả về xoaHet/napLai thì nút Chép mới dọn được bảng cũ */
ok(strpos($v, 'xoaHet: xoaHet') !== false && strpos($v, 'napLai: napLaiTatCa') !== false,
   'taoBang() tra ve xoaHet + napLai',
   'Thieu la nut Chep khong don duoc bang cu, dong moi bi cong vao dong cu');
ok(strpos($v, 'var GOC_ADMIN') !== false,
   'View cap duong dan goc admin cho JS',
   'JS khong tu biet duong dan goc — fetch se tro sai cho');

/* Modal tự dựng, KHÔNG dùng Bootstrap modal (jQuery nạp ở cuối trang) */
ok(strpos($v, 'data-toggle="modal"') === false,
   'Khong dung modal cua Bootstrap',
   'JS modal cua Bootstrap 4 phu thuoc jQuery nap o CUOI trang — goi som la hut');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

require_once $goc . 'app/models/SalesInvoicesModel.php';
require_once $goc . 'app/models/SalesInvoiceItemsModel.php';
require_once $goc . 'app/models/StocksModel.php';
require_once $goc . 'app/models/PartsModel.php';

$HD = new SalesInvoicesModel();
$IT = new SalesInvoiceItemsModel();
$ST = new StocksModel();
$P  = new PartsModel();

$kho = $pdo->query("SELECT id FROM warehouses ORDER BY id LIMIT 1")->fetchColumn();
ok(!empty($kho), 'Co kho de thu');

$hangHoa = $P->getForSelect(true);
if (count($hangHoa) < 2){ echo "\n[SKIP] Chua du hang hoa de thu.\n"; exit(summary()); }
$h1 = (int) $hangHoa[0]['id'];
$h2 = (int) $hangHoa[1]['id'];

/* --- tonTheoNhieuHang: mot truy van, va thieu khoa = ton 0 --- */
$map = $ST->tonTheoNhieuHang($kho, [$h1, $h2]);
ok(is_array($map), 'tonTheoNhieuHang() tra ve mang');
foreach ([$h1, $h2] as $pid){
    $mot = $ST->available($kho, $pid);
    $qua = isset($map[$pid]) ? $map[$pid] : 0.0;
    ok(abs($mot - $qua) < 0.0001,
       "Ton cua hang #$pid khop voi available() (" . $mot . ')',
       'Lay hang loat phai ra dung nhu hoi tung cai');
}
ok($ST->tonTheoNhieuHang($kho, []) === [], 'Danh sach rong thi tra ve mang rong, khong truy van');
$khongCo = $ST->tonTheoNhieuHang($kho, [999999999]);
ok(!isset($khongCo[999999999]),
   'Hang chua co dong ton nao thi KHONG co mat trong ket qua',
   'Noi goi phai hieu "khong co khoa" la ton 0, giong available()');

/* --- Hoá đơn thử + dòng hàng --- */
$soHD = $HD->nextNo();
$idHD = $HD->add([
    'invoice_no'   => $soHD,
    'customer_id'  => null,
    'customer_name'=> 'ZZ Khach thu chep',
    'warehouse_id' => $kho,
    'invoice_date' => date('Y-m-d'),
    'vat_rate'     => 8,
    // `sales_invoices`.`status` la TINYINT (0 = nhap, 1 = da ghi so), khong
    // phai chuoi nhu ben `quotations` — dat 'draft' vao day la loi 1366.
    'status'       => 0,
]);
ok($idHD > 0, 'Tao duoc hoa don thu');

$IT->syncForInvoice($idHD, [
    ['part_id' => $h1, 'quantity' => 2, 'unit_price' => 111000, 'discount_percent' => 0, 'note' => 'ghi chu 1'],
    ['part_id' => $h2, 'quantity' => 3, 'unit_price' => 222000, 'discount_percent' => 5, 'note' => ''],
]);

$dong = $IT->dongDeChep($idHD);
ok(count($dong) === 2, 'dongDeChep() tra ve du 2 dong');
ok($IT->demDong($idHD) === 2, 'demDong() dem dung 2 dong');

$d1 = null;
foreach ($dong as $d) if ((int) $d['part_id'] === $h1) $d1 = $d;
ok($d1 !== null, 'Tim thay dong cua hang #1');
ok((float) $d1['unit_price'] === 111000.0, 'Giu nguyen don gia da chot tren hoa don cu');
ok(isset($d1['gia_bay_gio']), 'Co kem gia HIEN TAI cua mat hang');
ok(isset($d1['part_code']) && isset($d1['part_name']) && isset($d1['item_type']),
   'Co kem ma / ten / loai de xep dong vao dung tab');
ok((float) $d1['gia_bay_gio'] !== 111000.0 || (float) $hangHoa[0]['price'] === 111000.0,
   'gia_bay_gio doc tu bang parts chu khong chep lai unit_price');

/* --- Nhận ra thiếu tồn --- */
$ton1  = $ST->available($kho, $h1);
$thieu = $ton1 + 1000;   // chắc chắn nhiều hơn tồn
$IT->syncForInvoice($idHD, [
    ['part_id' => $h1, 'quantity' => $thieu, 'unit_price' => 111000, 'discount_percent' => 0, 'note' => ''],
]);
$dong2 = $IT->dongDeChep($idHD);
$map2  = $ST->tonTheoNhieuHang($kho, [$h1]);
$co    = isset($map2[$h1]) ? $map2[$h1] : 0.0;
ok($co < (float) $dong2[0]['quantity'],
   'Nhan ra duoc dong doi nhieu hon ton (' . $co . ' < ' . $thieu . ')',
   'Khong nhan ra thi nguoi lap in phieu dua khach roi moi bi chan luc ghi so');

/* --- Danh sách để chép: hoá đơn của CHÍNH khách được đẩy lên đầu --- */
$khachId = $pdo->query("SELECT customer_id FROM sales_invoices
                         WHERE customer_id IS NOT NULL ORDER BY id DESC LIMIT 1")->fetchColumn();
if (!empty($khachId)){
    $ds = $HD->danhSachDeChep((int) $khachId, 50);
    ok(!empty($ds), 'danhSachDeChep() tra ve danh sach');
    $dauTien = $ds[0];
    ok((int) $dauTien['uu_tien'] === 0,
       'Hoa don cua CHINH khach dang chon nam dau danh sach',
       'De lan vao danh sach chung thi phai do giua hang tram so hoa don');
    ok(isset($dauTien['so_dong']) && isset($dauTien['khach']) && isset($dauTien['invoice_no']),
       'Moi dong co du so hieu, khach, so dong hang');
} else {
    echo "  [SKIP] Chua co hoa don nao gan khach de thu uu tien.\n";
}

$ds0 = $HD->danhSachDeChep(0, 50);
ok(!empty($ds0), 'Khong chon khach thi van ra danh sach day du');

// Dọn sạch
$pdo->prepare("DELETE FROM sales_invoice_items WHERE invoice_id = ?")->execute([$idHD]);
$pdo->prepare("DELETE FROM sales_invoices WHERE id = ?")->execute([$idHD]);
ok(empty($HD->getDetail($idHD)), 'Da don sach hoa don thu');

exit(summary());

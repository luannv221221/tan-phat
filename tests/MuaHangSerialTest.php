<?php
/**
 * MUA HÀNG + SERIAL — tài liệu khách gửi 29/10/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\MuaHangSerialTest.php
 *
 * SÁU CHỖ HỎNG SẼ ÂM THẦM, mỗi chỗ một mục bên dưới:
 *
 *   1. TẠO PHIẾU NHẬP HAI LẦN. Bấm hai lần (hoặc F5 lại trang kết quả) mà
 *      không có chốt chặn là hai phiếu nhập cho cùng một đơn — tồn kho cộng
 *      đôi, và không ai lần ra vì mỗi phiếu nhìn riêng đều hợp lệ.
 *
 *   2. GIÁ VỐN BẰNG 0. Đơn mua không có giá (đúng tài liệu). Nếu lúc chuyển
 *      sang phiếu nhập cứ để `unit_cost` = 0 thì hàng nhập đường này có giá
 *      vốn 0; bán ra là báo cáo lãi gộp bằng ĐÚNG doanh thu — một con số rất
 *      đẹp và hoàn toàn sai.
 *
 *   3. SỬA SỐ LƯỢNG LÀM BAY SERIAL. `purchase_serials`.`poi_id` có khoá ngoại
 *      CASCADE tới dòng hàng, mà syncForPo() của dòng hàng XOÁ RỒI GHI LẠI.
 *      Ghi serial trước khi ghi dòng là ghi vào khoảng không.
 *
 *   4. SERIAL TRÙNG LỌT QUA. Serial là định danh của đúng một chiếc; trùng
 *      nghĩa là gõ nhầm. Không chặn thì tới lúc tra cứu ra hai dòng mà không
 *      biết tin dòng nào — đúng lúc đang cần một câu trả lời dứt khoát.
 *
 *   5. SERIAL GARA NÀY LỌT SANG GARA KHÁC. Tra serial ra đơn mua của gara khác
 *      là lộ cả nhà cung cấp lẫn giá của họ.
 *
 *   6. MÀN SERIAL CÓ ĐƯỜNG GHI. Serial khai ở Đơn mua; sửa rời ở màn tra cứu
 *      là tách serial khỏi nguồn mua — mà nguồn mua đúng là thứ duy nhất màn
 *      đó có ích.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;
use App\core\Load;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration + noi day');

$mg = glob($goc . 'database/migrations/*_mua_hang_va_serial.php');
ok(count($mg) === 1, 'Co migration 000102 (mua_hang_va_serial)');
$src = count($mg) === 1 ? file_get_contents($mg[0]) : '';
foreach (['purchase_orders', 'purchase_order_items', 'purchase_serials'] as $b){
    ok(strpos($src, '`' . $b . '`') !== false, "Migration tao bang `$b`");
}
/* Chot nay da gap that o `orders` (migration 000099): danh so theo gara ma chi
   muc duy nhat toan he thong thi gara thu hai lap phieu la 1062. */
ok(strpos($src, 'UNIQUE KEY `uq_po_gara_no` (`garage_id`, `po_no`)') !== false,
   'So don mua duy nhat THEO GARA, khong phai toan he thong');
ok(strpos($src, 'UNIQUE KEY `uq_serial_gara` (`garage_id`, `serial`)') !== false,
   'Serial duy nhat theo gara (chot 4)');

$rt = codeOnly($goc . 'routes/web.php');
foreach (['purchase-orders', 'purchase-orders/add', 'purchase-orders/chot/(\d+)',
          'purchase-orders/tao-phieu-nhap/(\d+)', 'serials'] as $r){
    ok(strpos($rt, "'" . $r . "'") !== false, "Co route `$r`");
}
/* CHOT 6 — man tra cuu KHONG co duong ghi nao. */
foreach (['serials/add', 'serials/edit', 'serials/delete'] as $r){
    ok(strpos($rt, "'" . $r) === false, "Man serial KHONG co duong ghi `$r`");
}
/* Tao phieu nhap phai la POST: GET thi go thang dia chi vao trinh duyet la
   sinh ra mot phieu nhap kho that. */
ok(preg_match('~Route::post\(\s*.purchase-orders/tao-phieu-nhap~', $rt) === 1,
   'Tao phieu nhap la POST, khong phai GET');
ok(!preg_match('~Route::get\(\s*.purchase-orders/tao-phieu-nhap~', $rt),
   'KHONG co ban GET cua tao-phieu-nhap');

$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("~'Mua hàng'\s*=>\s*\[[^\]]*'purchase-orders'~u", $sb) === 1,
   'Nhom "Mua hang" co tren menu, chua `purchase-orders`');
ok(preg_match("~'Mua hàng'\s*=>\s*\[[^\]]*'serials'~u", $sb) === 1,
   'Nhom "Mua hang" chua `serials`');
/* Tai lieu: "nha cc (link cua doi tuong)" — cung man Doi tuong, vao tu hai loi. */
ok(preg_match("~'Mua hàng'\s*=>\s*\[[^\]]*'partners'~u", $sb) === 1,
   'Nhom "Mua hang" co loi tat sang man Doi tuong (nha cung cap)');
ok(preg_match("~'Mua hàng'\s*=>\s*'([a-z0-9-]+)'~u", $sb, $mIc) === 1, 'Nhom "Mua hang" co icon');
if (!empty($mIc[1])){
    $svg = file_get_contents($goc . 'public/assets/vendor/lucide/lucide.svg');
    ok(strpos($svg, 'id="i-' . $mIc[1] . '"') !== false,
       "Icon \"{$mIc[1]}\" co that trong sprite",
       'sidebar.php goi icon($groupIcons[...]) khong co duong lui');
}

// ---------------------------------------------------------------------------
section('Ma nguon — may chot kho thay');

$ctl = codeOnly($goc . 'app/controllers/admin/Purchaseorders.php');

/* CHOT 1 — chi tao phieu nhap MOT lan, va phai chot don truoc. */
ok(strpos($ctl, "goods_receipt_id") !== false
   && preg_match('~if \(!empty\(\$item\[.goods_receipt_id.\]\)\)~', $ctl) === 1,
   'Co chot chan tao phieu nhap lan hai');
ok(preg_match('~\(int\) \$item\[.status.\] !== 1~', $ctl) === 1,
   'Phai chot don truoc khi tao phieu nhap');
/* Tao phieu + gan nguoc id phai nam trong MOT giao dich: tao duoc phieu ma
   khong gan duoc id la lan sau bam lai ra phieu thu hai. */
ok(strpos($ctl, '->transaction(') !== false,
   'Tao phieu nhap + gan id nam trong mot giao dich');

/* CHOT 2 — giá vốn lấy giá nhập gần nhất, không để 0. */
ok(strpos($ctl, 'giaNhapGanNhat(') !== false,
   'Phieu nhap lay gia nhap gan nhat, khong de 0 (chot 2)');
$mdl = codeOnly($goc . 'app/models/PurchaseOrdersModel.php');
ok(strpos($mdl, 'function giaNhapGanNhat') !== false, 'Model co giaNhapGanNhat()');
ok(strpos($mdl, "`gr`.`status` = 1") !== false,
   'Chi lay gia tu phieu nhap DA GHI SO',
   'Phieu nhap nhap con nhap thi gia con sua duoc, khong phai can cu');

/* Phieu nhap sinh ra o trang thai NHAP — ton kho doi khi nguoi o kho ghi so. */
ok(preg_match("~'status'\s*=>\s*0~", $ctl) === 1,
   'Phieu nhap sinh ra o trang thai NHAP, khong tu ghi so');
/* Va phai mang dung loai — xem LoaiPhieuNhapTest. */
ok(strpos($ctl, "'receipt_type' => 'nhap_mua'") !== false,
   "Phieu nhap mang loai 'nhap_mua'");

/* CHOT 3 — thu tu ghi: dong hang TRUOC, serial SAU. */
if (preg_match('~function ghiDongVaSerial\(.*?\n    \}~s', $ctl, $mG)){
    $viDong   = strpos($mG[0], '__item->syncForPo');
    $viSerial = strpos($mG[0], '__serial->syncForPo');
    ok($viDong !== false && $viSerial !== false && $viDong < $viSerial,
       'Ghi dong hang TRUOC, serial SAU (chot 3)',
       'poi_id co CASCADE: xoa dong la serial bay theo, ghi serial truoc la ghi vao khoang khong');
}

/* CHOT 4 — tra ve danh sach serial bi tu choi, khong nem loi lam mat ca don. */
$snm = codeOnly($goc . 'app/models/PurchaseSerialsModel.php');
ok(strpos($snm, '$tuChoi') !== false,
   'Serial trung thi TRA VE danh sach bi tu choi, khong nem loi',
   'Nem loi thi ca don khong luu duoc, nguoi dung mat het nhung gi vua go');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

const DAU = 'ZZMH';
$donDep = function() use ($pdo){
    $pdo->exec("DELETE FROM `purchase_orders` WHERE `po_no` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `partners` WHERE `code` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `garages`  WHERE `code` LIKE '" . DAU . "%'");
};
$donDep();

$pdo->exec("INSERT INTO `garages` (`code`,`name`,`is_master`,`status`,`create_at`)
            VALUES ('" . DAU . "A','" . DAU . " Gara A',0,1,NOW()),
                   ('" . DAU . "B','" . DAU . " Gara B',0,1,NOW())");
$gA = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "A'")->fetchColumn();
$gB = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "B'")->fetchColumn();
ok($gA > 0 && $gB > 0, "Tao duoc hai gara thu (#$gA, #$gB)");

$st = $pdo->prepare("INSERT INTO `partners` (`code`,`name`,`type`,`status`,`garage_id`,`create_at`)
                     VALUES (?,?, 'supplier',1,?,NOW())");
$st->execute([DAU . 'NA', DAU . ' NCC cua A', $gA]);
$nccA = (int) $pdo->lastInsertId();
$st->execute([DAU . 'NB', DAU . ' NCC cua B', $gB]);
$nccB = (int) $pdo->lastInsertId();

$part = (int) $pdo->query("SELECT `id` FROM `parts` WHERE `status` = 1 LIMIT 1")->fetchColumn();

/** Lập một đơn mua đầy đủ dưới danh nghĩa một gara */
$lapDon = function($gara, $ncc, $so, array $serials) use ($part){
    return Model::trongGara($gara, function() use ($gara, $ncc, $so, $serials, $part){
        $po = Load::model('PurchaseOrdersModel');
        $it = Load::model('PurchaseOrderItemsModel');
        $sn = Load::model('PurchaseSerialsModel');

        $id   = $po->add(['po_no' => $so, 'partner_id' => $ncc, 'partner_name' => 'NCC',
                          'order_date' => date('Y-m-d'), 'status' => 0]);
        $dong = $it->syncForPo($id, [['part_id' => $part, 'quantity' => count($serials)]]);
        $poi  = array_key_first($dong);

        $sn3 = [];
        foreach ($serials as $s) $sn3[] = ['poi_id' => $poi, 'part_id' => $part, 'serial' => $s];
        $tuChoi = $sn->syncForPo($id, $sn3);

        return ['id' => $id, 'poi' => $poi, 'tuChoi' => $tuChoi];
    });
};

// ---------------------------------------------------------------------------
section('Serial trung bi chan (chot 4)');

$dA = $lapDon($gA, $nccA, DAU . '-A1', [DAU . 'SN-1', DAU . 'SN-2', DAU . 'SN-1']);
ok(count($dA['tuChoi']) === 1,
   'Trung ngay trong don: bi tu choi 1 serial (' . count($dA['tuChoi']) . ')');
ok(!empty($dA['tuChoi'][0]['ly_do']) && mb_strpos($dA['tuChoi'][0]['ly_do'], 'trong đơn này') !== false,
   'Ly do noi ro la trung TRONG CHINH DON NAY',
   'Dang bao: ' . (isset($dA['tuChoi'][0]['ly_do']) ? $dA['tuChoi'][0]['ly_do'] : '?'));

$con = (int) $pdo->query("SELECT COUNT(*) FROM `purchase_serials` WHERE `po_id` = " . (int) $dA['id'])->fetchColumn();
ok($con === 2, "Hai serial con lai VAN luu binh thuong ($con dong)",
   'Nem loi thi ca don mat trang');

/* Trung voi don KHAC cua CUNG gara -> bao duoc ten don kia. */
$dA2 = $lapDon($gA, $nccA, DAU . '-A2', [DAU . 'SN-1']);
ok(count($dA2['tuChoi']) === 1 && mb_strpos($dA2['tuChoi'][0]['ly_do'], DAU . '-A1') !== false,
   'Trung voi don khac: bao ro TEN DON da co serial do',
   'Dang bao: ' . (isset($dA2['tuChoi'][0]['ly_do']) ? $dA2['tuChoi'][0]['ly_do'] : '?'));

// ---------------------------------------------------------------------------
section('Hai gara dung chung mot so serial van duoc (chot 5)');

$dB = $lapDon($gB, $nccB, DAU . '-B1', [DAU . 'SN-1']);
ok(empty($dB['tuChoi']),
   'Gara B dung LAI serial ' . DAU . 'SN-1 cua gara A: duoc',
   'Hai gara mua hang cua hai noi, so trung nhau la chuyen cua ho');

/* VA KHONG THAY CUA NHAU. */
$traA = Model::trongGara($gA, function(){ return Load::model('PurchaseSerialsModel')->traCuu(DAU . 'SN-1'); });
$traB = Model::trongGara($gB, function(){ return Load::model('PurchaseSerialsModel')->traCuu(DAU . 'SN-1'); });
ok(count($traA) === 1 && count($traB) === 1,
   'Moi gara tra ra DUNG MOT dong cua chinh minh (A=' . count($traA) . ', B=' . count($traB) . ')');
ok(!empty($traA[0]['po_no']) && $traA[0]['po_no'] === DAU . '-A1',
   'Gara A tra ra don cua gara A (' . (isset($traA[0]['po_no']) ? $traA[0]['po_no'] : '?') . ')');
ok(!empty($traB[0]['po_no']) && $traB[0]['po_no'] === DAU . '-B1',
   'Gara B tra ra don cua gara B (' . (isset($traB[0]['po_no']) ? $traB[0]['po_no'] : '?') . ')',
   'Tra ra don cua gara khac la lo ca nha cung cap lan gia cua ho');

// ---------------------------------------------------------------------------
section('Tra cuu — khop dung day len dau');

$traGan = Model::trongGara($gA, function(){ return Load::model('PurchaseSerialsModel')->traCuu(DAU . 'SN'); });
ok(count($traGan) >= 2, 'Go mot phan serial van tim duoc (' . count($traGan) . ' ket qua)');

$traDung = Model::trongGara($gA, function(){ return Load::model('PurchaseSerialsModel')->traCuu(DAU . 'SN-2'); });
ok(!empty($traDung) && (int) $traDung[0]['khop_dung'] === 1,
   'Go nguyen chuoi: dong khop DUNG nam dau tien',
   'Nguoi o quay go ca serial tren tem thi can mot cau tra loi dut khoat');
ok(!empty($traDung[0]['part_name']) && !empty($traDung[0]['order_date']),
   'Ket qua co du: hang gi, mua cua ai, don nao, ngay nao');

/* Tra chuoi khong co -> khong duoc vo, tra ve rong. */
$traTrong = Model::trongGara($gA, function(){ return Load::model('PurchaseSerialsModel')->traCuu('KHONG-CO-SERIAL-NAY'); });
ok($traTrong === [] || count($traTrong) === 0, 'Tra chuoi khong co: tra ve rong, khong vo');

// ---------------------------------------------------------------------------
section('Sua so luong KHONG lam bay serial (chot 3)');

/* Lap lai dung thao tac nguoi dung lam: sua don (ghi lai dong hang) roi ghi
   serial. Dung thu tu thi serial con; nguoc thu tu thi mat sach. */
$giuDuoc = Model::trongGara($gA, function() use ($dA, $part){
    $it = Load::model('PurchaseOrderItemsModel');
    $sn = Load::model('PurchaseSerialsModel');
    $dong = $it->syncForPo($dA['id'], [['part_id' => $part, 'quantity' => 4]]);
    $poi  = array_key_first($dong);
    $sn->syncForPo($dA['id'], [
        ['poi_id' => $poi, 'part_id' => $part, 'serial' => DAU . 'SN-1'],
        ['poi_id' => $poi, 'part_id' => $part, 'serial' => DAU . 'SN-2'],
    ]);
    return count($sn->getByPo($dA['id']));
});
ok($giuDuoc === 2, "Sua so luong xong serial van con ($giuDuoc dong)");

// ---------------------------------------------------------------------------
section('Gia nhap gan nhat, khong phai 0 (chot 2)');

$gia = Model::trongGara($gA, function() use ($part){
    return Load::model('PurchaseOrdersModel')->giaNhapGanNhat($part);
});
ok($gia > 0, 'giaNhapGanNhat() tra ve so > 0 (' . number_format($gia) . ' d)',
   'De 0 thi hang nhap duong nay co gia von 0, ban ra la lai gop thoi len bang ca doanh thu');

$donDep();
exit(summary());

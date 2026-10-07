<?php
/**
 * Test ĐẶT HÀNG NỘI BỘ — gara mua hàng từ kho tổng (bước 6 nền tảng nhiều gara).
 *
 * Chạy:  C:\xampp\php\php.exe tests\DatHangNoiBoTest.php
 *
 * Một lần đặt sinh ĐÚNG HAI phiếu đã có sẵn trong hệ thống, trong cùng một
 * giao dịch:
 *     kho tổng -> phiếu XUẤT kho
 *     gara     -> phiếu NHẬP kho
 * Cả hai ở trạng thái NHÁP. Kho tổng ghi sổ phiếu xuất chính là bước duyệt.
 *
 * HAI CHỖ HỎNG ĐÃ MẮC THẬT khi làm, và là lý do có test này:
 *
 *   1. DÙNG Model::epGara() ĐỂ MƯỢN DANH NGHĨA GARA KHÁC. epGara() chỉ có tác
 *      dụng ở DÒNG LỆNH — garaLoc() trên web luôn đọc gara của phiên đăng nhập
 *      và bỏ qua nó. Chạy thử dòng lệnh thì hai phiếu vào đúng hai gara, nhưng
 *      qua web thì PHIẾU XUẤT RƠI VÀO GARA CỦA NGƯỜI ĐANG BẤM — gara tự lập
 *      phiếu xuất trong kho của chính mình. Nay có Model::trongGara().
 *
 *   2. HỎI TỒN TỪ PHÍA GARA MUA. StocksModel từ chối mọi kho không thuộc gara
 *      đang làm việc, nên kho của kho tổng là "kho lạ" và lúc nào cũng trả 0 —
 *      món nào cũng báo hết hàng.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/DatHangNoiBoModel.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Man hinh duoc noi vao he thong');

$routes = file_get_contents($goc . 'routes/web.php');
ok(strpos($routes, "'dat-hang-kho-tong'") !== false, 'Co route dat-hang-kho-tong');
ok(strpos($routes, "'dat-hang-kho-tong/dat'") !== false, 'Co route POST dat hang');
ok(is_file($goc . 'app/controllers/admin/Dathangkhotong.php'), 'Co controller');
ok(is_file($goc . 'app/views/admin/dat-hang-kho-tong/index.php'), 'Co view');

/* Làm xong một màn mà không nối vào menu thì coi như chưa làm. */
$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(strpos($sb, "'dat-hang-kho-tong'") !== false, 'Co duong vao tu menu trai');

// ---------------------------------------------------------------------------
section('Model::trongGara — muon danh nghia gara khac roi TRA LAI');

/* Đây là chốt chặn cho chỗ hỏng số 1. Mượn mà quên trả là mọi truy vấn sau đó
   trong cùng request đều chạy dưới gara sai. */
Model::epGara(null);
$truoc = Model::garaLoc();
$trong = Model::trongGara(99, function(){ return Model::garaLoc(); });
ok($trong === 99, 'Trong khoi: garaLoc() tra ve gara dang muon');
ok(Model::garaLoc() === $truoc, 'Ra khoi khoi: tra lai gara cu');

$nem = false;
try {
    Model::trongGara(99, function(){ throw new \RuntimeException('thu'); });
} catch (\Throwable $e){ $nem = true; }
ok($nem && Model::garaLoc() === $truoc,
   'Khoi nem loi thi VAN tra lai gara cu',
   'Quen tra la moi truy van sau do chay duoi gara sai');

$long = Model::trongGara(7, function(){
    return Model::trongGara(8, function(){ return Model::garaLoc(); }) . '/' . Model::garaLoc();
});
ok($long === '8/7', 'Long nhau van dung', $long);

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM goods_issue_items WHERE issue_id IN (SELECT id FROM goods_issues WHERE reason LIKE '%ZZNB%')");
    $pdo->exec("DELETE FROM goods_issues WHERE reason LIKE '%ZZNB%'");
    $pdo->exec("DELETE FROM goods_receipt_items WHERE receipt_id IN (SELECT id FROM goods_receipts WHERE reason LIKE '%ZZNB%')");
    $pdo->exec("DELETE FROM goods_receipts WHERE reason LIKE '%ZZNB%'");
};
$donSach();
register_shutdown_function(function() use ($donSach){ Model::epGara(null); $donSach(); });

// ---------------------------------------------------------------------------
section('Man hinh da khai + phan quyen');

$m = $pdo->query("SELECT id, chi_tan_phat FROM modules WHERE link = 'dat-hang-kho-tong'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($m), 'Module da khai trong bang modules', 'Chay: php migrate.php');
if (!empty($m)){
    /* KHÔNG mang cờ chi_tan_phat: đây là màn của GARA. Kho tổng mở ra sẽ thấy
       câu "không đặt hàng của chính mình" — controller chặn, vì không có cờ nào
       diễn đạt được "chỉ gara, trừ kho tổng". */
    ok((int) $m['chi_tan_phat'] === 0, 'KHONG mang co chi_tan_phat (day la man cua gara)');
    $q = $pdo->query("SELECT g.name, p.role FROM permissions p JOIN `groups` g ON g.id = p.group_id
                      WHERE p.module_id = " . (int) $m['id'])->fetchAll(PDO::FETCH_ASSOC);
    $theo = [];
    foreach ($q as $r) $theo[$r['name']][] = $r['role'];
    foreach (['Admin', 'Manager'] as $n){
        ok(isset($theo[$n]) && in_array('add', $theo[$n], true), "$n duoc DAT hang");
    }
    /* Đặt hàng là cam kết mua, sinh công nợ với công ty — để cấp quản lý bấm. */
    ok(isset($theo['Staff']) && !in_array('add', $theo['Staff'], true),
       'Staff chi XEM, khong dat duoc');
}

// ---------------------------------------------------------------------------
section('Dat hang: sinh dung hai phieu, dung hai gara');

$gTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1")->fetchColumn();
$gPhu  = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 0 AND status = 1 ORDER BY id LIMIT 1")->fetchColumn();
$khoTong = (int) $pdo->query("SELECT id FROM warehouses WHERE garage_id = $gTong ORDER BY is_default DESC, id LIMIT 1")->fetchColumn();

$hang = $pdo->query("SELECT s.part_id, s.quantity FROM stocks s JOIN parts p ON p.id = s.part_id
                     WHERE s.warehouse_id = $khoTong AND s.quantity >= 5 AND p.garage_id IS NULL
                     LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
if (count($hang) < 2){
    echo "  [SKIP] Kho tong khong du hai mat hang co ton de thu.\n";
    $donSach(); exit(summary());
}

$M = new DatHangNoiBoModel();

Model::epGara($gPhu);
$kq = $M->dat([
    ['part_id' => (int) $hang[0]['part_id'], 'quantity' => 2],
    ['part_id' => (int) $hang[1]['part_id'], 'quantity' => 3],
], 'ZZNB thu');
Model::epGara(null);

ok(!empty($kq['pxk']['id']) && !empty($kq['pnk']['id']), 'Sinh ra ca phieu xuat lan phieu nhap');

$px = $pdo->query("SELECT * FROM goods_issues WHERE id = " . (int) $kq['pxk']['id'])->fetch(PDO::FETCH_ASSOC);
$pn = $pdo->query("SELECT * FROM goods_receipts WHERE id = " . (int) $kq['pnk']['id'])->fetch(PDO::FETCH_ASSOC);

ok((int) $px['garage_id'] === $gTong,
   'Phieu XUAT thuoc KHO TONG',
   'Day la cho da hong that qua web: phieu xuat roi vao gara cua nguoi dang bam');
ok((int) $pn['garage_id'] === $gPhu, 'Phieu NHAP thuoc GARA dat hang');
ok((int) $px['warehouse_id'] === $khoTong, 'Phieu xuat di tu kho cua kho tong');
ok((int) $pn['warehouse_id'] !== $khoTong, 'Phieu nhap vao kho cua gara, khong phai kho tong');

/* Nháp cả hai — ghi sổ là việc của từng bên, và ghi sổ phiếu xuất chính là duyệt. */
ok((int) $px['status'] === 0 && (int) $pn['status'] === 0,
   'CA HAI phieu o trang thai NHAP, khong tu ghi so',
   'Tu ghi so la gara bam mot nut ma tru thang ton cua kho tong — bo qua khau duyet');

ok(strpos($px['reason'], $pn['receipt_no']) !== false, 'Phieu xuat ghi so phieu nhap');
ok(strpos($pn['reason'], $px['issue_no']) !== false, 'Phieu nhap ghi so phieu xuat');

$slPx = (int) $pdo->query("SELECT COUNT(*) FROM goods_issue_items WHERE issue_id = " . (int) $px['id'])->fetchColumn();
$slPn = (int) $pdo->query("SELECT COUNT(*) FROM goods_receipt_items WHERE receipt_id = " . (int) $pn['id'])->fetchColumn();
ok($slPx === 2 && $slPn === 2, 'Hai phieu deu co du 2 dong hang', "xuat $slPx / nhap $slPn");

/* Phiếu xuất ghi GIÁ VỐN, mà giá vốn bình quân chỉ biết lúc ghi sổ. */
ok((float) $px['total_amount'] == 0.0,
   'Phieu xuat de tong tien 0 — gia von dien luc ghi so');
ok((float) $pn['total_amount'] > 0,
   'Phieu nhap co tong tien theo gia cong ty', $pn['total_amount']);

// ---------------------------------------------------------------------------
section('Doi tuong hai dau tu dung len');

$ncc = $pdo->query("SELECT * FROM partners WHERE code = 'NB-KHOTONG' AND garage_id = $gPhu")->fetch(PDO::FETCH_ASSOC);
ok(!empty($ncc) && $ncc['type'] === 'supplier', 'Kho tong la NHA CUNG CAP trong danh sach cua gara');
$kh = $pdo->query("SELECT * FROM partners WHERE code LIKE 'NB-%' AND garage_id = $gTong AND type = 'customer'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($kh), 'Gara la KHACH HANG trong danh sach cua kho tong');

// ---------------------------------------------------------------------------
section('Chan nhung truong hop khong hop le');

$loi = function(callable $viec){
    try { $viec(); return ''; } catch (\Throwable $e){ return $e->getMessage(); }
};

Model::epGara($gTong);
$e = $loi(function() use ($M, $hang){ $M->dat([['part_id' => (int) $hang[0]['part_id'], 'quantity' => 1]]); });
ok(mb_stripos($e, 'chính mình') !== false, 'Kho tong KHONG dat hang cua chinh minh', $e);

Model::epGara($gPhu);
$e = $loi(function() use ($M){ $M->dat([]); });
ok(mb_stripos($e, 'Chưa chọn') !== false, 'Khong chon mon nao -> bao ro', $e);

$e = $loi(function() use ($M, $hang){
    $M->dat([['part_id' => (int) $hang[0]['part_id'], 'quantity' => 999999]]);
});
ok(mb_stripos($e, 'không đủ hàng') !== false, 'Kho tong khong du ton -> chan ngay luc dat', $e);

/* Chỉ đặt được hàng CỦA KHO TỔNG — hàng riêng của một gara không phải thứ
   công ty bán ra. */
$hangRieng = (int) $pdo->query("SELECT id FROM parts WHERE garage_id IS NOT NULL LIMIT 1")->fetchColumn();
if ($hangRieng > 0){
    $e = $loi(function() use ($M, $hangRieng){ $M->dat([['part_id' => $hangRieng, 'quantity' => 1]]); });
    ok(mb_stripos($e, 'kho tổng') !== false, 'Chi dat duoc hang cua kho tong', $e);
}
Model::epGara(null);

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM goods_issues WHERE reason LIKE '%ZZNB%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

<?php
/**
 * Test NHIỀU GARA — tầng 3: chọn nguồn danh mục khi lập báo giá.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NguonBaoGiaTest.php
 *
 * BỐN CHỖ HỎNG SẼ ÂM THẦM — mỗi chỗ một khẳng định riêng:
 *
 *   1. Đổi nguồn làm MẤT dòng đang chọn.
 *      Mặt hàng riêng của gara không có trong kho tổng. Nạp lại ô chọn mà bỏ
 *      nó đi thì ô hoá trống, bấm Lưu một cái là bay dòng — không lỗi nào báo.
 *
 *   2. Sửa phiếu của chi nhánh khác thì mất hàng riêng của họ.
 *      Màn Sửa dựng ô chọn từ danh mục; lấy danh mục của NGƯỜI ĐANG MỞ thay vì
 *      của gara đã lập phiếu là đúng lỗi trên, chỉ khác đường đi.
 *
 *   3. Sửa phiếu làm phiếu ĐỔI CHỦ.
 *      `garage_id` chỉ được ghi lúc LẬP. Nhét vào headerData() (dùng chung cho
 *      cả sửa) thì người chi nhánh khác sửa một chữ là phiếu sang tên.
 *
 *   4. Giá khuyến mãi của kho tổng đè giá riêng của gara.
 *      Cả hệ thống dùng quy ước "có sale_price thì lấy sale_price". Gara đặt
 *      555.000 mà mặt hàng đang khuyến mãi 1.380.000 ở kho tổng thì form vẫn
 *      hiện 1.380.000. Đã xảy ra thật khi làm tầng này.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Controller cap hai nguon cho form');

$ctl = codeOnly($goc . 'app/controllers/admin/Quotations.php');

ok(strpos($ctl, "\$c['partsTong']") !== false && strpos($ctl, "\$c['partsGara']") !== false,
   'formData() cap CA HAI nguon danh muc');
ok(strpos($ctl, 'hopNhatHang') !== false,
   '`parts` la HOP cua hai nguon',
   'Chi lay danh muc tong thi man Sua mat hang rieng cua gara');

/* edit() phải truyền gara CỦA PHIẾU vào formData() */
$thanEdit = '';
if (preg_match('~public function edit\(\$id\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $thanEdit = $m[1];
ok($thanEdit !== '', 'Doc duoc than ham edit()');
ok(strpos($thanEdit, "\$item['garage_id']") !== false && strpos($thanEdit, 'formData(') !== false,
   'edit() lay danh muc cua gara DA LAP phieu',
   'Lay cua nguoi dang mo thi phieu chi nhanh khac mat hang rieng');

/* postAdd ghi garage_id; headerData() thì KHÔNG — nếu không, sửa là đổi chủ */
$thanAdd = '';
if (preg_match('~public function postAdd\(\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $thanAdd = $m[1];
ok(strpos($thanAdd, 'gara_hien_tai_id()') !== false,
   'postAdd() ghi garage_id cho phieu moi');

$thanHeader = '';
if (preg_match('~private function headerData\(\$f\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $thanHeader = $m[1];
ok($thanHeader !== '', 'Doc duoc than ham headerData()');
ok(strpos($thanHeader, 'garage_id') === false,
   'headerData() KHONG ghi garage_id',
   'headerData() dung chung cho ca sua — nhet vao day thi sua mot chu la phieu doi chu');

// ---------------------------------------------------------------------------
section('Giao dien chon nguon');

$v = file_get_contents($goc . 'app/views/admin/quotations/add.php');

ok(strpos($v, 'id="chon-nguon"') !== false, 'Co o chon nguon tren form');
ok(strpos($v, 'data-nguon="gara"') !== false && strpos($v, 'data-nguon="tong"') !== false,
   'Co du hai nut: gara hien tai va kho tong');
ok(strpos($v, 'if (!empty($partsGara))') !== false,
   'An o chon nguon khi gara chua dung danh muc rieng',
   'Mot nut chon giua kho tong va mot danh sach rong chi lam nguoi dung boi roi');
ok(strpos($v, 'var NGUON') !== false && strpos($v, 'var TEN_HANG') !== false,
   'JS co du lieu ca hai nguon + ten moi mat hang');
ok(strpos($v, 'function doiNguon') !== false, 'taoBang() co ham doiNguon()');

/* Đổi nguồn chỉ nạp lại danh sách, KHÔNG dựng lại bảng — dựng lại là mất hết
   số lượng, chiết khấu, ghi chú người dùng đã gõ. */
$thanDoi = '';
if (preg_match('~function doiNguon\([^)]*\)\{(.*?)\}~s', $v, $m)) $thanDoi = $m[1];
ok($thanDoi !== '' && strpos($thanDoi, 'napLaiTatCa') !== false,
   'doiNguon() chi nap lai danh sach goi y');
ok($thanDoi !== '' && strpos($thanDoi, 'xoaHet') === false && strpos($thanDoi, 'innerHTML') === false,
   'doiNguon() KHONG dung sach bang',
   'Dung lai bang la mat het so luong, chiet khau, ghi chu nguoi dung da go');

/* Giữ mặt hàng lạc nguồn */
ok(strpos($v, 'ngoài nguồn đang chọn') !== false,
   'Mat hang khong thuoc nguon moi van duoc giu, co gan nhan',
   'Bo di thi o chon hoa trong va bam Luu la mat dong');
ok(strpos($v, 'thayDangChon') !== false,
   'Co co danh dau da tim thay mat hang dang chon trong nguon moi');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}
$cot = $pdo->query("SHOW COLUMNS FROM `quotations`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('garage_id', $cot, true)){
    echo "\n[SKIP] Chua chay migration them garage_id cho quotations.\n"; exit(summary());
}
ok(true, 'Bang `quotations` co cot garage_id');

require_once $goc . 'app/models/PartsModel.php';
require_once $goc . 'app/models/GaragePricesModel.php';
require_once $goc . 'app/models/GaragesModel.php';

$P = new PartsModel(); $G = new GaragePricesModel(); $GA = new GaragesModel();

$gid = $GA->add(['code' => 'ZZQ1', 'name' => 'Gara bao gia', 'is_master' => 0, 'status' => 1, 'sort_order' => 95]);

$tong = $P->theoNguon(PartsModel::NGUON_TONG);
ok(count($tong) > 0, 'Kho tong co hang');

// Gara chọn 1 mặt hàng + có 1 hàng riêng
$G->datGia($gid, $tong[0]['id'], '444000');
$idRieng = $P->add([
    'code' => 'ZZQ1-0001', 'name' => 'ZZ Dich vu rieng bao gia',
    'slug' => $P->slugTrong('ZZ Dich vu rieng bao gia'),
    'item_type' => 'service', 'price' => 333000, 'garage_id' => $gid,
    'status' => 1, 'show_on_web' => 0,
]);

$dsGara = $P->theoNguon(PartsModel::NGUON_GARA, $gid);
$dsTong = $P->theoNguon(PartsModel::NGUON_TONG);

$co = function($ds, $id){
    foreach ($ds as $r) if ((int) $r['id'] === (int) $id) return $r;
    return null;
};

ok($co($dsGara, $idRieng) !== null, 'Hang rieng CO trong nguon "Gara hien tai"');
ok($co($dsTong, $idRieng) === null, 'Hang rieng KHONG co trong nguon "Kho tong"',
   'Day chinh la truong hop "lac nguon" ma giao dien phai giu lai');

/* HỢP hai nguồn — đây là thứ màn Sửa dùng để dựng ô chọn */
$hop = [];
foreach ($dsTong as $r) $hop[(int) $r['id']] = $r;
foreach ($dsGara as $r) $hop[(int) $r['id']] = $r;
ok(isset($hop[(int) $idRieng]), 'HOP hai nguon CO hang rieng',
   'Man Sua dung `parts` de dung o chon — thieu la mo phieu ra mat dong');
ok(count($hop) === count($dsTong) + 1,
   'HOP khong nhan doi mat hang co o ca hai nguon',
   'Dang co ' . count($hop) . ', ky vong ' . (count($dsTong) + 1));

/* Bản của gara phải THẮNG trong hợp — nó mang giá đã áp bảng giá riêng */
$apDung = function($r){ return (int) (!empty($r['sale_price']) ? $r['sale_price'] : $r['price']); };
$idChung = (int) $tong[0]['id'];
ok($apDung($hop[$idChung]) === 444000,
   'Trong HOP, ban cua GARA thang (mang gia rieng)',
   'Dang la ' . number_format($apDung($hop[$idChung])) . ' — ban cua kho tong da de len');

/* Nguồn mặc định của form: có danh mục gara thì dùng nó */
$nguonMacDinh = !empty($dsGara) ? 'gara' : 'tong';
ok($nguonMacDinh === 'gara', 'Gara da co danh muc thi form mo len o nguon gara');

$gidTrong = $GA->add(['code' => 'ZZQ2', 'name' => 'Gara chua cau hinh', 'is_master' => 0, 'status' => 1, 'sort_order' => 96]);
ok(empty($P->theoNguon(PartsModel::NGUON_GARA, $gidTrong)),
   'Gara chua cau hinh thi form phai roi ve kho tong',
   'Nguon gara rong ma van chon no thi o chon hang trong tron');

// Dọn sạch
$pdo->prepare("DELETE FROM garage_part_prices WHERE garage_id IN (?, ?)")->execute([$gid, $gidTrong]);
$pdo->prepare("DELETE FROM parts WHERE garage_id IN (?, ?)")->execute([$gid, $gidTrong]);
$GA->remove($gid); $GA->remove($gidTrong);
ok(empty($GA->getDetail($gid)) && empty($GA->getDetail($gidTrong)), 'Da don sach du lieu test');

exit(summary());

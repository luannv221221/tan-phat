<?php
/**
 * Test NHIỀU GARA — tầng 2: danh mục riêng của gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DanhMucGaraTest.php
 *
 * BỐN CHỖ HỎNG SẼ ÂM THẦM, KHÔNG BÁO LỖI — nên mỗi chỗ có khẳng định riêng:
 *
 *   1. "Gara chưa chọn gì thì cho xem tạm hàng tổng cho tiện."
 *      Làm vậy thì hai nguồn giống hệt nhau, nút chọn nguồn thành vô nghĩa, và
 *      người dùng tưởng gara đã có bảng giá riêng. Nguồn "Gara hiện tại" của
 *      một gara chưa cấu hình PHẢI rỗng.
 *
 *   2. Hàng riêng của gara A lọt sang gara B — hoặc lên website chung.
 *      Không có lỗi nào bật ra; chỉ là chi nhánh khác bán thứ mình không có.
 *
 *   3. Giá bỏ trống bị hiểu thành 0.
 *      Bỏ trống nghĩa là "theo giá tổng", còn 0 là một mức giá thật (kiểm tra
 *      miễn phí). Lẫn hai thứ thì cả danh mục âm thầm về 0 đồng.
 *
 *   4. Tên biến của controller đụng tên biến View::share() dùng chung.
 *      Đã dính đúng lỗi này khi làm: controller đặt `dsGara` cho danh mục, mà
 *      header cũng dùng `dsGara` cho ô đổi gara. Dữ liệu chia sẻ ĐÈ dữ liệu
 *      controller, nên bảng danh mục in ra danh sách gara.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg  = glob($goc . 'database/migrations/*_danh_muc_rieng_cua_gara.php');
ok(!empty($mg), 'Co migration danh muc rieng cua gara');
$src = !empty($mg) ? file_get_contents($mg[0]) : '';

ok(strpos($src, "ALTER TABLE `parts` ADD COLUMN `garage_id`") !== false,
   'Them cot `parts`.`garage_id`');
ok(strpos($src, 'CREATE TABLE IF NOT EXISTS `garage_part_prices`') !== false,
   'Tao bang `garage_part_prices`');
ok(strpos($src, 'UNIQUE KEY `uq_gpp` (`garage_id`, `part_id`)') !== false,
   'Mot gara chi co MOT gia cho mot mat hang',
   'Thieu UNIQUE thi tick hai lan la hai dong, gia nao thang tuy thu tu truy van');
ok(strpos($src, 'ON DELETE RESTRICT') !== false,
   '`parts`.`garage_id` dat RESTRICT',
   'SET NULL thi hang rieng cua mot gara nhay vao danh muc tong; CASCADE thi bao gia cu tro vao khoang khong');

// ---------------------------------------------------------------------------
section('Man hinh duoc noi vao he thong');

$routes = file_get_contents($goc . 'routes/web.php');
foreach (['garage-catalog', 'garage-catalog/chon', 'garage-catalog/them-rieng',
          'garage-catalog/xoa-rieng/(\d+)'] as $r){
    ok(strpos($routes, "'" . $r . "'") !== false, "Co route $r");
}
$sidebar = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("~'Hàng hoá'\s*=>\s*\[[^\]]*'garage-catalog'~u", $sidebar) === 1,
   'Menu trai co muc Danh muc cua gara');
ok(is_file($goc . 'app/controllers/admin/Garagecatalog.php'), 'Co controller');
ok(is_file($goc . 'app/models/GaragePricesModel.php'), 'Co GaragePricesModel');
ok(is_file($goc . 'app/views/admin/garagecatalog/lists.php'), 'Co view');

// ---------------------------------------------------------------------------
section('Chot chan trong controller');

$ctl = codeOnly($goc . 'app/controllers/admin/Garagecatalog.php');

/* Soi riêng thân xoaRieng(): phải so garage_id của mặt hàng với gara đang chọn.
   Thiếu là sửa id trên thanh địa chỉ thì xoá được hàng của chi nhánh khác — hoặc
   xoá luôn một mặt hàng của danh mục tổng mà mọi gara đang dùng. */
$than = '';
if (preg_match('~public function xoaRieng\([^)]*\)\s*\{(.*?)\n    \}~s', $ctl, $m)) $than = $m[1];
ok($than !== '', 'Doc duoc than ham xoaRieng()');
ok(strpos($than, "\$item['garage_id']") !== false && strpos($than, "\$gara['id']") !== false,
   'xoaRieng() so chu so huu truoc khi xoa',
   'Thieu la sua id tren thanh dia chi thi xoa duoc hang cua chi nhanh khac');
ok(strpos($than, "empty(\$item['garage_id'])") !== false,
   'xoaRieng() chan ca truong hop mat hang thuoc DANH MUC TONG',
   'Hang tong co garage_id NULL — so bang == voi id gara se lot');

ok(strpos($ctl, "'show_on_web' => 0") !== false,
   'Hang rieng mac dinh KHONG len website chung');

/* Bảng tick phải gửi kèm danh sách mọi dòng đang hiển thị. Thiếu thì không
   phân biệt được "bỏ tick" với "dòng không có trên trang". */
ok(strpos($ctl, "co_mat") !== false,
   'Form chon gui kem danh sach dong dang hien thi (co_mat)',
   'Thieu thi thao tac bo tick khong bao gio co tac dung');

// ---------------------------------------------------------------------------
section('Ten bien khong dung do voi View::share()');

/* AppServiceProvider chia sẻ một số biến cho MỌI màn hình admin, và dữ liệu
   chia sẻ ĐÈ dữ liệu của controller. Controller nào đặt trùng tên là hỏng
   trong im lặng — bảng in ra dữ liệu của thứ khác. */
$provider = codeOnly($goc . 'app/providers/AppServiceProvider.php');
preg_match_all("~\\\$dataShare\['content'\]\['([a-zA-Z0-9_]+)'\]~", $provider, $mp);
$dungChung = array_unique($mp[1]);
ok(count($dungChung) >= 3, 'Doc duoc danh sach bien dung chung (' . implode(', ', $dungChung) . ')');

$dung = [];
foreach (glob($goc . 'app/controllers/admin/*.php') as $f){
    $code = codeOnly($f);
    foreach ($dungChung as $ten){
        if (preg_match("~\\\$c\['" . $ten . "'\]\s*=|\\\$this->__data\['content'\]\['" . $ten . "'\]\s*=~", $code)){
            $dung[] = basename($f) . ' -> ' . $ten;
        }
    }
}
ok(empty($dung), 'Khong controller nao dat trung ten bien dung chung',
   implode(' | ', $dung));

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}
if (empty($pdo->query("SHOW TABLES LIKE 'garage_part_prices'")->fetchAll())){
    echo "\n[SKIP] Chua chay migration danh muc gara.\n"; exit(summary());
}

require_once $goc . 'app/models/PartsModel.php';
require_once $goc . 'app/models/GaragePricesModel.php';
require_once $goc . 'app/models/GaragesModel.php';

$P  = new PartsModel();
$G  = new GaragePricesModel();
$GA = new GaragesModel();

$master = $GA->getMaster();
ok(!empty($master), 'Co gara tong de thu');

// Hai gara thử
$gA = $GA->add(['code' => 'ZZTA', 'name' => 'Gara thu A', 'is_master' => 0, 'status' => 1, 'sort_order' => 90]);
$gB = $GA->add(['code' => 'ZZTB', 'name' => 'Gara thu B', 'is_master' => 0, 'status' => 1, 'sort_order' => 91]);

$tong = $P->theoNguon(PartsModel::NGUON_TONG);
ok(count($tong) > 0, 'Danh muc tong co hang (' . count($tong) . ' mat hang)');

/* --- 1. Gara chưa chọn gì -> RỖNG, không phải cả danh mục tổng --- */
ok(count($P->theoNguon(PartsModel::NGUON_GARA, $gA)) === 0,
   'Gara chua chon gi thi nguon "Gara hien tai" RONG',
   'Cho xem tam hang tong thi hai nguon giong het nhau, nut chon nguon thanh vo nghia');
ok(count($P->theoNguon(PartsModel::NGUON_GARA, 0)) === 0,
   'Khong co gara thi tra ve rong, khong lo hang tong');

/* --- 2. Giá riêng đè giá tổng; bỏ trống thì rơi về giá tổng --- */
$p1 = $tong[0]; $p2 = $tong[1];
$G->datGia($gA, $p1['id'], '888000');
$G->datGia($gA, $p2['id'], '');          // bỏ trống = theo giá tổng

$dm = $P->theoNguon(PartsModel::NGUON_GARA, $gA);
ok(count($dm) === 2, 'Gara A co dung 2 mat hang sau khi chon');

$theoId = [];
foreach ($dm as $r) $theoId[(int) $r['id']] = $r;

ok((float) $theoId[(int) $p1['id']]['price'] === 888000.0,
   'Gia rieng DE len gia tong');
ok((float) $theoId[(int) $p2['id']]['price'] === (float) $p2['price'],
   'Bo trong gia rieng thi roi ve gia tong',
   'Ep (float) chuoi rong thanh 0.0 -> ca danh muc am tham ve 0 dong');
ok($theoId[(int) $p2['id']]['gia_rieng'] === null,
   'Gia rieng bo trong luu NULL chu khong phai 0',
   '0 la mot muc gia that (kiem tra mien phi) — lan hai thu nay la mat hang mien phi nhay ve gia goc');

/* GIÁ KHUYẾN MÃI CỦA KHO TỔNG KHÔNG ĐƯỢC ĐÈ GIÁ RIÊNG CỦA GARA.

   Cả hệ thống dùng quy ước "có sale_price thì lấy sale_price, không thì lấy
   price". Nếu theoNguon() cứ COALESCE sale_price về danh mục tổng, thì mặt
   hàng đang khuyến mãi ở kho tổng sẽ giữ nguyên giá khuyến mãi và giá riêng
   của gara bị bỏ qua TRONG IM LẶNG — form báo giá hiện đúng con số kho tổng.
   Đã xảy ra thật: gara đặt 555.000 mà form vẫn hiện 1.380.000. */
$apDung = function($r){ return (int) (!empty($r['sale_price']) ? $r['sale_price'] : $r['price']); };

$idKM = null;
foreach ($tong as $r){ if (!empty($r['sale_price'])){ $idKM = (int) $r['id']; break; } }
if ($idKM === null){
    // Không có mặt hàng nào đang khuyến mãi -> tự dựng một cái để thử
    $idKM = (int) $p1['id'];
    $P->edit(['sale_price' => 1380000], $idKM);
    $datTam = true;
}
$G->datGia($gA, $idKM, '555000');
$dmKM   = $P->theoNguon(PartsModel::NGUON_GARA, $gA);
$rowKM  = null;
foreach ($dmKM as $r) if ((int) $r['id'] === $idKM) $rowKM = $r;

ok($rowKM !== null, 'Tim thay mat hang dang khuyen mai trong danh muc gara');
ok($rowKM !== null && $apDung($rowKM) === 555000,
   'Gia rieng cua gara THANG gia khuyen mai cua kho tong',
   'Dang ap dung ' . ($rowKM !== null ? number_format($apDung($rowKM)) : '?')
   . ' — COALESCE sale_price ve danh muc tong se nuot mat gia rieng');

// Bỏ giá riêng đi thì phải rơi lại về giá khuyến mãi của kho tổng
$G->datGia($gA, $idKM, '');
$rowKM2 = null;
foreach ($P->theoNguon(PartsModel::NGUON_GARA, $gA) as $r) if ((int) $r['id'] === $idKM) $rowKM2 = $r;
$goc = null;
foreach ($tong as $r) if ((int) $r['id'] === $idKM) $goc = $r;
ok($rowKM2 !== null && $goc !== null && $apDung($rowKM2) === $apDung($goc),
   'Bo gia rieng thi roi lai ve gia (ke ca gia khuyen mai) cua kho tong');

if (!empty($datTam)) $P->edit(['sale_price' => null], $idKM);

/* Trả lại đúng trạng thái trước khối này: p1 có giá riêng, p2 theo giá tổng.
   $idKM rất có thể CHÍNH LÀ p1 (mặt hàng đầu tiên đang khuyến mãi), nên nếu
   chỉ boChon() thì các khẳng định bên dưới đếm nhầm số dòng. */
$G->boChon($gA, $idKM);
$G->datGia($gA, $p1['id'], '888000');
$G->datGia($gA, $p2['id'], '');
ok($G->demTheoGara($gA) === 2, 'Da tra lai dung trang thai truoc khi thu gia khuyen mai');

// 0 đồng phải lưu được, và KHÁC với bỏ trống
$G->datGia($gA, $p2['id'], '0');
$row = $G->mot($gA, $p2['id']);
ok($row['price'] !== null && (float) $row['price'] === 0.0,
   'Gia 0 dong luu duoc va KHAC voi bo trong');
$G->datGia($gA, $p2['id'], '');   // trả lại

/* --- 3. UNIQUE chặn trùng --- */
$demTruoc = $G->demTheoGara($gA);
$G->datGia($gA, $p1['id'], '777000');
ok($G->demTheoGara($gA) === $demTruoc, 'Chon lai mat hang da co thi CAP NHAT, khong them dong moi');

/* --- 4. Hàng riêng: không lọt sang gara khác, không lên web --- */
$idRieng = $P->add([
    'code' => 'ZZTA-9999', 'name' => 'ZZ Cong tho thu', 'slug' => $P->slugTrong('ZZ Cong tho thu'),
    'item_type' => 'service', 'price' => 150000, 'garage_id' => $gA,
    'status' => 1, 'show_on_web' => 0,
]);

$dmA = $P->theoNguon(PartsModel::NGUON_GARA, $gA);
$dmB = $P->theoNguon(PartsModel::NGUON_GARA, $gB);
$coTrong = function($ds, $id){
    foreach ($ds as $r) if ((int) $r['id'] === (int) $id) return true;
    return false;
};
ok($coTrong($dmA, $idRieng),  'Hang rieng CO trong danh muc cua chinh gara do');
ok(!$coTrong($dmB, $idRieng), 'Hang rieng cua gara A KHONG lot sang gara B');
ok(!$coTrong($P->theoNguon(PartsModel::NGUON_TONG), $idRieng),
   'Hang rieng KHONG nam trong danh muc tong');

// getForSelect() là thứ form Lập báo giá đang dùng — cũng không được lộ
ok(!$coTrong($P->getForSelect(), $idRieng),
   'getForSelect() KHONG tra ve hang rieng cua gara',
   'Day la o chon hang o form Lap bao gia — lo la gara nao cung thay hang cua nhau');

// Storefront: kể cả khi ai đó bật show_on_web
$P->edit(['show_on_web' => 1], $idRieng);
ok(!$coTrong($P->storefront([], 500), $idRieng),
   'Hang rieng KHONG len website chung ke ca khi bat co show_on_web',
   'Website la trang chung; dang hang rieng cua mot chi nhanh len do thi khach noi khac dat phai thu khong co');
$P->edit(['show_on_web' => 0], $idRieng);

/* --- 5. Xoá gara còn hàng riêng phải bị chặn --- */
$dung = $GA->dangDungODau($gA);
ok(isset($dung['hàng riêng']) && $dung['hàng riêng'] >= 1,
   'Gara con hang rieng thi bao ro ra',
   'Khong bao thi nguoi dung chi thay mot loi CSDL kho hieu');

$loi = false;
try { $GA->remove($gA); } catch (\Throwable $e){ $loi = true; }
$conSong = !empty($GA->getDetail($gA));
ok($conSong, 'Khoa ngoai RESTRICT chan xoa gara con hang rieng');

/* --- 6. Xoá mặt hàng thì giá riêng của nó đi theo --- */
$P->remove($idRieng);
$G->datGia($gB, $p1['id'], '111000');
$demB = $G->demTheoGara($gB);
ok($demB === 1, 'Gara B co 1 dong gia rieng');
$pdo->prepare("DELETE FROM garage_part_prices WHERE garage_id = ?")->execute([$gB]);

// Dọn sạch
$pdo->prepare("DELETE FROM garage_part_prices WHERE garage_id IN (?, ?)")->execute([$gA, $gB]);
$pdo->prepare("DELETE FROM parts WHERE garage_id IN (?, ?)")->execute([$gA, $gB]);
$GA->remove($gA); $GA->remove($gB);
ok(empty($GA->getDetail($gA)) && empty($GA->getDetail($gB)), 'Da don sach du lieu test');
ok((int) $pdo->query("SELECT COUNT(*) FROM parts WHERE garage_id IS NOT NULL")->fetchColumn() === 0,
   'Khong con hang rieng nao sot lai');

exit(summary());

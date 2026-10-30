<?php
/**
 * KHO TỔNG KHÔNG CÓ "DANH MỤC CỦA GARA" (30/10/2026, theo yêu cầu).
 *
 * Chạy:  C:\xampp\php\php.exe tests\KhoTongKhongCoDanhMucTest.php
 *
 * Danh mục của một gara trả lời "trong hàng chung, gara tôi làm những món
 * nào". Kho tổng không có câu hỏi đó — hàng chung là của chính nó. Đứng ở ghế
 * Tân Phát thì màn đó và màn Quản lý hàng hoá gần như trùng nhau, nhìn không
 * ra khác chỗ nào.
 *
 * ẨN MỘT MÀN KHÔNG CHỈ LÀ BỚT MỘT DÒNG MENU. Kho tổng vẫn tick được vào
 * `garage_part_prices` như một gara thường, và đã tick thật: 17/18 mặt hàng.
 * Mặt hàng thứ 18 KHÔNG hiện ra ở ô chọn hàng của chính Tân Phát, không báo
 * gì. Ẩn menu mà để nguyên chỗ đó thì tự dựng một cái bẫy: thêm hàng mới vào
 * kho tổng, nó không hiện, mà màn để sửa thì vừa giấu đi mất.
 *
 * Nên test gác năm điều:
 *
 *   1. MENU ẨN VỚI KHO TỔNG, GARA THƯỜNG KHÔNG ĐỔI. Ẩn lan sang gara là lấy
 *      mất đúng cái màn họ cần nhất.
 *
 *   2. KHÔNG GẮN VÀO _KHO_TONG_MENU_GON. Công tắc đó là bản thử về bố cục
 *      menu; đây là một sự thật nghiệp vụ. Gắn chung thì tắt bản thử hoá tắt
 *      cả hai, và không ai nhớ vì sao.
 *
 *   3. NGUỒN HÀNG CỦA KHO TỔNG PHẢI ĐỦ. theoNguon(NGUON_GARA, kho tổng) trả
 *      rỗng KỂ CẢ khi bảng còn dòng cũ, để mọi chỗ gọi rơi về danh mục tổng.
 *
 *   4. CHỖ GỌI PHẢI CÓ ĐƯỜNG LÙI. Trả rỗng mà bên gọi không lùi về danh mục
 *      tổng thì ô chọn hàng trống trơn — hỏng nặng hơn lúc chưa sửa.
 *
 *   5. GÕ THẲNG ĐƯỜNG DẪN PHẢI ĐỌC ĐƯỢC LÝ DO. Chỉ ẩn trên menu nên màn vẫn
 *      vào được; gặp một danh sách rỗng thì người dùng tưởng mất dữ liệu.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';
$sb  = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');

/** Chạy thật khối logic đầu sidebar.php rồi trả về menu nó dựng ra. */
$dungMenu = function($laGaraTong) use ($sb){
    $het = strpos($sb, "\n?>");
    if ($het === false) return null;
    $ma = substr($sb, strlen('<?php'), $het - strlen('<?php'));

    $garaHienTai = ['id' => 1, 'name' => 'Thu', 'is_master' => $laGaraTong ? 1 : 0];
    $listModules = [];
    $menuGroups = $groupIcons = $groupSeparators = $anNhom = $menuDon = null;

    eval($ma);
    return $menuGroups;
};

// ---------------------------------------------------------------------------
section('Menu — an voi kho tong, gara thuong khong doi (chot 1)');

$tong = $dungMenu(true);
$gara = $dungMenu(false);
ok($tong !== null && $gara !== null, 'Chay duoc khoi logic cua sidebar.php');

if ($tong !== null && $gara !== null){
    ok(!in_array('garage-catalog', $tong['Hàng hoá'], true),
       'Kho tong KHONG thay `garage-catalog` tren menu');
    ok(in_array('garage-catalog', $gara['Hàng hoá'], true),
       'Gara thuong VAN thay `garage-catalog`',
       'An lan sang gara la lay mat dung cai man ho can nhat');

    /* Phần còn lại của nhóm phải nguyên vẹn — array_diff để sót thì mất thêm. */
    ok(in_array('products', $tong['Hàng hoá'], true)
       && in_array('part-categories', $tong['Hàng hoá'], true),
       'Nhom Hang hoa cua kho tong van con `products` va `part-categories`');
    ok(count($gara['Hàng hoá']) - count($tong['Hàng hoá']) === 1,
       'Kho tong chi bot DUNG MOT muc so voi gara thuong');
}

// ---------------------------------------------------------------------------
section('Khong gan vao cong tac ban thu (chot 2)');

/* Dòng `if` phải đọc `is_master`, KHÔNG được nhắc _KHO_TONG_MENU_GON. */
ok(preg_match('~if\s*\(\s*!empty\(\$garaHienTai\[\x27is_master\x27\]\)\s*\)\s*\{\s*\$menuGroups\[\x27Hàng hoá\x27\]~u', $sb) === 1,
   'Dieu kien an chi xet is_master',
   'Gan vao _KHO_TONG_MENU_GON thi tat ban thu hoa tat ca hai');

/* Chạy ở tiến trình riêng với công tắc TẮT — vẫn phải ẩn. */
$kich = "error_reporting(0);\n"
      . "define('_KHO_TONG_MENU_GON', false);\n"
      . "require GOC . '/vendor/autoload.php';\n"
      . "require GOC . '/config.php';\n"
      . "\$sb = file_get_contents(GOC . '/app/views/layouts/admin/sidebar.php');\n"
      . "\$ma = substr(\$sb, 5, strpos(\$sb, \"\\n?>\") - 5);\n"
      . "\$garaHienTai = ['id' => 1, 'name' => 'Thu', 'is_master' => 1];\n"
      . "\$listModules = [];\n"
      . "eval(\$ma);\n"
      . "echo in_array('garage-catalog', \$menuGroups['Hàng hoá'], true) ? 'CON' : 'AN';\n";
$kich = str_replace('GOC', var_export(realpath($goc), true), $kich);

$tam = sys_get_temp_dir() . '/zz_dmgara_' . getmypid() . '.php';
file_put_contents($tam, "<?php\n" . $kich);
$ra = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tam) . ' 2>&1'));
@unlink($tam);
ok($ra === 'AN', 'Tat _KHO_TONG_MENU_GON: kho tong VAN khong thay `garage-catalog`',
   'Ket qua tien trinh con: ' . $ra);

// ---------------------------------------------------------------------------
section('Chi an tren menu — duong dan van con');

$rt = codeOnly($goc . 'routes/web.php');
ok(strpos($rt, "'garage-catalog'") !== false, 'Route `garage-catalog` van con');
ok(is_file($goc . 'app/controllers/admin/Garagecatalog.php'), 'Controller van con');

/* Không được có migration nào xoá dữ liệu nhân danh lần sửa này — xoá rồi thì
   bật lại màn hình cũng không khôi phục được gara đã tick những gì. */
$xau = [];
foreach (glob($goc . 'database/migrations/*.php') as $f){
    $src = file_get_contents($f);
    /* Phải soi ĐÚNG câu xoá bảng này. Bản đầu chỉ hỏi "file có nhắc
       garage_part_prices" VÀ "file có chữ DELETE FROM" — hai điều rời nhau,
       nên migration gốc (tạo bảng, rồi down() xoá `parts`) bị bắt oan. */
    if (preg_match('~DELETE\s+FROM\s+`?garage_part_prices`?~i', $src)){
        $xau[] = basename($f);
    }
}
ok(empty($xau), 'Khong migration nao xoa du lieu `garage_part_prices`',
   'Co: ' . implode(', ', $xau) . ' — xoa du lieu thi khong con duong lui');

// ---------------------------------------------------------------------------
section('Cho goi phai co duong lui ve danh muc tong (chot 4)');

$po = codeOnly($goc . 'app/controllers/admin/Purchaseorders.php');
ok(preg_match('~if\s*\(empty\(\$c\[\x27parts\x27\]\)\)[^;]*NGUON_TONG~', $po) === 1,
   'Don mua: rong thi lui ve danh muc tong',
   'Thieu duong lui thi o chon hang cua kho tong trong tron');

$qt = codeOnly($goc . 'app/controllers/admin/Quotations.php');
ok(strpos($qt, 'hopNhatHang($tong, $gara)') !== false,
   'Bao gia: o chon hang la HOP cua hai nguon nen rong van du hang');

$add = file_get_contents($goc . 'app/views/admin/quotations/add.php');
ok(preg_match('~\$nguonMacDinh\s*=\s*!empty\(\$partsGara\)\s*\?\s*\x27gara\x27\s*:\s*\x27tong\x27~', $add) === 1,
   'Bao gia: rong thi nguon mac dinh la kho tong');
ok(preg_match('~if\s*\(!empty\(\$garaCuaPhieu\)\s*&&\s*empty\(\$garaCuaPhieu\[\x27is_master\x27\]\)\)~', $add) === 1,
   'Bao gia: o chon nguon AN voi kho tong',
   'De lai thi hai nut "Cong ty" / "Kho tong" tro thanh mot thu, mot nut con mo di');

// ---------------------------------------------------------------------------
section('Go thang duong dan phai doc duoc ly do (chot 5)');

$ctl = codeOnly($goc . 'app/controllers/admin/Garagecatalog.php');
ok(strpos($ctl, "\$c['laKhoTong'] = la_gara_tong()") !== false,
   'Controller bao cho view biet dang la kho tong');

$vw = file_get_contents($goc . 'app/views/admin/garagecatalog/lists.php');
ok(strpos($vw, '@if (!empty($laKhoTong))') !== false, 'View co nhanh rieng cho kho tong');
ok(strpos($vw, 'Kho tổng không dùng màn này') !== false, 'View noi ro kho tong khong dung man nay');
ok(strpos($vw, 'Quản lý hàng hoá') !== false, 'View chi sang man thay the');
ok(strpos($vw, '@elseif') === false, 'Khong dung @elseif',
   'Template chi str_replace("@else") nen @elseif bi cat thanh "else" + "if (...)" roi vo');

$soIf  = preg_match_all('~^@if~m', $vw);
$soEnd = preg_match_all('~^@endif~m', $vw);
ok($soIf === $soEnd, "Can bang @if/@endif ($soIf / $soEnd)");

// ---------------------------------------------------------------------------
section('Chay that tren MySQL (chot 3)');

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
if (empty($master['id'])){
    echo "\n[SKIP] Chua khai gara tong.\n"; exit(summary());
}
$mid = (int) $master['id'];

$dsTong = $P->theoNguon(PartsModel::NGUON_TONG);
if (count($dsTong) < 2){
    echo "\n[SKIP] Danh muc tong chua co du 2 mat hang de thu.\n"; exit(summary());
}

/* TICK TẠM MỘT MẶT HÀNG CHO KHO TỔNG rồi khẳng định code VẪN BỎ QUA nó.
   Khẳng định "trả về rỗng" khi bảng vô tình không có dòng nào thì không chứng
   minh được gì — nó xanh vì không có dữ liệu, không phải vì code đúng. */
$chuaTick = null;
$hoi = $pdo->prepare('SELECT COUNT(*) FROM garage_part_prices WHERE garage_id = ? AND part_id = ?');
foreach ($dsTong as $r){
    $hoi->execute([$mid, (int) $r['id']]);
    if ((int) $hoi->fetchColumn() === 0){ $chuaTick = (int) $r['id']; break; }
}
$tuTao = false;
if ($chuaTick !== null){
    $G->datGia($mid, $chuaTick, '777000');
    $tuTao = true;
}
$hoiTat = $pdo->prepare('SELECT COUNT(*) FROM garage_part_prices WHERE garage_id = ? AND status = 1');
$hoiTat->execute([$mid]);
$soDong = (int) $hoiTat->fetchColumn();

ok($soDong > 0, "Kho tong dang co $soDong dong trong `garage_part_prices` de thu",
   'Khong co dong nao thi khang dinh duoi day xanh vo nghia');
ok(count($P->theoNguon(PartsModel::NGUON_GARA, $mid)) === 0,
   'theoNguon(NGUON_GARA, kho tong) tra RONG du bang con dong cu',
   'Con doc bang do thi mat hang chua tick bien mat khoi o chon cua chinh Tan Phat');
ok(count($P->theoNguon(PartsModel::NGUON_GARA, $mid, true)) === 0,
   'Dung ca khi loc bo dich vu (duong don mua di qua)');

if ($tuTao){
    $pdo->prepare('DELETE FROM garage_part_prices WHERE garage_id = ? AND part_id = ?')
        ->execute([$mid, $chuaTick]);
    $hoi->execute([$mid, $chuaTick]);
    ok((int) $hoi->fetchColumn() === 0, 'Da don dong tick tam cua test');
}

/* Gara thường không đổi gì. */
$gX = $GA->add(['code' => 'ZZKT', 'name' => 'Gara thu kho tong', 'is_master' => 0,
                'status' => 1, 'sort_order' => 92]);
ok(count($P->theoNguon(PartsModel::NGUON_GARA, $gX)) === 0,
   'Gara thuong chua chon gi: van RONG nhu truoc');

$G->datGia($gX, (int) $dsTong[0]['id'], '555000');
$dmX = $P->theoNguon(PartsModel::NGUON_GARA, $gX);
ok(count($dmX) === 1, 'Gara thuong tick 1 mon thi danh muc co 1 mon',
   'Dem duoc: ' . count($dmX));
ok(!empty($dmX) && (int) $dmX[0]['price'] === 555000,
   'Gara thuong van giu duoc gia rieng');

$pdo->prepare('DELETE FROM garage_part_prices WHERE garage_id = ?')->execute([$gX]);
$pdo->prepare('DELETE FROM parts WHERE garage_id = ?')->execute([$gX]);
$GA->remove($gX);
ok(empty($GA->getDetail($gX)), 'Da don sach gara thu');

exit(summary());

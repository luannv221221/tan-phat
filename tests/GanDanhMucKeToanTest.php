<?php
/**
 * Test GÁN / BỎ GÁN DANH MỤC KẾ TOÁN DÙNG CHUNG THEO TỪNG CÔNG TY.
 *
 * Chạy:  C:\xampp\php\php.exe tests\GanDanhMucKeToanTest.php
 *
 * Khách hỏi 26/11/2026: "phần kế toán có cái danh mục tk, danh mục loại phiếu,
 * nó dùng chung nhau à? vừa tạo công ty thử nó có đủ luôn". Anh chốt cùng ngày:
 * "Khách muốn tân phát dựng sẵn nhưng cho thể gán hoặc không gán theo từng cty
 * cơ, không phải cty con nào cũng giống nhau".
 *
 * Chốt:
 *   - Tân Phát vẫn dựng sẵn bộ chuẩn (dòng `garage_id` NULL). Mặc định công ty
 *     nào cũng được gán hết (như trước) — công ty mở mới vẫn lập phiếu được ngay.
 *   - Màn Quản lý công ty › Danh mục kế toán của từng công ty: tick / bỏ tick
 *     từng tài khoản, từng loại phiếu. Bỏ gán thì CÔNG TY ĐÓ không thấy, không
 *     chọn được, gửi tay id cũng không lập được phiếu. Công ty khác không đổi.
 *   - Lưu bảng `acc_shared_hidden` (dòng bị BỎ gán) — migration 000124.
 *   - Không bỏ gán được thứ đang dùng: loại phiếu đã có phiếu, tài khoản đã có
 *     phiếu / loại phiếu của công ty / loại phiếu còn gán đang dùng; bỏ cha thì
 *     con theo, giữ con thì giữ cha. Giữ lại thì nói lý do.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Nguon');

ok(count(glob($goc . 'database/migrations/*_000124_*.php')) === 1, 'Co migration 000124 (bang bo gan)');
ok(is_file($goc . 'app/models/AccSharedHiddenModel.php'), 'Co AccSharedHiddenModel');
ok(is_file($goc . 'app/views/admin/garages/ke-toan.php'), 'Co man Danh muc ke toan cua cong ty');
$r = file_get_contents($goc . 'routes/web.php');
ok(strpos($r, "Route::get('garages/ke-toan/(\\d+)'") !== false && strpos($r, "Route::post('garages/ke-toan/(\\d+)'") !== false,
   'Co tuyen GET + POST garages/ke-toan/<id>');
ok(strpos(file_get_contents($goc . 'app/views/admin/garages/lists.php'), "/ke-toan/'") !== false,
   'Danh sach cong ty co nut mo man Danh muc ke toan');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){ echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary()); }

ok((bool) $pdo->query("SHOW TABLES LIKE 'acc_shared_hidden'")->fetch(), 'CSDL co bang acc_shared_hidden (da chay 000124)');
if (!$pdo->query("SHOW TABLES LIKE 'acc_shared_hidden'")->fetch()){ exit(summary()); }

require_once $goc . 'app/models/AccAccountsModel.php';
require_once $goc . 'app/models/AccVoucherTypesModel.php';
require_once $goc . 'app/models/AccSharedHiddenModel.php';

$idTong = (int) $pdo->query("SELECT id FROM garages WHERE is_master = 1 LIMIT 1")->fetchColumn();
$cty = $pdo->query("SELECT id FROM garages WHERE is_master = 0 ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
if (count($cty) < 2){ echo "\n[SKIP] Can 2 cong ty con.\n"; exit(summary()); }
list($A, $B) = array_map('intval', $cty);

$tk = $pdo->query("SELECT code, id FROM acc_accounts WHERE garage_id IS NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
$lp = $pdo->query("SELECT code, id FROM acc_voucher_types WHERE garage_id IS NULL")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach (['131', '141', '156', '333', '3331', '1111'] as $c) if (empty($tk[$c])){ echo "\n[SKIP] Thieu tai khoan chung $c.\n"; exit(summary()); }
foreach (['PT01', 'PC04', 'BN03'] as $c) if (empty($lp[$c])){ echo "\n[SKIP] Thieu loai phieu chung $c.\n"; exit(summary()); }

$donSach = function() use ($pdo, $A, $B){
    $pdo->exec("DELETE FROM acc_shared_hidden WHERE garage_id IN ($A, $B)");
    $pdo->exec("DELETE FROM acc_vouchers WHERE voucher_no LIKE 'ZZGAN%'");
};
$donSach();
register_shutdown_function($donSach);

$H = new AccSharedHiddenModel();
$tatCaTk = array_map('intval', array_values($tk));
$tatCaLp = array_map('intval', array_values($lp));
$tru = function(array $ds, array $bo){ return array_values(array_diff($ds, $bo)); };
$an = function($g) use ($pdo){
    $r = ['account' => [], 'vtype' => []];
    foreach ($pdo->query("SELECT kind, item_id FROM acc_shared_hidden WHERE garage_id = " . (int) $g) as $x) $r[$x['kind']][] = (int) $x['item_id'];
    return $r;
};

// ---------------------------------------------------------------------------
section('Bo gan: cong ty do khong thay, cong ty khac khong doi');

$kq = $H->ganChoCongTy($A, $tru($tatCaTk, [(int) $tk['156']]), $tru($tatCaLp, [(int) $lp['BN03']]));
$anA = $an($A);
ok(in_array((int) $tk['156'], $anA['account'], true) && in_array((int) $lp['BN03'], $anA['vtype'], true),
   'Luu dung dong bo gan (156, BN03)', json_encode($anA));

$T = new AccAccountsModel(); $V = new AccVoucherTypesModel();
$maTk = function($g) use ($T){ return Model::trongGara($g, function() use ($T){ return array_column($T->getTree(), 'code'); }); };
$maLp = function($g) use ($V){ return Model::trongGara($g, function() use ($V){ return array_column($V->getLists(), 'code'); }); };

ok(!in_array('156', $maTk($A), true), 'Cong ty A: danh muc tai khoan KHONG con 156');
ok(!in_array('BN03', $maLp($A), true), 'Cong ty A: danh muc loai phieu KHONG con BN03');
ok(in_array('156', $maTk($B), true) && in_array('BN03', $maLp($B), true), 'Cong ty B van thay du (khong bi lay theo)');
ok(in_array('156', $maTk($idTong), true) && in_array('BN03', $maLp($idTong), true), 'Tan Phat van thay du (noi quan ly bo chuan)');
ok(Model::trongGara($A, function() use ($V){ return $V->getLists('chi', true); }) !== []
   && !in_array('BN03', array_column(Model::trongGara($A, function() use ($V){ return $V->getLists('chi', true); }), 'code'), true),
   'O chon loai phieu luc lap phieu chi cua A khong co BN03');
ok(empty(Model::trongGara($A, function() use ($V, $lp){ return $V->chiTietKemTk((int) $lp['BN03']); })),
   'Gui tay id BN03 khi lap phieu: A KHONG lap duoc');
ok(empty(Model::trongGara($A, function() use ($T, $tk){ return $T->getDetail((int) $tk['156']); })),
   'Gui tay id 156 (lam tai khoan cha / no-co): A KHONG dung duoc');
ok(!empty(Model::trongGara($B, function() use ($V, $lp){ return $V->chiTietKemTk((int) $lp['BN03']); })),
   'B van lap phieu BN03 duoc');

// ---------------------------------------------------------------------------
section('Cay: bo cha thi con theo, giu con thi giu cha');

$H->ganChoCongTy($A, $tru($tatCaTk, [(int) $tk['333'], (int) $tk['3331']]), $tatCaLp);
$anA = $an($A)['account'];
ok(in_array((int) $tk['333'], $anA, true) && in_array((int) $tk['3331'], $anA, true), 'Bo 333 + 3331: ca hai deu bo gan');

$kq = $H->ganChoCongTy($A, $tru($tatCaTk, [(int) $tk['333']]), $tatCaLp);
ok(!in_array((int) $tk['333'], $an($A)['account'], true), 'Giu 3331 ma bo 333: 333 tu duoc giu (con can cha)');

// ---------------------------------------------------------------------------
section('Khong bo gan duoc thu dang dung — giu lai va noi ly do');

$kq = $H->ganChoCongTy($A, $tru($tatCaTk, [(int) $tk['131']]), $tatCaLp);
ok(!in_array((int) $tk['131'], $an($A)['account'], true), 'Bo 131 trong khi PT01 (no 1111 / co 131) con gan: 131 duoc giu');
ok(mb_strpos(implode(' ', $kq['giuLai']), '131') !== false && mb_strpos(implode(' ', $kq['giuLai']), 'PT01') !== false,
   'Bao ro: giu 131 vi PT01 dang dung', json_encode($kq['giuLai'], JSON_UNESCAPED_UNICODE));

$pdo->prepare("INSERT INTO acc_vouchers (voucher_no, loai, vtype_id, voucher_date, ly_do, amount, debit_code, credit_code, status, garage_id, create_at)
               VALUES ('ZZGAN1', 'chi', ?, CURDATE(), 'ZZGAN', 1000, '141', '1111', 1, ?, NOW())")->execute([(int) $lp['PC04'], $A]);
$kq = $H->ganChoCongTy($A, $tru($tatCaTk, [(int) $tk['141']]), $tru($tatCaLp, [(int) $lp['PC04']]));
ok(!in_array((int) $lp['PC04'], $an($A)['vtype'], true), 'PC04 da co phieu cua A: khong bo gan duoc');
ok(!in_array((int) $tk['141'], $an($A)['account'], true), 'Tai khoan 141 da co phieu cua A: khong bo gan duoc');
ok(count($kq['giuLai']) >= 2, 'Bao ro hai muc duoc giu lai', json_encode($kq['giuLai'], JSON_UNESCAPED_UNICODE));
$dd = $H->dangDung($A);
ok(isset($dd['vtype'][(int) $lp['PC04']]) && isset($dd['account'][(int) $tk['141']]), 'Man gan biet truoc muc nao dang dung (khoa o tick)');

// ---------------------------------------------------------------------------
section('Chay that qua man hinh');

$base = web_goc();
$lam = function($jar){
    return function($method, $url, $data = null) use ($jar){
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
        if ($method === 'POST'){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array) $data)); }
        $b = curl_exec($ch); $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return [$c, (string) $b];
    };
};
preg_match("~const MK_MAU = '([^']+)'~", file_get_contents($goc . 'tools/tao-du-lieu-gara-mau.php'), $mk);
$dangNhap = function($email) use ($lam, $base, $mk){
    $jar = tempnam(sys_get_temp_dir(), 'gan'); $h = $lam($jar);
    list($c, $b) = $h('GET', $base . '/dang-nhap');
    if ($c === 0) return null;
    $t = preg_match('~name="_token"[^>]*value="([^"]+)"~', $b, $m) ? $m[1] : '';
    $h('POST', $base . '/dang-nhap', ['_token' => $t, 'email' => $email, 'password' => $mk[1] ?? '']);
    return $h;
};
$tp = $dangNhap('admin@gara-mau.test');
if ($tp === null){ echo "\n[SKIP] Apache khong chay.\n"; exit(summary()); }
$maA = $pdo->query("SELECT code FROM garages WHERE id = $A")->fetchColumn();

list($c, $man) = $tp('GET', $base . "/admin/garages/ke-toan/$A");
if ($c !== 200){ echo "\n[SKIP] Khong mo duoc man bang admin@gara-mau.test (HTTP $c).\n"; exit(summary()); }
ok(substr_count($man, 'name="tk[]"') >= count($tk) && substr_count($man, 'name="lp[]"') >= count($lp),
   'Man gan liet ke du tai khoan + loai phieu dung chung');
ok(strpos($man, 'value="' . (int) $lp['PC04'] . '"') !== false && preg_match('~data-khoa="1"[^>]*value="' . (int) $lp['PC04'] . '"|value="' . (int) $lp['PC04'] . '"[^>]*data-khoa="1"~', $man) === 1,
   'Loai phieu dang dung hien KHOA (khong bo tick duoc)');
$tok = preg_match('~name="_token"[^>]*value="([^"]+)"~', $man, $m) ? $m[1] : '';

$donSach();
list($c) = $tp('POST', $base . "/admin/garages/ke-toan/$A",
               ['_token' => $tok, 'tk' => $tru($tatCaTk, [(int) $tk['156']]), 'lp' => $tru($tatCaLp, [(int) $lp['BN03']])]);
ok($c === 302, 'Luu man gan (chuyen trang)', "HTTP $c");
$anA = $an($A);
ok($anA['account'] === [(int) $tk['156']] && $anA['vtype'] === [(int) $lp['BN03']], 'CSDL: dung 156 + BN03 bi bo gan cho A', json_encode($anA));
ok($an($B) === ['account' => [], 'vtype' => []], 'Cong ty B khong bi dong vao');

/* Chu cong ty A (tai khoan thu cua DMSG) vao danh muc cua minh. */
if ($maA === 'DMSG'){
    $chu = $dangNhap('chugara.sg@gara-mau.test');
    list($c, $b) = $chu('GET', $base . '/admin/acc-voucher-types');
    ok($c === 200 && strpos($b, '>BN03<') === false && strpos($b, 'PT01') !== false,
       'Chu cong ty A: danh muc loai phieu khong con BN03, van con PT01', "HTTP $c");
    list($c) = $chu('GET', $base . "/admin/garages/ke-toan/$A");
    ok($c !== 200, 'Chu cong ty con KHONG mo duoc man gan (chi Tan Phat)', "HTTP $c");
}

exit(summary());

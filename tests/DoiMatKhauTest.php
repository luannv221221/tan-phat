<?php
/**
 * Test ĐỔI MẬT KHẨU của chính tài khoản quản trị đang đăng nhập.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DoiMatKhauTest.php
 *
 * VÌ SAO CÓ TEST NÀY (11/11/2026): khách nhắn Zalo sau buổi họp — "thiếu đổi
 * password em nhé". Menu tài khoản góc phải chỉ có "Xem trang chủ" và "Đăng
 * xuất"; muốn đổi mật khẩu thì phải nhờ người có quyền vào màn Người dùng sửa
 * hộ, tức là mật khẩu mới đi qua tay người khác.
 *
 * NĂM CHỖ DỄ HỎNG, mỗi chỗ một mục dưới đây:
 *
 *   1. KHÔNG HỎI MẬT KHẨU HIỆN TẠI. Ai ngồi vào máy đang mở sẵn phiên là
 *      chiếm được tài khoản vĩnh viễn — đặt mật khẩu mới rồi chủ máy hết vào.
 *
 *   2. ĐỔI XONG MÀ MÁY KHÁC VẪN VÀO ĐƯỢC. Cookie "Ghi nhớ đăng nhập" sống 30
 *      ngày, nên đúng tình huống người ta cần đổi nhất (nghi lộ, lỡ đăng nhập
 *      ở máy lạ) thì việc đổi gần như vô nghĩa.
 *
 *   3. ĐÁ LUÔN CẢ PHIÊN ĐANG THAO TÁC. Người vừa bấm "Đổi mật khẩu" bị văng
 *      ra trang đăng nhập ngay lúc màn hình báo thành công — nhìn như hỏng.
 *
 *   4. TUYẾN POST ĐẶT TÊN RIÊNG. Route::post() không ghi tuyến vào nhóm
 *      `admin/*` (chỉ Route::get() ghi), mà bảng middleware đánh khoá theo
 *      CHUỖI ĐƯỜNG DẪN. Nên tuyến POST phải TRÙNG TÊN tuyến GET thì mới mượn
 *      được AuthMiddleware; đặt `doi-mat-khau/luu` là người chưa đăng nhập
 *      POST thẳng vào cũng chạy tới controller.
 *
 *   5. ĐĂNG KÝ VÀO BẢNG `modules`. Sinh ra một ô tick ở màn Phân quyền, và chỉ
 *      cần ai bỏ tick là cả nhóm đó không đổi được mật khẩu của chính mình.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Tuyen duong + menu');

$rt = file_get_contents($goc . 'routes/web.php');
ok(strpos($rt, "Route::get('doi-mat-khau',  'admin/doimatkhau')") !== false,
   'Co tuyen GET admin/doi-mat-khau');
ok(strpos($rt, "Route::post('doi-mat-khau', 'admin/doimatkhau/postDoi')") !== false,
   'Tuyen POST TRUNG TEN tuyen GET (`doi-mat-khau`)',
   'Dat ten khac thi tuyen POST khong co AuthMiddleware — xem muc 4 dau file');

$hd = file_get_contents($goc . 'app/views/layouts/admin/header.php');
ok(strpos($hd, "admin/doi-mat-khau") !== false, 'Menu tai khoan co muc Doi mat khau');
ok(strpos($hd, "icon('lock')") !== false,
   'Dung bieu tuong `lock`',
   'Sprite Lucide trong du an KHONG co `key`; icon() thieu hinh thi in o trong chu khong bao loi');
ok(strpos($hd, "route('admin/doi-mat-khau')") === false,
   'Muc menu KHONG hoi route()',
   'Man nay co y khong co module nen khong co quyen nao de hoi; hoi la luon rong');

// ---------------------------------------------------------------------------
section('Controller: kiem mat khau hien tai TRUOC moi thu khac');

$ctl = codeOnly($goc . 'app/controllers/admin/Doimatkhau.php');
ok(strpos($ctl, 'Hash::check($hienTai') !== false,
   'Co doi chieu mat khau hien tai bang Hash::check()');
ok(preg_match('~\$errors\[.current_password.\].*?\$errors\[.new_password.\]~s', $ctl) === 1,
   'Kiem mat khau hien tai NAM TRUOC phan kiem mat khau moi');
ok(strpos($ctl, "\$moi === \$hienTai") !== false,
   'Chan mat khau moi trung mat khau cu',
   'Go lai dung mat khau cu thi man hinh bao "doi thanh cong" trong khi chang co gi doi');
ok(strpos($ctl, 'removeKhacPhien(') !== false,
   'Doi xong thi da cac phien KHAC ra');
ok(strpos($ctl, 'removeByUser(') === false,
   'KHONG dung removeByUser() (xoa sach ca phien dang thao tac)',
   'Xoa ca la nguoi vua doi bi vang ra trang dang nhap ngay luc bao thanh cong');
ok(strpos($ctl, 'Session::regenerate()') !== false,
   'Cap session id moi sau khi doi mat khau');
ok(strpos($ctl, 'Hash::make($moi)') !== false, 'Mat khau moi duoc bam truoc khi luu');

$view = file_get_contents($goc . 'app/views/admin/doi-mat-khau/form.php');
ok(strpos($view, 'csrf_field()') !== false, 'Form co CSRF');
ok(substr_count($view, 'type="password"') === 3,
   'Du ba o mat khau (hien tai / moi / nhap lai)');
ok(strpos($view, 'thiết bị khác') !== false,
   'Man hinh NOI TRUOC chuyen cac thiet bi khac se bi dang xuat',
   'Khong bao truoc thi dien thoai tu dang xuat bi tuong la su co');

$mdl = codeOnly($goc . 'app/models/LoginToken.php');
ok(preg_match('~`user_id` = \? AND `id` <> \?~', $mdl) === 1,
   'removeKhacPhien() giu lai dung mot phien (id <> ?)');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL + HTTP');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

/* CO Y KHONG CO MODULE — xem muc 5 dau file */
$m = $pdo->query("SELECT COUNT(*) FROM modules WHERE link = 'doi-mat-khau'")->fetchColumn();
ok((int) $m === 0,
   'KHONG dang ky `doi-mat-khau` vao bang `modules`',
   'Co module la sinh ra mot o tich o man Phan quyen — bo tich la ca nhom het doi duoc mat khau cua chinh minh');

$base = 'http://localhost:88/tan-phat';
$EMAIL = 'covan.sg@gara-mau.test';   // Staff gara 2 — vai thap nhat, van phai doi duoc
$MK    = 'Gara@2026';
$MK_MOI = 'ZzThu@' . random_int(100000, 999999);

$u = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
$u->execute([$EMAIL]);
$nd = $u->fetch(PDO::FETCH_ASSOC);
if (empty($nd)){
    echo "\n[SKIP] Khong co tai khoan thu $EMAIL.\n"; exit(summary());
}
$hashGoc = $nd['password'];

$jars = [];
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });
/* TRA LAI MAT KHAU GOC du test co dut giua chung — khong thi lan chay sau
   dang nhap khong duoc va ca loat test khac do theo. */
register_shutdown_function(function() use ($pdo, $EMAIL, $hashGoc){
    $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hashGoc, $EMAIL]);
});

$http = function($method, $url, $jar, $data = null){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST'){
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $raw  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    $body = $raw === false ? '' : substr($raw, (int) $info['header_size']);
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'],
            'text' => html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
};
$token = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };
$moJar = function() use (&$jars){ $j = tempnam(sys_get_temp_dir(), 'zzdmk'); $jars[] = $j; return $j; };

$dangNhap = function($jar, $mk) use ($http, $token, $base, $EMAIL){
    $r = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;
    return $http('POST', "$base/dang-nhap", $jar,
                 ['email' => $EMAIL, 'password' => $mk, '_token' => $token($r['text'])]);
};

/* Gui form doi mat khau; tra ve trang SAU khi doi (de doc thong bao / loi) */
$doi = function($jar, $cu, $moi, $lai) use ($http, $token, $base){
    $t = $token($http('GET', "$base/admin/doi-mat-khau", $jar)['text']);
    $http('POST', "$base/admin/doi-mat-khau", $jar,
          ['_token' => $t, 'current_password' => $cu,
           'new_password' => $moi, 'new_password2' => $lai]);
    return $http('GET', "$base/admin/doi-mat-khau", $jar)['text'];
};
$mkTrongCsdl = function($mk) use ($pdo, $EMAIL){
    $h = $pdo->query("SELECT password FROM users WHERE email = " . $pdo->quote($EMAIL))->fetchColumn();
    return password_verify($mk, $h);
};

// --- Hai may cung dang nhap mot tai khoan ---
$mayA = $moJar(); $mayB = $moJar();
if ($dangNhap($mayA, $MK) === null){
    echo "\n[SKIP] Apache khong chay (localhost:88).\n"; exit(summary());
}
$dangNhap($mayB, $MK);

ok($http('GET', "$base/admin", $mayA)['code'] === 200, 'May A dang nhap duoc');
ok($http('GET', "$base/admin", $mayB)['code'] === 200,
   'May B (thiet bi thu hai) cung dang nhap duoc',
   'Khong co khang dinh nay thi phan "da may khac ra" xanh chi vi B von da vao khong duoc');

$r = $http('GET', "$base/admin/doi-mat-khau", $mayA);
ok($r['code'] === 200,
   'Tai khoan Staff (vai thap nhat) MO DUOC man doi mat khau',
   'Man nay khong co module nen khong phu thuoc quyen — HTTP ' . $r['code']);

// --- Bon duong tu choi ---
ok(strpos($doi($mayA, 'SAI-BET', 'Moi@123456', 'Moi@123456'), 'Mật khẩu hiện tại không đúng') !== false,
   'Mat khau hien tai SAI -> tu choi');
ok(strpos($doi($mayA, $MK, 'Moi@123456', 'Lech@999'), 'Mật khẩu nhập lại không khớp') !== false,
   'Nhap lai khong khop -> tu choi');
ok(strpos($doi($mayA, $MK, $MK, $MK), 'phải khác mật khẩu hiện tại') !== false,
   'Mat khau moi TRUNG mat khau cu -> tu choi');
ok(strpos($doi($mayA, $MK, 'abcde', 'abcde'), 'tối thiểu 6 ký tự') !== false,
   'Mat khau moi qua ngan -> tu choi');

ok($mkTrongCsdl($MK),
   'Sau BON lan bi tu choi, mat khau trong CSDL VAN LA mat khau cu',
   'Tu choi o man hinh ma van ghi xuong CSDL la kieu loi khong ai nhin ra');

// --- Doi that ---
$sau = $doi($mayA, $MK, $MK_MOI, $MK_MOI);
ok(strpos($sau, 'Đã đổi mật khẩu') !== false, 'Doi thanh cong -> co thong bao');
ok($mkTrongCsdl($MK_MOI) && !$mkTrongCsdl($MK),
   'CSDL: mat khau MOI co hieu luc, mat khau CU het hieu luc');

ok($http('GET', "$base/admin", $mayA)['code'] === 200,
   'May A (vua doi) VAN o nguyen trong he thong',
   'Da ca phien dang thao tac la nguoi dung bi vang ra ngay luc bao thanh cong');

$rb = $http('GET', "$base/admin", $mayB);
ok($rb['code'] === 302 && strpos($rb['loc'], 'dang-nhap') !== false,
   'May B (thiet bi khac) BI DANG XUAT',
   'Cookie ghi nho song 30 ngay — khong da ra thi doi mat khau gan nhu vo nghia. HTTP '
   . $rb['code'] . ' ' . $rb['loc']);

// --- Dang nhap lai bang mat khau moi, roi tra lai mat khau goc ---
$mayC = $moJar();
$dangNhap($mayC, $MK_MOI);
ok($http('GET', "$base/admin", $mayC)['code'] === 200,
   'Dang nhap lai duoc bang mat khau MOI');

$doi($mayC, $MK_MOI, $MK, $MK);
ok($mkTrongCsdl($MK), 'Da tra lai mat khau goc cho tai khoan thu');

exit(summary());

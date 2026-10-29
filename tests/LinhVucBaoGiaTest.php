<?php
/**
 * LĨNH VỰC (gara ô tô / bán phụ tùng) — quyết định báo giá hiện khối nào.
 *
 * Chạy:  C:\xampp\php\php.exe tests\LinhVucBaoGiaTest.php
 *
 * Tài liệu khách gửi 29/10/2026: "báo giá gara thì cần biển số xe, báo giá phụ
 * tùng thì chỉ cần thông tin khách hàng… thiết kế trong cấu hình hệ thống, cho
 * chọn là gara ô tô thì hiện ra báo giá hiện tại, nếu không thì coi như bán
 * phụ tùng, thiết bị". Ảnh minh hoạ ghi thẳng lên khối Hãng bảo hiểm / Bảo
 * hiểm trả / Biển số xe / Số km: "khi bán phụ tùng, thiết bị không cần phần
 * này".
 *
 * BA CHỖ HỎNG SẼ ÂM THẦM:
 *
 *   1. MẶC ĐỊNH SAI. Thêm một ô cấu hình mà mặc định là "bán phụ tùng" thì mọi
 *      gara đang dùng mở báo giá lên thấy bốn ô biến mất — đổi hành vi sau
 *      lưng họ. Mặc định phải là 'gara'.
 *
 *   2. SỬA PHIẾU CŨ LÀ XOÁ TRẮNG. Ẩn khối đi thì mấy ô đó không được gửi lên,
 *      mà headerData() đọc `isset($f['bao_hiem_tra']) ? ... : 0` và
 *      lien_ket_xe() coi không gửi là để trống. Nên chỉ cần mở một phiếu cũ
 *      (đã có bảo hiểm / biển số) ra sửa một dòng hàng rồi bấm Lưu là mất
 *      sạch, không lời cảnh báo nào. Màn sửa phải hiện khối khi phiếu ĐÃ CÓ dữ
 *      liệu, bất kể lĩnh vực.
 *
 *   3. LĨNH VỰC KHÔNG THỪA HƯỞNG ĐƯỢC. Đây là cờ HÀNH VI, khác mấy ô nhận
 *      diện: Tân Phát đặt một lần làm mặc định, gara nào khác ngành thì tự
 *      chọn lại. Đọc bằng rieng() thay vì map() là gara mới mở không thừa
 *      hưởng gì.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('O cau hinh + ham doc');

$frm = file_get_contents($goc . 'app/views/admin/settings/form.php');
$stt = codeOnly($goc . 'app/controllers/admin/Settings.php');
$fn  = codeOnly($goc . 'app/helpers/functions.php');

ok(strpos($frm, 'name="linh_vuc"') !== false, 'Man Cau hinh co o Linh vuc');
ok(strpos($frm, 'value="gara"') !== false && strpos($frm, 'value="phu_tung"') !== false,
   'Co du hai lua chon: gara / phu_tung');
ok(preg_match('~\$keys\s*=\s*\[[\s\S]*?\x27linh_vuc\x27~', $stt) === 1,
   'linh_vuc nam trong danh sach khoa duoc sua',
   'Khong co trong whitelist thi chon xong bam Luu cung khong luu');

ok(function_exists('la_linh_vuc_gara'), 'Co ham la_linh_vuc_gara()');
ok(function_exists('linh_vuc_hien_tai'), 'Co ham linh_vuc_hien_tai()');

/* CHOT 1 — mac dinh phai la 'gara'. */
ok(preg_match("~val\(\s*\x27linh_vuc\x27\s*,\s*\x27gara\x27\s*\)~", $fn) === 1,
   "Mac dinh la 'gara' khi chua ai khai",
   'Mac dinh "phu_tung" la moi gara dang dung mo bao gia len thay bon o bien mat');

/* CHOT 3 — doc qua val()/map() (co tron Cau hinh chung lam nen), KHONG phai
   rieng(). Day la co hanh vi nen thua huong la dung. */
if (preg_match('~function linh_vuc_hien_tai\(\)\{(.*?)\n\}~s', $fn, $mL)){
    ok(strpos($mL[1], '->val(') !== false,
       'Doc qua val() — gara thua huong duoc mac dinh chung');
    ok(strpos($mL[1], '->rieng(') === false,
       'KHONG doc bang rieng()',
       'rieng() khong tron mac dinh chung — gara moi mo se khong thua huong gi');
}

/* Chi nhan dung hai gia tri; go bay vao CSDL thi phai roi ve 'gara'. */
if (preg_match('~function linh_vuc_hien_tai\(\)\{(.*?)\n\}~s', $fn, $mL2)){
    ok(preg_match("~\x27phu_tung\x27\s*\?\s*\x27phu_tung\x27\s*:\s*\x27gara\x27~", $mL2[1]) === 1,
       'Gia tri la thi roi ve gara, khong de lot gia tri bay');
}

// ---------------------------------------------------------------------------
section('Man LAP bao gia — an khoi khi ban phu tung');

$add = file_get_contents($goc . 'app/views/admin/quotations/add.php');
ok(preg_match('~<\?php if \(la_linh_vuc_gara\(\)\)\s*:\s*\?>~', $add) === 1,
   'quotations/add.php boc khoi bang la_linh_vuc_gara()');

/* Bon o phai nam TRONG khoi do. Lay doan giua if va endif roi kiem. */
if (preg_match('~<\?php if \(la_linh_vuc_gara\(\)\)\s*:\s*\?>(.*?)<\?php endif; \?>~s', $add, $mB)){
    foreach (['insurer_id', 'bao_hiem_tra', 'bien_so', 'so_km'] as $o){
        ok(strpos($mB[1], 'name="' . $o . '"') !== false,
           "quotations/add.php: o `$o` nam trong khoi bi an");
    }
    /* O Khach hang phai nam NGOAI — ban phu tung van can biet ban cho ai. */
    ok(strpos($mB[1], 'name="customer_id"') === false,
       'quotations/add.php: o Khach hang nam NGOAI khoi (ban phu tung van can)');
}

// ---------------------------------------------------------------------------
section('Man SUA bao gia — phieu cu khong bi xoa trang (chot 2)');

$edit = file_get_contents($goc . 'app/views/admin/quotations/edit.php');
ok(strpos($edit, '$coXeBaoHiem') !== false,
   'quotations/edit.php co bien kiem phieu da co bao hiem / xe chua');
ok(preg_match('~if \(la_linh_vuc_gara\(\) \|\| \$coXeBaoHiem\)~', $edit) === 1,
   'Man sua hien khoi khi LA GARA **hoac** phieu da co du lieu',
   'Chi xet linh vuc thi mo phieu cu ra sua mot dong hang la mat bao hiem + bien so');

/* Bien do phai xet DU CA BON o — thieu mot o la o do van bi xoa trang. */
if (preg_match('~\$coXeBaoHiem\s*=(.*?);~s', $edit, $mC)){
    foreach (['insurer_id', 'bao_hiem_tra', 'bien_so', 'so_km'] as $o){
        ok(strpos($mC[1], $o) !== false, "Bien kiem co xet `$o`");
    }
}
ok(mb_strpos($edit, 'không mất dữ liệu') !== false,
   'Co dong giai thich vi sao khoi van hien o che do ban phu tung',
   'Khong noi ra thi nguoi dung tuong o cau hinh khong an gi');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

const DAU = 'ZZLV';
$donDep = function() use ($pdo){
    $pdo->exec("DELETE FROM `garage_settings` WHERE `garage_id` IN (SELECT `id` FROM `garages` WHERE `code` LIKE '" . DAU . "%')");
    $pdo->exec("DELETE FROM `garages` WHERE `code` LIKE '" . DAU . "%'");
};
$donDep();

$pdo->exec("INSERT INTO `garages` (`code`,`name`,`is_master`,`status`,`create_at`)
            VALUES ('" . DAU . "G','" . DAU . " Gara thu',0,1,NOW())");
$gid = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "G'")->fetchColumn();

/* Gara moi mo, chua khai gi -> phai la 'gara'. Doc lai trong tien trinh con vi
   linh_vuc_hien_tai() nho ket qua trong mot request (static $cache). */
$doc = function($gara) use ($goc){
    $ma = '<?php require ' . var_export($goc . 'vendor/autoload.php', true) . ';'
        . ' require ' . var_export($goc . 'config.php', true) . ';'
        . ' App\core\Model::epGara(' . (int) $gara . ');'
        . ' echo linh_vuc_hien_tai();';
    $tam = sys_get_temp_dir() . '/zz_lv_' . getmypid() . '.php';
    file_put_contents($tam, $ma);
    $ra = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tam) . ' 2>&1'));
    @unlink($tam);
    return $ra;
};

ok($doc($gid) === 'gara',
   "Gara moi mo, chua khai gi -> 'gara' (dang ra: " . $doc($gid) . ")",
   'Mac dinh phai giu nguyen hanh vi cu');

$pdo->prepare("INSERT INTO `garage_settings` (`garage_id`,`skey`,`svalue`,`update_at`) VALUES (?,?,?,NOW())")
    ->execute([$gid, 'linh_vuc', 'phu_tung']);
ok($doc($gid) === 'phu_tung', "Khai 'phu_tung' thi doc ra 'phu_tung'");

$pdo->prepare("UPDATE `garage_settings` SET `svalue` = ? WHERE `garage_id` = ? AND `skey` = 'linh_vuc'")
    ->execute(['lung_tung', $gid]);
ok($doc($gid) === 'gara',
   "Gia tri bay trong CSDL -> roi ve 'gara', khong vo",
   'Dang ra: ' . $doc($gid));

$donDep();
exit(summary());

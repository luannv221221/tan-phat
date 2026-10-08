<?php
/**
 * NHÓM QUYỀN THEO GARA — migration 000093, 08/10/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NhomQuyenGaraTest.php
 *
 * VIỆC: mở màn Quản lý nhóm cho gara, để chủ gara tự phân quyền cho nhân viên
 * của mình. Trước đó ba nhóm Admin / Manager / Staff dùng chung CẢ HỆ THỐNG —
 * 4 người nhóm Manager trải 3 gara, 3 người nhóm Staff trải 3 gara. Mở màn đó
 * ra trong tình trạng ấy là: chủ gara Sài Gòn bỏ tick một quyền thì chủ gara
 * Đà Nẵng mất quyền đó theo.
 *
 * SÁU CHỖ HỎNG SẼ ÂM THẦM, mỗi chỗ một mục bên dưới:
 *
 *   1. NHÓM VẪN DÙNG CHUNG. Thiếu `groups`.`garage_id`, hoặc có cột mà quên
 *      nhân bản, là một gara sửa trúng nhóm của mọi gara. Không ai thấy ngay:
 *      người bị mất quyền là người ở gara khác, vài hôm sau mới báo.
 *
 *   2. MANAGER THÀNH TOÀN QUYỀN. `laToanQuyen()` lấy mốc "có role `permission`
 *      trên module `groups`" — mà chính migration 000093 cấp đúng role đó cho
 *      Manager. Thiếu điều kiện "nhóm hệ thống" là mọi chủ gara thành toàn
 *      quyền và gán được nhóm Admin cho nhân viên: leo thang trong một bước.
 *
 *   3. TỰ SỬA NHÓM CỦA MÌNH. Bộ lọc "chỉ tick quyền mình đang có" không chặn
 *      được gì khi đối tượng sửa là chính nhóm mình.
 *
 *   4. ĐƯỜNG VÒNG QUA NHÂN VIÊN. Chủ gara cấp `permission` cho Staff (hợp lệ,
 *      vì đó là quyền chủ gara đang có), rồi Staff mở nhóm Manager ra BỎ TICK
 *      cho tới khi chủ gara mất quyền. Không nâng quyền cho mình, nhưng khoá
 *      được người trên mình.
 *
 *   5. NHÓM RỖNG = NHÓM MẠNH NHẤT. RoleMiddleware xưa nay bỏ qua toàn bộ phần
 *      kiểm quyền khi nhóm không có dòng nào, và với một request thật thì không
 *      ai redirect — trang vẫn vẽ ra. Hai cửa vào: tạo nhóm mới chưa tick gì,
 *      và xoá nhóm (khoá ngoại `users.group_id` là ON DELETE SET NULL).
 *
 *   6. GARA MỚI KHÔNG CÓ NHÓM. Mở gara mà quên nhân bản là chủ gara mới vào
 *      màn Quản lý nhóm thấy bảng trống, và không gán được nhóm nào cho ai.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc  = __DIR__ . '/../';
$base = 'http://localhost:88/tan-phat';

// ---------------------------------------------------------------------------
section('Migration dung hinh');

$mg = glob($goc . 'database/migrations/*_nhom_quyen_theo_gara.php');
ok(count($mg) === 1, 'Co migration nhom quyen theo gara');
$src = count($mg) === 1 ? file_get_contents($mg[0]) : '';

ok(strpos($src, 'ALTER TABLE `groups` ADD COLUMN `garage_id`') !== false,
   'Migration them `groups`.`garage_id`');
ok(strpos($src, "`chi_tan_phat` = 0 WHERE `link` = 'groups'") !== false,
   'Migration mo man Quan ly nhom cho gara');
ok(strpos($src, "`link` = 'modules'") === false,
   'Migration KHONG mo man Quan ly module',
   'Man do chi DANG KY mot man hinh da co trong ma nguon; xoa mot dong la go man do khoi CA HE THONG');

/* Nhan ban phai CHEP CA DONG QUYEN. Nhom moi rong la nhom vao duoc moi man
   hinh (xem muc 5), nen nhan ban thieu quyen con te hon khong nhan ban. */
ok(strpos($src, 'chepQuyen') !== false, 'Migration chep ca dong `permissions` khi nhan ban nhom');
ok(strpos($src, "UPDATE `users` SET `group_id`") !== false,
   'Migration chuyen tai khoan sang nhom cua gara minh',
   'Khong chuyen thi nguoi cua gara B van dung nhom cua gara A — sua ben nay doi ben kia');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$mot = function($sql, $bd = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($bd); return $st->fetch(PDO::FETCH_ASSOC); };
$so  = function($sql, $bd = []) use ($pdo){ $st = $pdo->prepare($sql); $st->execute($bd); return (int) $st->fetchColumn(); };

$cot = $pdo->query("SHOW COLUMNS FROM `groups`")->fetchAll(PDO::FETCH_COLUMN);
ok(in_array('garage_id', $cot, true), '`groups` co cot garage_id');
if (!in_array('garage_id', $cot, true)){ echo "\n[SKIP] Chua chay migration 000093.\n"; exit(summary()); }

/* `permissions` KHONG them garage_id — co y. No khoa theo group_id, ma nhom da
   thuoc ve mot gara. Hai nguon su that cho cung mot cau hoi thi som muon lech. */
ok(!in_array('garage_id', $pdo->query("SHOW COLUMNS FROM `permissions`")->fetchAll(PDO::FETCH_COLUMN), true),
   '`permissions` KHONG co garage_id (theo nhom, nhom da thuoc gara)');

$fk = $mot("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_groups_garage'");
ok(!empty($fk) && $fk['DELETE_RULE'] === 'CASCADE',
   'Khoa ngoai groups -> garages la CASCADE (nhom rieng khong con nghia khi gara mat)',
   json_encode($fk));

// --- 1. Nhóm KHÔNG còn dùng chung ---
section('1. Nhom khong con dung chung giua cac gara');

$lanGara = $pdo->query("SELECT g.name, COUNT(DISTINCT u.garage_id) n
                          FROM `groups` g JOIN `users` u ON u.group_id = g.id
                         WHERE g.garage_id IS NOT NULL
                      GROUP BY g.id HAVING n > 1")->fetchAll(PDO::FETCH_ASSOC);
ok(empty($lanGara), 'Khong nhom nao cua gara co nguoi thuoc HAI gara khac nhau',
   'Dang trai: ' . json_encode($lanGara));

$lech = $so("SELECT COUNT(*) FROM `users` u JOIN `groups` g ON g.id = u.group_id
              WHERE g.garage_id IS NOT NULL AND g.garage_id <> u.garage_id");
ok($lech === 0, 'Khong tai khoan nao dung nhom cua gara KHAC', "Con $lech tai khoan lech");

/* Moi gara dang hoat dong phai co du bo nhom. Thieu la chu gara do vao man
   Quan ly nhom thay bang trong. */
$thieuNhom = [];
foreach ($pdo->query("SELECT id, code FROM garages WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC) as $g){
    foreach (['Manager', 'Staff'] as $ten){
        if ($so("SELECT COUNT(*) FROM `groups` WHERE name = ? AND garage_id = ?", [$ten, (int) $g['id']]) === 0){
            $thieuNhom[] = $g['code'] . '/' . $ten;
        }
    }
}
ok(empty($thieuNhom), 'Moi gara dang hoat dong co du nhom Manager + Staff rieng',
   'Thieu: ' . implode(', ', $thieuNhom));

// --- 2. laToanQuyen chỉ nhận nhóm hệ thống ---
section('2. Manager co `permission` nhung KHONG phai toan quyen');

require_once $goc . 'app/models/GroupsModel.php';
$GM = new GroupsModel();

$idAdmin = $so("SELECT id FROM `groups` WHERE name = 'Admin' AND garage_id IS NULL LIMIT 1");
ok($idAdmin > 0, 'Nhom Admin la nhom HE THONG (garage_id IS NULL)',
   'Gan Admin vao mot gara la gara do thanh nguoi van hanh nen tang');

$mgrs = $pdo->query("SELECT g.id, ga.code FROM `groups` g JOIN garages ga ON ga.id = g.garage_id
                      WHERE g.name = 'Manager'")->fetchAll(PDO::FETCH_ASSOC);
ok(!empty($mgrs), 'Co nhom Manager cua gara de kiem');

ok($idAdmin === 0 || $GM->laToanQuyen($idAdmin), 'Admin (nhom he thong) la TOAN QUYEN');
foreach ($mgrs as $m){
    $q = $pdo->query("SELECT p.role FROM permissions p JOIN modules m ON m.id = p.module_id
                       WHERE p.group_id = " . (int) $m['id'] . " AND m.link = 'groups'")->fetchAll(PDO::FETCH_COLUMN);
    ok(in_array('view', $q, true) && in_array('permission', $q, true),
       "Manager cua {$m['code']} phan quyen duoc cho nhan vien minh", implode(',', $q));
    ok(!$GM->laToanQuyen((int) $m['id']),
       "Manager cua {$m['code']} KHONG phai toan quyen (du co role `permission`)",
       'Toan quyen thi gan duoc nhom Admin cho nhan vien — leo thang trong mot buoc');
}

// --- 3 + 4. Phạm vi sửa quyền ---
section('3+4. Chi sua duoc nhom YEU HON MINH, cung gara');

$dsGara = $pdo->query("SELECT id, code FROM garages WHERE status = 1 ORDER BY is_master DESC, id")->fetchAll(PDO::FETCH_ASSOC);
if (count($dsGara) < 2){
    echo "  [SKIP] Can it nhat hai gara de thu phan cach ly.\n";
} else {
    $g1 = (int) $dsGara[0]['id']; $g2 = (int) $dsGara[1]['id'];
    $M1 = $so("SELECT id FROM `groups` WHERE name = 'Manager' AND garage_id = ?", [$g1]);
    $S1 = $so("SELECT id FROM `groups` WHERE name = 'Staff'   AND garage_id = ?", [$g1]);
    $M2 = $so("SELECT id FROM `groups` WHERE name = 'Manager' AND garage_id = ?", [$g2]);
    $S2 = $so("SELECT id FROM `groups` WHERE name = 'Staff'   AND garage_id = ?", [$g2]);

    ok($M1 && $S1 && $M2 && $S2, 'Lay duoc bon nhom de thu');

    ok($GM->suaQuyenDuoc($M1, $S1), 'Manager sua duoc nhom Staff CUNG gara');
    ok(!$GM->suaQuyenDuoc($M1, $M1), 'Manager KHONG sua duoc nhom cua CHINH MINH (muc 3)');
    ok(!$GM->suaQuyenDuoc($M1, $S2), 'Manager KHONG sua duoc nhom Staff cua GARA KHAC');
    ok(!$GM->suaQuyenDuoc($M1, $M2), 'Manager KHONG sua duoc nhom Manager cua gara khac');
    ok(!$GM->suaQuyenDuoc($M1, $idAdmin), 'Manager KHONG sua duoc nhom he thong (Admin)');

    /* MUC 4 — duong vong qua nhan vien. Staff ma duoc cap `permission` thi cung
       khong ha duoc nhom Manager: nhom Manager khong yeu hon Staff. */
    ok(!$GM->suaQuyenDuoc($S1, $M1),
       'Staff KHONG sua duoc nhom Manager cung gara (muc 4 — duong vong qua nhan vien)',
       'Khong nang quyen cho minh, nhung bo tick duoc tới khi chu gara mat quyen');
    ok(!$GM->suaQuyenDuoc($S1, $S1), 'Staff KHONG sua duoc nhom cua chinh minh');

    ok($GM->suaQuyenDuoc($idAdmin, $M1) && $GM->suaQuyenDuoc($idAdmin, $idAdmin),
       'Toan quyen sua duoc moi nhom, ke ca nhom cua chinh minh',
       'Khong thi khong con ai sua duoc nhom Admin');

    /* Nhom RONG cung gara thi sua duoc — rong la yeu hon moi thu, va nhom vua
       tao thi rong. Khong cho sua la tao ra nhom khong bao gio cap duoc quyen. */
    $pdo->exec("DELETE p FROM permissions p JOIN `groups` g ON g.id = p.group_id WHERE g.name = 'ZZNQ-rong'");
    $pdo->exec("DELETE FROM `groups` WHERE name = 'ZZNQ-rong'");
    $pdo->prepare("INSERT INTO `groups` (name, garage_id, create_at) VALUES ('ZZNQ-rong', ?, NOW())")->execute([$g1]);
    $zRong = (int) $pdo->lastInsertId();
    ok($GM->suaQuyenDuoc($M1, $zRong), 'Manager cap duoc quyen cho nhom RONG cua gara minh',
       'Nhom vua tao thi rong; khong cho sua la nhom chet');
    $pdo->exec("DELETE FROM `groups` WHERE id = $zRong");
}

// --- 5. Nhóm rỗng / tài khoản không nhóm = KHÔNG có quyền ---
section('5. Nhom rong va tai khoan khong nhom = KHONG co quyen');

$rmw = codeOnly($goc . 'app/middlewares/RoleMiddleware.php');
ok(strpos($rmw, 'empty($permissionData)') !== false
   && preg_match('~if\s*\(\s*!empty\(\$currentModuleId\)\s*&&\s*empty\(\$permissionData\)\s*\)~', $rmw) === 1,
   'RoleMiddleware CHAN khi URL khop module ma nhom khong co dong quyen nao',
   'Truoc 08/10/2026 truong hop nay roi ra khoi moi nhanh kiem tra -> trang van ve ra');

$ctl = codeOnly($goc . 'app/controllers/admin/Groups.php');
ok(strpos($ctl, 'soNguoi') !== false,
   'Groups::delete() dem nguoi truoc khi xoa nhom',
   '`users`.`group_id` la ON DELETE SET NULL — xoa nhom con nguoi la ho mat nhom');
ok(strpos($ctl, 'luuChoNhom') !== false,
   'Groups::postPermission() luu bang luuChoNhom() (mot giao dich)',
   'Xoa sach roi chen lai ngoai giao dich: chen do giua duong la nhom thanh rong');

$pm = codeOnly($goc . 'app/models/PermissionsModel.php');
ok(strpos($pm, 'transaction') !== false, 'PermissionsModel::luuChoNhom() chay trong transaction');

/* Khong duoc de nhom nao RONG — ke ca do migration hay do nguoi dung bam luu. */
$nhomRong = $pdo->query("SELECT CONCAT(g.name, ' (', COALESCE(ga.code, 'he-thong'), ')')
                           FROM `groups` g LEFT JOIN garages ga ON ga.id = g.garage_id
                          WHERE NOT EXISTS (SELECT 1 FROM permissions p WHERE p.group_id = g.id)")
               ->fetchAll(PDO::FETCH_COLUMN);
ok(empty($nhomRong), 'Khong nhom nao dang rong quyen', 'Dang rong: ' . implode(', ', $nhomRong));

$khongNhom = $so("SELECT COUNT(*) FROM `users` WHERE group_id IS NULL");
ok($khongNhom === 0, 'Khong tai khoan nao mat nhom', "Co $khongNhom tai khoan group_id IS NULL");

// --- 6. Mở gara mới thì nhân bản nhóm ---
section('6. Mo gara moi thi nhan ban nhom');

$mo = codeOnly($goc . 'app/models/MoGaraModel.php');
ok(strpos($mo, 'nhanBanNhom') !== false, 'MoGaraModel dung bo nhom quyen cho gara moi');
ok(preg_match("~name.*=\s*'Manager'\s*AND\s*`garage_id`\s*=~", $mo) === 1,
   'MoGaraModel tim nhom Manager CUA CHINH GARA DO',
   'Lay nhom Manager dau tien tim thay la chu gara moi khong thay nhom nao cua minh');

// ---------------------------------------------------------------------------
section('Giao dien man Quan ly nhom');

$lv = file_get_contents($goc . 'app/views/admin/groups/lists.php');
ok(strpos($lv, 'garage_name') !== false,
   'Danh sach nhom co cot Gara',
   'Bon dong cung ten "Manager" ma khong co cot gara thi khong phan biet duoc');
ok(strpos($lv, "sua_duoc") !== false,
   'Nut Sua / Xoa / Phan quyen an theo quyen sua tung dong');

$pv = file_get_contents($goc . 'app/views/admin/groups/permission.php');
ok(strpos($pv, 'quyenCuaToi') !== false && strpos($pv, 'disabled') !== false,
   'Bang phan quyen KHOA o ngoai pham vi nguoi dang bam');
ok(strpos($pv, "type=\"hidden\"") !== false,
   'O bi khoa ma dang duoc tick thi kem mot `hidden` de luu lai khong lam mat',
   'O `disabled` khong duoc trinh duyet gui len — thieu hidden la moi lan luu mat sach quyen ngoai tam');
/* O khong co dau ngoac kep quanh `name` tung lam ten truong thanh
   `permission[9][]"`. PHP bo qua ky tu la nen van chay, nhung do la tinh co. */
ok(strpos($pv, 'name=permission[') === false,
   'Thuoc tinh name co dau ngoac kep day du',
   'Truoc day viet `name=permission[{{id}}][]"` — thieu dau mo ngoac kep');

echo "\n";
exit(summary());

<?php
/**
 * Test CHỦ GARA TỰ THÊM NHÓM QUYỀN.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChuGaraTaoNhomTest.php
 *
 * Khách ghi 25/11/2026 ở dòng "Quản lý tài khoản người dùng": "Thiếu nhóm và
 * phân quyền cho nhóm". Gara chỉ có sẵn hai nhóm Manager + Staff; chủ gara mở
 * Quản lý nhóm thì KHÔNG có nút Thêm nhóm (08/10 cố ý chỉ cấp view +
 * permission), nên muốn tách Kế toán / Thợ / Lễ tân là không làm được.
 *
 * Nay nhóm Manager của mọi gara có add / edit / delete trên màn Nhóm (migration
 * 000116), gara mở mới thừa hưởng qua nhân bản nhóm mẫu. Kèm một chốt mới:
 * KHÔNG TRÙNG TÊN NHÓM trong một gara — code tìm "nhóm Manager của gara" theo
 * tên (tạo / sửa tài khoản chủ gara), hai nhóm cùng tên Manager là chọn bừa.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/GroupsModel.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Man Nhom chan trung ten trong mot gara');

$ctl = codeOnly($goc . 'app/controllers/admin/Groups.php');
ok(preg_match('~function postAdd\(\).*?trungTen\(~s', $ctl) === 1, 'Them nhom: kiem trung ten');
ok(preg_match('~function postEdit\(\$id\).*?trungTen\(~s', $ctl) === 1, 'Sua ten nhom: kiem trung ten');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$G = new GroupsModel();
ok(method_exists($G, 'trungTen'), 'GroupsModel co trungTen()');
if (method_exists($G, 'trungTen')){
    $mg = $pdo->query("SELECT id, garage_id FROM `groups` WHERE name = 'Manager' AND garage_id IS NOT NULL
                       ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $g  = (int) $mg['garage_id'];
    ok($G->trungTen('Manager', $g) === true, 'Ten "Manager" da co trong gara -> trung');
    ok($G->trungTen('  manager ', $g) === true, 'Khong phan biet hoa thuong, bo khoang trang');
    ok($G->trungTen('Manager', $g, (int) $mg['id']) === false, 'Sua chinh nhom do, giu ten -> khong tinh la trung');
    ok($G->trungTen('ZZ Nhom Chua Co ' . mt_rand(), $g) === false, 'Ten moi -> khong trung');
    $khac = (int) $pdo->query("SELECT id FROM garages WHERE id <> $g ORDER BY id LIMIT 1")->fetchColumn();
    $coTen = (int) $pdo->query("SELECT COUNT(*) FROM `groups` WHERE name = 'Ke toan ZZ' AND garage_id = $khac")->fetchColumn();
    ok($coTen === 0 && $G->trungTen('Ke toan ZZ', $g) === false, 'Gara khac co ten do cung khong tinh (moi gara mot bo nhom)');
}

// ---------------------------------------------------------------------------
section('Moi nhom Manager cua gara co du quyen tren man Nhom');

$thieu = $pdo->query("
    SELECT g.id, g.garage_id, r.role
      FROM `groups` g
      CROSS JOIN (SELECT 'view' role UNION SELECT 'add' UNION SELECT 'edit'
                  UNION SELECT 'delete' UNION SELECT 'permission') r
      JOIN modules m ON m.link = 'groups'
      LEFT JOIN permissions p ON p.group_id = g.id AND p.module_id = m.id AND p.role = r.role
     WHERE g.name = 'Manager' AND g.garage_id IS NOT NULL AND p.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
ok(empty($thieu), 'Nhom Manager nao cung co view/add/edit/delete/permission tren `groups`', json_encode($thieu));

$staffCo = (int) $pdo->query("SELECT COUNT(*) FROM permissions p JOIN `groups` g ON g.id = p.group_id
                               JOIN modules m ON m.id = p.module_id
                              WHERE g.name = 'Staff' AND m.link = 'groups'")->fetchColumn();
ok($staffCo === 0, 'Nhom Staff van KHONG co quyen nao tren man Nhom (chong tu nang quyen)');

exit(summary());

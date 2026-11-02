<?php
/**
 * Test: NHÀ CUNG CẤP THUẦN KHÔNG KHAI XE.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NccKhongKhaiXeTest.php
 *
 * VÌ SAO CÓ TEST NÀY (31/10/2026): màn Đối tượng dựng chung cho cả khách và
 * NCC, nên khối "Xe của khách" + nút "Thêm xe cho khách này" hiện ra kể cả khi
 * đang khai một nhà cung cấp phụ tùng. Người khai tưởng phải khai cả xe cho
 * NCC. Thêm NCC xong hệ thống còn đẩy thẳng sang màn Sửa kèm lời nhắc "Khai xe
 * của khách ngay bên dưới" — bắt làm một việc không tồn tại.
 *
 * BA LỚP, LỚP TRONG MỚI LÀ LUẬT:
 *   1. View ẩn khối theo loại (lớp che ngoài, đổi loại là hiện/ẩn ngay).
 *   2. Partners::postAdd() không đẩy NCC sang màn Sửa nữa.
 *   3. Vehicles::validate() KHÔNG nhận NCC thuần làm chủ xe — link
 *      vehicles/add?partner_id=... và POST tay đều không luồn qua được.
 *
 * CHỖ DỄ SỬA HỎNG: đối tượng ĐÃ LỠ có xe thì khối vẫn phải hiện, nếu không là
 * giấu mất dữ liệu đã khai mà không còn đường nào sửa.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Man Doi tuong — khoi Xe cua khach an khi la NCC');

$edit = file_get_contents($goc . 'app/views/admin/partners/edit.php');

ok(strpos($edit, 'data-o-xe-khach') !== false,
   'Khoi "Xe cua khach" co moc data-o-xe-khach de JS bat duoc');
ok(preg_match('/\$anXe\s*=\s*\(\$selType\s*===\s*\'supplier\'\s*&&\s*!\$coXe\)/', $edit) === 1,
   'An NGAY TU SERVER khi la NCC — khong doi JS chay moi an',
   'Neu doi JS thi khoi nhap nhay mot cai roi moi bien mat');
ok(preg_match('/\$coXe\s*=\s*!empty\(\$xeDs\)/', $edit) === 1,
   'Co bien $coXe doc tu danh sach xe that');
ok(strpos($edit, "data-co-xe=\"{{\$coXe ? 1 : 0}}\"") !== false,
   'Truyen $coXe xuong JS qua data-co-xe');
ok(strpos($edit, 'if (oXe && !coXe)') !== false,
   'DA CO XE THI KHONG AN — an di la giau mat du lieu da khai');
ok(strpos($edit, "oNhom.style.display") !== false,
   'Van giu viec an o "Nhom khach" cho NCC (khong lam hong cai cu)');

// Mot flash dung cho ca bao loi lan bao thanh cong — dung ve do cho moi truong hop
ok(strpos($edit, "!empty(\$errors) ? 'alert-danger' : 'alert-success'") !== false,
   'Loi bao THANH CONG khong con ve mau do nhu mot loi');

// ---------------------------------------------------------------------------
section('Man Them doi tuong — loi nhac khai xe an theo loai');

$add = file_get_contents($goc . 'app/views/admin/partners/add.php');
ok(strpos($add, 'data-o-xe-khach') !== false,
   'Loi nhac "Luu khach xong se mo khoi Xe cua khach" co moc de an');
ok(preg_match('/\$selType\s*===\s*\'supplier\'\s*\?\s*\' style="display:none"\'/', $add) === 1,
   'Chon san NCC (quay lai sau khi loi) thi loi nhac an ngay tu server');

// ---------------------------------------------------------------------------
section('Them NCC xong khong bi day sang man Sua');

$pt = codeOnly($goc . 'app/controllers/admin/Partners.php');
ok(preg_match('/postAdd\(\).*?\$dl\s*=\s*\$this->buildData\(\)/s', $pt) === 1,
   'postAdd() giu lai du lieu vua dung de biet loai gi');
ok(preg_match('/if\s*\(\$dl\[\'type\'\]\s*===\s*\'supplier\'\)\s*\{.*?redirect\(\'admin\/\'\s*\.\s*\$this->routeBase\)/s', $pt) === 1,
   'NCC: ve thang danh sach, KHONG vao man Sua');
// Cau nhac van con cho khach — khong vo tinh bo mat tinh nang cu
$ptFull = file_get_contents($goc . 'app/controllers/admin/Partners.php');
ok(strpos($ptFull, 'Khai xe của khách ngay bên dưới.') !== false,
   'Khach van duoc dan sang man Sua de khai xe (tinh nang cu con nguyen)');

// Nhanh supplier phai nam TRUOC cau nhac khai xe trong cung ham
$viTriNcc  = strpos($pt, "\$dl['type'] === 'supplier'");
$viTriNhac = strpos($pt, 'Khai xe c');
ok($viTriNcc !== false && $viTriNhac !== false && $viTriNcc < $viTriNhac,
   'Nhanh NCC chan TRUOC cau nhac khai xe');

// ---------------------------------------------------------------------------
section('Luat that: NCC thuan khong nhan lam chu xe');

$ve = codeOnly($goc . 'app/controllers/admin/Vehicles.php');
ok(strpos($ve, "in_array(\$chu['type'], ['customer', 'both'], true)") !== false,
   'Vehicles::validate() chi nhan chu xe loai customer / both',
   'Day moi la luat that — view chi la lop che ngoai');
ok(strpos($ve, "\$this->__partner->getLists(['type' => 'customer'") !== false,
   'O chon chu xe cung chi liet ke khach (customer + both)');
ok(preg_match('/\$chu\s*=\s*\$this->__partner->getDetail/', $ve) === 1,
   'Doc ban ghi chu xe ra roi moi xet loai (khong chi kiem ton tai)');

// ---------------------------------------------------------------------------
section('CSKH van la noi cham soc khach, khong dinh toi NCC');

$cus = codeOnly($goc . 'app/controllers/admin/Customers.php');
ok(strpos($cus, "in_array(\$p['type'], ['customer', 'both'], true)") !== false,
   'Man CSKH tu loc bo NCC thuan (da dung tu truoc, giu nguyen)');

// ---------------------------------------------------------------------------
section('Du lieu that: khong co xe nao thuoc NCC thuan');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $r = $pdo->query("SELECT COUNT(*) FROM `vehicles` `v`
                        JOIN `partners` `p` ON `p`.`id` = `v`.`partner_id`
                       WHERE `p`.`type` = 'supplier'")->fetchColumn();
    ok((int) $r === 0, 'Khong co xe nao dang treo duoi mot NCC thuan',
       'Dang co ' . (int) $r . ' xe — doi loai doi tuong do sang "Ca hai" hoac go xe ra');
} catch (Exception $e) {
    echo "  [BO QUA] Khong noi duoc CSDL: " . $e->getMessage() . "\n";
}

exit(summary());

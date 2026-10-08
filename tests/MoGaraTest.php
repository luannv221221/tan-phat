<?php
/**
 * Test MỞ MỘT GARA MỚI — bước 7, bước cuối của nền tảng nhiều gara.
 *
 * Chạy:  C:\xampp\php\php.exe tests\MoGaraTest.php
 *
 * Tạo xong một dòng trong bảng `garages` thì gara đó vẫn CHƯA LÀM ĐƯỢC GÌ:
 * chưa có tên miền nên không có website, chưa có kho nên không nhập hàng được,
 * chưa có tài khoản nên không ai đăng nhập được, và trang web mang tên Tân Phát
 * vì chưa khai cấu hình riêng.
 *
 * Trước đây phải đi năm màn khác nhau để khai đủ, quên một thứ thì tới lúc dùng
 * mới biết. Nay tạo gara là dựng luôn cả năm.
 *
 * HAI ĐIỀU TEST NÀY GÁC KỸ NHẤT:
 *
 *   1. CHẠY LẠI KHÔNG SINH BẢN SAO. Gara mở dở dang rồi sửa tay là chuyện
 *      thường; gọi lại hàm dựng khung không được đẻ ra kho thứ hai, nhóm khách
 *      thứ hai, tên miền thứ hai.
 *
 *   2. VIỆC PHỤ HỎNG KHÔNG ĐƯỢC LÀM ĐỔ VIỆC CHÍNH. Gara đã tạo xong rồi mới
 *      tới bước dựng khung. Để một bước phụ (email chủ gara trùng người khác)
 *      ném lỗi là người dùng thấy "thêm gara thất bại" trong khi gara THẬT RA
 *      ĐÃ CÓ — rồi họ bấm thêm lần nữa và ra hai gara.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/MoGaraModel.php';

use App\core\Model;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Noi vao luong tao gara');

$ctl = codeOnly($goc . 'app/controllers/admin/Garages.php');
ok(strpos($ctl, 'MoGaraModel') !== false, 'Garages::postAdd goi MoGaraModel');
ok(strpos($ctl, "empty(\$data['is_master'])") !== false,
   'Gara TONG thi bo qua — no da co san moi thu tu lau');

$v = file_get_contents($goc . 'app/views/admin/garages/add.php');
foreach (['chu_name', 'chu_email', 'chu_password'] as $o){
    ok(strpos($v, 'name="' . $o . '"') !== false, "Form co o `$o`");
}

// ---------------------------------------------------------------------------
try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$donSach = function() use ($pdo){
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zzmg-%@local.test'");
    foreach ($pdo->query("SELECT id FROM garages WHERE code LIKE 'ZZMG%'")->fetchAll(PDO::FETCH_COLUMN) as $g){
        $g = (int) $g;
        $pdo->exec("DELETE FROM garage_domains  WHERE garage_id = $g");
        $pdo->exec("DELETE FROM garage_settings WHERE garage_id = $g");
        $pdo->exec("DELETE FROM customer_groups WHERE garage_id = $g");
        $pdo->exec("DELETE FROM warehouses      WHERE garage_id = $g");
        $pdo->exec("DELETE FROM users           WHERE garage_id = $g");
        $pdo->exec("DELETE FROM garages         WHERE id = $g");
    }
};
$donSach();
register_shutdown_function(function() use ($donSach){ Model::epGara(null); $donSach(); });

$taoGara = function($ma, $ten) use ($pdo){
    $pdo->prepare("INSERT INTO garages (code, name, address, phone, is_master, status, sort_order, create_at)
                   VALUES (?, ?, '12 Duong Thu', '0909888777', 0, 1, 99, NOW())")->execute([$ma, $ten]);
    return (int) $pdo->lastInsertId();
};

// ---------------------------------------------------------------------------
section('Dung bo khung cho gara moi');

$M  = new MoGaraModel();
$id = $taoGara('ZZMG1', 'ZZMG Gara Mot');
$kq = $M->dungBoKhung($id, ['name' => 'ZZMG Chu', 'email' => 'zzmg-chu@local.test', 'password' => 'MatKhau@123']);

ok(empty($kq['thieu']), 'Dung duoc het, khong thieu gi', implode(' | ', $kq['thieu']));

$host = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $id")->fetchColumn();
ok($host !== false && strpos($host, 'zzmg1.') === 0,
   'Co ten mien phu theo ma gara', (string) $host);
/* Tên miền gốc SUY TỪ gara tổng, không viết cứng — đổi tên miền hệ thống thì
   chỉ sửa dòng trong `garage_domains`, không phải sửa code. */
$gocTong = $pdo->query("SELECT host FROM garage_domains d JOIN garages g ON g.id = d.garage_id
                        WHERE g.is_master = 1 ORDER BY LENGTH(host) ASC LIMIT 1")->fetchColumn();
ok($host === 'zzmg1.' . $gocTong, 'Ten mien goc suy tu gara tong', (string) $gocTong);

ok((int) $pdo->query("SELECT COUNT(*) FROM warehouses WHERE garage_id = $id AND is_default = 1")->fetchColumn() === 1,
   'Co dung MOT kho mac dinh');
ok((int) $pdo->query("SELECT COUNT(*) FROM customer_groups WHERE garage_id = $id")->fetchColumn() === 1,
   'Co nhom khach mac dinh');
ok($pdo->query("SELECT svalue FROM garage_settings WHERE garage_id = $id AND skey = 'site_name'")->fetchColumn()
   === 'ZZMG Gara Mot',
   'Cau hinh web mang TEN GARA, khong con mang ten Tan Phat');

$u = $pdo->query("SELECT u.email, g.name FROM users u JOIN `groups` g ON g.id = u.group_id
                  WHERE u.garage_id = $id")->fetch(PDO::FETCH_ASSOC);
ok(!empty($u) && $u['email'] === 'zzmg-chu@local.test' && $u['name'] === 'Manager',
   'Co tai khoan chu gara, nhom Manager', json_encode($u));

// ---------------------------------------------------------------------------
section('Ten mien sinh ra phai HOP LE va dung goc');

/* HAI LOI DA MAC THAT, phat hien khi mo gara that tren may chu:

   1. MA GARA KHONG DUOC GOT. Ban dau chi strtolower(trim()), nen ma co dau
      cach / dau tieng Viet / gach duoi sinh ra host sai: "Long Biên" thanh
      "long biên.etek..." — dia chi khong ton tai. Gara tao xong nhin nhu binh
      thuong, toi luc mo ten mien moi thay "Khong tim thay gara".

   2. CHON TEN MIEN GOC BANG "IT DAU CHAM NHAT". Them mot host de chay thu nhu
      `tp01.localhost` (1 dau cham) la no thang `etek.rikkeiedu.org` (2 dau
      cham), gara moi nhan ten mien `<ma>.tp01.localhost`. Van bao "thanh cong",
      chi co dieu dia chi do khong ai vao duoc. */

$gocThat = $pdo->query("SELECT d.host FROM garage_domains d JOIN garages g ON g.id = d.garage_id
                        WHERE g.is_master = 1 ORDER BY d.is_primary DESC, d.id ASC LIMIT 1")->fetchColumn();
$maTong  = $pdo->query("SELECT code FROM garages WHERE is_master = 1")->fetchColumn();
$goc     = (strpos((string) $gocThat, strtolower($maTong) . '.') === 0)
         ? substr($gocThat, strlen($maTong) + 1) : $gocThat;

foreach ([
    'ZZMG Long Biên' => 'zzmg-long-bien',
    'ZZMG_LB'        => 'zzmglb',
    'ZZMG-LB2'       => 'zzmg-lb2',
] as $ma => $nhanMong){
    $idT = $taoGara($ma, 'Gara ' . $ma);
    $M->dungBoKhung($idT, []);
    $h = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $idT")->fetchColumn();
    ok($h === $nhanMong . '.' . $goc,
       "Ma \"$ma\" -> ten mien hop le",
       'thuc te: ' . var_export($h, true) . ' | mong doi: ' . $nhanMong . '.' . $goc);
    /* Khong duoc co dau cach hay chu co dau trong host */
    ok(is_string($h) && preg_match('~^[a-z0-9.-]+$~', $h) === 1,
       "Ma \"$ma\" -> host chi gom chu thuong, so, gach noi, dau cham", (string) $h);
}

/* Them mot host NGAN HON cho gara tong roi mo gara moi: goc KHONG duoc doi. */
$pdo->prepare("INSERT INTO garage_domains (garage_id, host, is_primary, status, create_at)
               SELECT id, 'zzmg-ngan.test', 0, 1, NOW() FROM garages WHERE is_master = 1")->execute();
$idN = $taoGara('ZZMG9', 'ZZMG Gara Chin');
$M->dungBoKhung($idN, []);
$hN = $pdo->query("SELECT host FROM garage_domains WHERE garage_id = $idN")->fetchColumn();
ok($hN === 'zzmg9.' . $goc,
   'Them host ngan hon cho gara tong KHONG cuop mat ten mien goc',
   'thuc te: ' . var_export($hN, true));
$pdo->exec("DELETE FROM garage_domains WHERE host = 'zzmg-ngan.test'");

// ---------------------------------------------------------------------------
section('Chay lai KHONG sinh ban sao');

$M->dungBoKhung($id, ['name' => 'ZZMG Chu', 'email' => 'zzmg-chu@local.test', 'password' => 'MatKhau@123']);
ok((int) $pdo->query("SELECT COUNT(*) FROM garage_domains WHERE garage_id = $id")->fetchColumn() === 1,
   'Van dung mot ten mien');
ok((int) $pdo->query("SELECT COUNT(*) FROM warehouses WHERE garage_id = $id")->fetchColumn() === 1,
   'Van dung mot kho');
ok((int) $pdo->query("SELECT COUNT(*) FROM customer_groups WHERE garage_id = $id")->fetchColumn() === 1,
   'Van dung mot nhom khach');
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE garage_id = $id")->fetchColumn() === 1,
   'Van dung mot tai khoan');

// ---------------------------------------------------------------------------
section('Viec phu hong KHONG lam do viec chinh');

$id2 = $taoGara('ZZMG2', 'ZZMG Gara Hai');
/* Email đã thuộc về chủ gara 1 -> không tạo được tài khoản, nhưng phần còn
   lại VẪN phải dựng xong. */
$kq2 = $M->dungBoKhung($id2, ['name' => 'ZZMG Chu 2', 'email' => 'zzmg-chu@local.test', 'password' => 'MatKhau@123']);

ok(!empty($kq2['thieu']), 'Bao ro la con thieu, khong im lang');
ok(mb_stripos(implode(' ', $kq2['thieu']), 'đã có người dùng') !== false,
   'Noi dung ly do: email da co nguoi dung', implode(' | ', $kq2['thieu']));
ok((int) $pdo->query("SELECT COUNT(*) FROM warehouses WHERE garage_id = $id2")->fetchColumn() === 1,
   'Kho VAN duoc dung du tai khoan hong');
ok((int) $pdo->query("SELECT COUNT(*) FROM garage_domains WHERE garage_id = $id2")->fetchColumn() === 1,
   'Ten mien VAN duoc dung');
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE garage_id = $id2")->fetchColumn() === 0,
   'Va KHONG tao tai khoan nao bang email trung');

// ---------------------------------------------------------------------------
section('Chan tai khoan chu gara khong hop le');

$id3 = $taoGara('ZZMG3', 'ZZMG Gara Ba');
$thu = function($chu) use ($M, $id3){
    $r = $M->dungBoKhung($id3, $chu);
    return implode(' ', $r['thieu']);
};
ok(mb_stripos($thu(['name' => 'A', 'email' => 'khong-phai-email', 'password' => 'MatKhau@123']), 'định dạng') !== false,
   'Email sai dinh dang -> bao ro');
ok(mb_stripos($thu(['name' => 'A', 'email' => 'zzmg-b@local.test', 'password' => '123']), '6 ký tự') !== false,
   'Mat khau ngan -> bao ro');
ok(mb_stripos($thu(['name' => '', 'email' => 'zzmg-c@local.test', 'password' => 'MatKhau@123']), 'cần đủ') !== false,
   'Thieu ho ten -> bao ro');
ok((int) $pdo->query("SELECT COUNT(*) FROM users WHERE garage_id = $id3")->fetchColumn() === 0,
   'Khong truong hop nao lot qua');

/* Để trống cả ba ô thì KHÔNG phải lỗi — gara vẫn tạo, cấp tài khoản sau. */
$id4 = $taoGara('ZZMG4', 'ZZMG Gara Bon');
$kq4 = $M->dungBoKhung($id4, []);
ok(empty($kq4['thieu']), 'Bo trong ca ba o -> khong bao thieu gi', implode(' | ', $kq4['thieu']));
ok((int) $pdo->query("SELECT COUNT(*) FROM warehouses WHERE garage_id = $id4")->fetchColumn() === 1,
   'Va van dung du phan con lai');

$donSach();
ok((int) $pdo->query("SELECT COUNT(*) FROM garages WHERE code LIKE 'ZZMG%'")->fetchColumn() === 0,
   'Da don sach du lieu test');

exit(summary());

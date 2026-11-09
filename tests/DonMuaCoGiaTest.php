<?php
/**
 * Test ĐƠN MUA CÓ GIÁ — đơn giá, thành tiền, tổng cộng.
 *
 * Chạy:  C:\xampp\php\php.exe tests\DonMuaCoGiaTest.php
 *
 * VÌ SAO CÓ TEST NÀY (31/10/2026): tài liệu khách gửi 29/10 ghi "không cần giá
 * nhập", nên đơn mua dựng ra chỉ có số lượng + serial. Khách mở màn hình lên
 * và hỏi lại "đơn mua sao không có giá nhỉ". Đúng — không có tiền thì không
 * duyệt được mua bao nhiêu, cũng không đối chiếu được với tờ hoá đơn NCC dán
 * ngay bên trên.
 *
 * BA CHỖ DỄ HỎNG, mỗi chỗ một mục dưới đây:
 *
 *   1. ĐƠN CŨ PHẢI CHẠY NHƯ CŨ. Mọi đơn lập trước 31/10 có unit_price = 0.
 *      Nếu phiếu nhập cứ lấy thẳng giá trên đơn thì những đơn đó nhập kho với
 *      giá vốn 0 — bán ra là báo cáo lãi gộp thổi lên bằng đúng doanh thu.
 *      Nên 0 phải RƠI VỀ giaNhapGanNhat().
 *
 *   2. GÕ "1.450.000" PHẢI RA 1450000. Ô số lượng lọc bằng /[^\d.]/ (giữ dấu
 *      chấm vì 2.5 lít dầu là thật). Dùng y hệt cho ô tiền thì "1.450.000"
 *      thành 1.45 — sai một triệu lần mà không có lỗi nào báo.
 *
 *   3. TỔNG BÊN ĐƠN CHỈ LÀ SỐ CHÉP LẠI. Nguồn sự thật là tổng `amount` của
 *      dòng hàng. Cộng ở hai nơi khác nhau là có ngày lệch.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration 000103 them du ba cot');

$mg = glob($goc . 'database/migrations/*_000103_*.php');
ok(count($mg) === 1, 'Co dung mot migration 000103', 'Tim thay ' . count($mg) . ' file');
$sqlMg = count($mg) === 1 ? file_get_contents($mg[0]) : '';

foreach ([['purchase_order_items', 'unit_price'], ['purchase_order_items', 'amount'],
          ['purchase_orders', 'total_amount']] as $c){
    ok(strpos($sqlMg, '`' . $c[1] . '`') !== false, 'Migration them `' . $c[0] . '`.`' . $c[1] . '`');
}
ok(substr_count($sqlMg, 'hasColumn') >= 4, 'Co kiem cot truoc khi them — chay lai duoc');
ok(strpos($sqlMg, 'DEFAULT 0') !== false,
   'Cot de DEFAULT 0 chu khong NULL',
   'Mot duong roi (0 = chua khai gia), khong phai hai (0 va NULL)');

// ---------------------------------------------------------------------------
section('Model ghi don gia va thanh tien');

$mdlIt = codeOnly($goc . 'app/models/PurchaseOrderItemsModel.php');
ok(strpos($mdlIt, "'unit_price' => \$gia") !== false, 'syncForPo() ghi `unit_price`');
ok(preg_match("~'amount'\s*=> \\\$tt~", $mdlIt) === 1, 'syncForPo() ghi `amount`');
ok(strpos($mdlIt, 'if ($gia < 0) $gia = 0;') !== false,
   'Don gia AM bi keo ve 0',
   'Mot dong am du lon la don trieu dong thanh don vai nghin, khong ai nhin ra');
/* 09/11/2026 — cong thuc them chiet khau (migration 000106). Don cu khong doi
   mot dong nao: discount_percent mac dinh 0 nen (1 - 0/100) = 1.
   Phep thu CON SO cho ca hai duong nam o VatMuaHangVaGiaWebTest. */
ok(preg_match('~round\(\$gia \* \$qty \* \(1 - \$ck / 100\), 2\)~', $mdlIt) === 1,
   'Thanh tien = don gia x so luong x (1 - CK%), lam tron 2 so');
ok(strpos($mdlIt, 'function tongTheoPo') !== false, 'Model co tongTheoPo()');
ok(strpos($mdlIt, 'COALESCE(SUM(`amount`), 0)') !== false,
   'tongTheoPo() cong tu dong hang chu khong doc total_amount',
   'total_amount chi la so chep lai cho man danh sach');

// ---------------------------------------------------------------------------
section('Controller doc gia tu form va chot tong');

$ctl = codeOnly($goc . 'app/controllers/admin/Purchaseorders.php');
ok(strpos($ctl, "\$f['price'][\$i]") !== false, 'Doc o don gia tu form (`price[]`)');
ok(strpos($ctl, "preg_replace('/[^\\d]/', '', (string) \$f['price'][\$i])") !== false,
   'O tien bo MOI ky tu khong phai chu so',
   'Giu dau cham nhu o so luong thi "1.450.000" thanh 1.45');
ok(strpos($ctl, 'function chotTong') !== false, 'Controller co chotTong()');
ok(substr_count($ctl, '$this->chotTong(') === 2,
   'Ghi lai tong sau CA HAI duong luu (them moi va sua)',
   'Thieu mot duong la tong tren man danh sach dung yen o so cu');
ok(strpos($ctl, '$this->__item->tongTheoPo($id)') !== false,
   'Tong doc lai tu CSDL, khong cong tu mang vua gui len');

// ---------------------------------------------------------------------------
section('Tao phieu nhap: gia tren don di truoc, trong moi roi ve gia gan nhat');

ok(preg_match('/\$gia\s*=\s*\(float\)\s*\(isset\(\$d\[\'unit_price\'\]\).*?\)\;\s*\n\s*if\s*\(\$gia\s*<=\s*0\)\s*\$gia\s*=\s*\$this->__model->giaNhapGanNhat/s', $ctl) === 1,
   'Lay don gia tren don TRUOC, <= 0 moi roi ve giaNhapGanNhat()');
ok(strpos($ctl, 'giaNhapGanNhat(') !== false,
   'VAN GIU duong roi giaNhapGanNhat() — don cu deu di duong nay',
   'Bo di la moi don lap truoc 31/10 nhap kho voi gia von 0');

// ---------------------------------------------------------------------------
section('Man hinh co cot Don gia / Thanh tien / Tong cong');

foreach (['add', 'edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/purchase-orders/' . $v . '.php');
    ok(strpos($f, '>Đơn giá<') !== false,   $v . '.php: co cot "Don gia"');
    ok(strpos($f, '>Thành tiền<') !== false, $v . '.php: co cot "Thanh tien"');
    ok(strpos($f, 'id="tong-tien"') !== false, $v . '.php: co dong "Tong cong"');
    ok(strpos($f, "gia.name = 'price[]'") !== false, $v . '.php: o gia gui len ten `price[]`');
    ok(strpos($f, 'window.oTien(gia)') !== false,
       $v . '.php: o gia dung oTien() — khong am, buoc 1 dong, VAN DE TRONG DUOC');
    ok(strpos($f, "oTt.textContent = g > 0 ? tt.toLocaleString('vi-VN') : '—'") !== false,
       $v . '.php: chua khai gia thi hien dau — chu khong hien so 0',
       'Trong = "NCC chua bao gia", khac han 0 dong');
}

$lst = file_get_contents($goc . 'app/views/admin/purchase-orders/lists.php');
ok(strpos($lst, 'Tổng tiền') !== false, 'lists.php: co cot "Tong tien"');
ok(strpos($lst, "colspan=\"8\"") !== false,
   'lists.php: dong "chua co don nao" trai du 8 cot',
   'Them cot ma quen colspan thi hang do thieu mot o');

// ---------------------------------------------------------------------------
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$cot = function($bang) use ($pdo){
    $ra = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `$bang`") as $r) $ra[$r['Field']] = $r;
    return $ra;
};

$ci = $cot('purchase_order_items');
ok(isset($ci['unit_price']), 'CSDL co `purchase_order_items`.`unit_price`');
ok(isset($ci['amount']),     'CSDL co `purchase_order_items`.`amount`');
$co = $cot('purchase_orders');
ok(isset($co['total_amount']), 'CSDL co `purchase_orders`.`total_amount`');

if (isset($ci['unit_price'])){
    ok(stripos($ci['unit_price']['Type'], 'decimal(15,2)') !== false,
       'unit_price la DECIMAL(15,2) — dung khuon voi goods_receipt_items.unit_cost',
       'Type that: ' . $ci['unit_price']['Type']);
    ok($ci['unit_price']['Null'] === 'NO' && (string) $ci['unit_price']['Default'] === '0.00',
       'unit_price NOT NULL DEFAULT 0 — don cu tu co gia 0, khong phai NULL');
}

/* TONG BEN DON PHAI KHOP TONG DONG HANG. Lech la mot trong hai duong luu
   quen goi chotTong() — man danh sach se bao mot so, mo don ra lai mot so. */
$lech = $pdo->query(
    "SELECT `po`.`id`, `po`.`po_no`, `po`.`total_amount`,
            COALESCE((SELECT SUM(`amount`) FROM `purchase_order_items` WHERE `po_id` = `po`.`id`), 0) AS `tong_dong`
       FROM `purchase_orders` `po`
      HAVING ABS(`total_amount` - `tong_dong`) > 0.01")->fetchAll(PDO::FETCH_ASSOC);
$mo = [];
foreach ($lech as $l) $mo[] = $l['po_no'] . ' (don ' . $l['total_amount'] . ' / dong ' . $l['tong_dong'] . ')';
ok(count($lech) === 0, 'Tong tren don khop tong dong hang o moi don mua',
   count($lech) . ' don lech: ' . implode('; ', $mo));

exit(summary());

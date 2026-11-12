<?php
/**
 * Test BẢNG TÍNH GIÁ — giá vốn + giá bán theo từng loại khách.
 *
 * Chạy:  C:\xampp\php\php.exe tests\BangTinhGiaTest.php
 *
 * VÌ SAO CÓ TEST NÀY (11/11/2026): khách chiếu một sơ đồ vẽ tay trong buổi
 * họp, kèm hai bộ số cụ thể. Test này ĐI THEO ĐÚNG HAI BỘ SỐ ĐÓ — nếu công
 * thức sau này bị sửa lệch, con số trong sơ đồ không ra nữa và test gãy.
 *
 * Sơ đồ khách:
 *   Mua hàng   phiếu 1: gạt mưa  5 × 1,0tr = 5tr ; dầu nhớt 3 × 2,0tr = 6tr
 *              phiếu 2: gạt mưa 10 × 1,2tr = 12tr; dầu nhớt 5 × 2,2tr = 11tr
 *   Giá vốn    gần nhất  : gạt mưa 1,2tr    ; dầu nhớt 2,2tr
 *              bình quân : gạt mưa 17/15 = 1,13tr ; dầu nhớt 17/8 = 2,125tr
 *   Loại khách vip 10% | đại lý 15% | lẻ 20%
 *   Giá bán    "gạt mưa: 1,2t × 20% + giá vốn"  (tức giá vốn × 1,20)
 *
 * BỐN CHỖ DỄ HỎNG, mỗi chỗ một mục dưới đây:
 *
 *   1. CÔNG THỨC VIẾT Ở HAI NƠI. Màn hình, lúc tính lại và JS của màn báo giá
 *      đều phải ra một con số. Nên chỉ có MỘT hàm tinhGiaBan().
 *
 *   2. GIÁ VỐN 0 ≠ GIÁ BÁN 0. Mã chưa từng nhập kho thì chưa có giá vốn. Ghi
 *      giá bán 0 rồi để màn báo giá lấy xuống là bán 0 đồng, không ai nhìn ra.
 *
 *   3. `parts`.`price` KHÔNG PHẢI GIÁ VỐN — nó là giá bán niêm yết. Lấy nó làm
 *      giá vốn rồi cộng 20% là mỗi lần bấm "Tính lại" giá trèo lên một bậc.
 *
 *   4. HAI GARA HAI BẢNG GIÁ. Cùng một mã của kho tổng, gara A lãi 20%, gara B
 *      lãi 15% — phải là hai dòng, không đè lên nhau.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration 000107 dung du bang va cot');

$mg = glob($goc . 'database/migrations/*_000107_*.php');
ok(count($mg) === 1, 'Co dung mot migration 000107', 'Tim thay ' . count($mg) . ' file');
$sqlMg = count($mg) === 1 ? file_get_contents($mg[0]) : '';

ok(strpos($sqlMg, '`part_prices`') !== false,       'Migration tao bang `part_prices`');
ok(strpos($sqlMg, '`markup_percent`') !== false,    'Migration them `customer_groups`.`markup_percent`');
ok(strpos($sqlMg, '`cost_extra`') !== false,        'Migration them `parts`.`cost_extra`');
ok(strpos($sqlMg, "'part-prices'") !== false,       'Migration dang ky module `part-prices`');
ok(strpos($sqlMg, "'products'") !== false,
   'Quyen chep tu module `products`',
   'Khong chep quyen thi moi gara phai tich tay, va se tuong man hinh bi loi');
ok(strpos($sqlMg, "'binh_quan'") !== false,
   'Mac dinh `cost_method` = binh_quan',
   'Mac dinh khac la doi gia von cua moi gara ma khong ai yeu cau');
ok(substr_count($sqlMg, 'hasColumn') >= 2, 'Co kiem cot truoc khi them — chay lai duoc');
ok(strpos($sqlMg, 'function hasColumn') !== false,
   'Migration TU KHAI hasColumn()',
   'Lop cha Migration chi co hasTable(); thieu la loi "Call to undefined method"');

// ---------------------------------------------------------------------------
section('Cong thuc gia ban — mot cho duy nhat');

$mdl = codeOnly($goc . 'app/models/PartPricesModel.php');
ok(strpos($mdl, 'function tinhGiaBan') !== false, 'Model co tinhGiaBan()');
ok(preg_match('~round\(\(\$giaVon \+ \$chiPhi\) \* \(1 \+ \$heSo / 100\), 0\)~', $mdl) === 1,
   'Gia ban = (gia von + chi phi) x (1 + he so lai)');
ok(strpos($mdl, 'function giaVon') !== false,   'Model co giaVon()');
ok(strpos($mdl, 'function tinhLai') !== false,  'Model co tinhLai() — nut tinh gia');
ok(strpos($mdl, 'function chayNeuDenHan') !== false, 'Model co chayNeuDenHan() — dat lich');
ok(strpos($mdl, 'function mapMoiNhom') !== false,    'Model co mapMoiNhom() — cho man bao gia');

/* KHONG duoc roi ve `parts`.`price`: day la GIA BAN niem yet, khong phai gia von. */
ok(strpos($mdl, "Load::model('PartsModel')") === false,
   'giaNhapGanNhat() cua model nay KHONG roi ve `parts`.`price`',
   'parts.price la gia ban niem yet — lay lam gia von roi cong 20% la gia treo len moi lan tinh');

ok(strpos($mdl, 'dkChungVaRieng') !== false,
   'Doc `parts` bang dkChungVaRieng (chung-va-rieng), khong phai dkGara',
   'Loc bang dkGara thi bang gia bo sot dung nhung ma ban chay nhat — hang kho tong');

// ---------------------------------------------------------------------------
section('Hai kieu gia von — dung hai lua chon, khong co lua chon thu ba');

ok(preg_match("~const KIEU = \['binh_quan'.*'gan_nhat'~s", $mdl) === 1,
   'Chi khai dung hai kieu: binh_quan va gan_nhat');
ok(preg_match("~isset\(self::KIEU\[\\\$v\]\) \? \\\$v : 'binh_quan'~", $mdl) === 1,
   'Gia tri la bi keo ve binh_quan',
   'Sua tay o form thanh "abc" thi khong duoc sinh ra kieu tinh gia von thu ba');
ok(preg_match("~const LICH = \['tat'~", $mdl) === 1, 'Lich co bon muc, mac dinh tat');

// ---------------------------------------------------------------------------
section('Nhom khach hang co he so lai');

$ctlNhom = codeOnly($goc . 'app/controllers/admin/Customergroups.php');
ok(strpos($ctlNhom, "'markup_percent'") !== false, 'Controller luu `markup_percent`');
ok(strpos($ctlNhom, 'max(0,') !== false,
   'He so lai am bi keo ve 0',
   'He so am la ban duoi gia von ma khong co gi bao');

foreach (['add', 'edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/customer-groups/' . $v . '.php');
    ok(strpos($f, 'name="markup_percent"') !== false, $v . '.php: co o He so lai');
    ok(strpos($f, 'name="discount_percent"') !== false,
       $v . '.php: VAN GIU o Chiet khau',
       'Hai con so nguoc chieu nhau, khong duoc gop lam mot');
}

$lst = file_get_contents($goc . 'app/views/admin/customer-groups/lists.php');
ok(strpos($lst, 'Hệ số lãi (%)') !== false, 'lists.php: co cot He so lai');
ok(strpos($lst, 'colspan="7"') !== false,
   'lists.php: dong "chua co nhom" trai du 7 cot',
   'Them cot ma quen colspan thi hang do thieu mot o');

// ---------------------------------------------------------------------------
section('Truong chi phi tren man Hang hoa');

$ctlSp = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(strpos($ctlSp, "'cost_extra'") !== false, 'Controller hang hoa luu `cost_extra`');
foreach (['add', 'edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/products/' . $v . '.php');
    ok(strpos($f, 'name="cost_extra"') !== false, 'products/' . $v . '.php: co o Chi phi');
}

// ---------------------------------------------------------------------------
section('Man Bang tinh gia — chi doc, nut tinh lai doi quyen sua');

$ctl = codeOnly($goc . 'app/controllers/admin/Partprices.php');
ok(strpos($ctl, 'function index') !== false,       'Controller co index()');
ok(strpos($ctl, 'function postTinhlai') !== false, 'Controller co postTinhlai()');
ok(strpos($ctl, 'chayNeuDenHan()') !== false,      'Mo man thi kiem lich tu chay');
ok(strpos($ctl, 'function edit') === false && strpos($ctl, 'function delete') === false,
   'KHONG co edit()/delete() — man nay chi doc',
   'Sua thang mot o gia o day thi lan tinh sau mat trang');

$rt = file_get_contents($goc . 'routes/web.php');
ok(strpos($rt, "Route::get('part-prices', 'admin/partprices')") !== false,
   'Co tuyen xem bang gia');
ok(strpos($rt, "Route::post('part-prices/edit/tinh-lai'") !== false,
   'Lenh tinh lai nam DUOI `edit/` va la POST',
   'De ten khac thi RoleMiddleware chi doi quyen `view` — ai xem duoc cung tinh lai duoc');

$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("~'products', 'part-prices'~", $sb) === 1,
   'Menu: Bang tinh gia dung ngay sau Hang hoa');

$view = file_get_contents($goc . 'app/views/admin/part-prices/lists.php');
ok(strpos($view, 'csrf_field()') !== false, 'Form tinh lai co CSRF');
ok(strpos($view, 'coQuyenTinh') !== false, 'Nut tinh lai an theo quyen');
ok(strpos($view, '$soCot') !== false,
   'colspan TINH RA theo so nhom khach',
   'Viet cung thi gara them mot nhom la dong "chua co du lieu" thieu mot o');

// ---------------------------------------------------------------------------
section('Cau hinh gara co hai o moi');

$ctlSet = codeOnly($goc . 'app/controllers/admin/Settings.php');
ok(strpos($ctlSet, "'cost_method'") !== false && strpos($ctlSet, "'price_schedule'") !== false,
   'Settings cho phep luu cost_method + price_schedule');
$formSet = file_get_contents($goc . 'app/views/admin/settings/form.php');
ok(strpos($formSet, 'name="cost_method"') !== false,    'form.php: co o Kieu tinh gia von');
ok(strpos($formSet, 'name="price_schedule"') !== false, 'form.php: co o Lich tu tinh');

// ---------------------------------------------------------------------------
section('Bon man lap chung tu deu lay gia theo loai khach');

foreach (['quotations/add', 'quotations/edit', 'sales-invoices/add', 'sales-invoices/edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/' . $v . '.php');
    ok(strpos($f, 'var NHOM_KH')  !== false, $v . '.php: co ban do khach -> nhom');
    ok(strpos($f, 'var GIA_NHOM') !== false, $v . '.php: co ban do nhom -> gia');
    ok(strpos($f, 'function giaNhom') !== false, $v . '.php: co ham giaNhom()');
    ok(strpos($f, 'giaNhom(sel.value)') !== false,
       $v . '.php: chon mat hang thi lay gia cua loai khach');
    ok(strpos($f, 'ĐỔI KHÁCH THÌ ĐƠN GIÁ') !== false,
       $v . '.php: doi khach thi don gia di theo loai khach moi');
    ok(preg_match('~return \(g != null && g > 0\) \? Math\.round\(g\) : null;~', $f) === 1,
       $v . '.php: chua co gia thi tra null (giu gia niem yet), khong tra 0');
}

$ctlQ = codeOnly($goc . 'app/controllers/admin/Quotations.php');
ok(strpos($ctlQ, 'nhomCuaKhach()') !== false,  'Quotations truyen ban do nhom khach');
ok(strpos($ctlQ, 'trongGara(') !== false,
   'Quotations doc bang gia cua DUNG GARA MANG PHIEU',
   'Mo phieu cua gara A ra sua ma thay gia cua gara B la sai tien');
$ctlI = codeOnly($goc . 'app/controllers/admin/Salesinvoices.php');
ok(strpos($ctlI, 'nhomCuaKhach()') !== false,  'Salesinvoices truyen ban do nhom khach');
ok(strpos($ctlI, 'mapMoiNhom()') !== false,    'Salesinvoices truyen bang gia');

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

$cp = $cot('part_prices');
foreach (['garage_id', 'part_id', 'group_id', 'cost_price', 'markup_percent',
          'cost_extra', 'sale_price', 'method', 'calculated_at'] as $c){
    ok(isset($cp[$c]), 'CSDL co `part_prices`.`' . $c . '`');
}

$cg = $cot('customer_groups');
ok(isset($cg['markup_percent']), 'CSDL co `customer_groups`.`markup_percent`');
if (isset($cg['markup_percent'])){
    ok($cg['markup_percent']['Null'] === 'NO' && (float) $cg['markup_percent']['Default'] == 0.0,
       'markup_percent NOT NULL DEFAULT 0 — nhom cu khong doi hanh vi');
}
ok(isset($cg['discount_percent']), 'CSDL VAN CON `customer_groups`.`discount_percent`');

$cpart = $cot('parts');
ok(isset($cpart['cost_extra']), 'CSDL co `parts`.`cost_extra`');

/* KHOA DUY NHAT phai la bo ba — thieu garage_id thi hai gara de len nhau. */
$idx = $pdo->query("SHOW INDEX FROM `part_prices` WHERE Key_name = 'uq_part_price'")
           ->fetchAll(PDO::FETCH_ASSOC);
$cotKhoa = array_column($idx, 'Column_name');
ok($cotKhoa === ['garage_id', 'part_id', 'group_id'],
   'Khoa duy nhat la (garage_id, part_id, group_id)',
   'Dang co: ' . implode(',', $cotKhoa));

/* Module + quyen */
$m = $pdo->query("SELECT id, chi_tan_phat FROM modules WHERE link = 'part-prices'")
         ->fetch(PDO::FETCH_ASSOC);
ok(!empty($m), 'Module `part-prices` da dang ky trong `modules`');
if (!empty($m)){
    ok((int) $m['chi_tan_phat'] === 0, 'chi_tan_phat = 0 — man cua tung gara');
    $q = (int) $pdo->query("SELECT COUNT(*) FROM permissions WHERE module_id = " . (int) $m['id'])
                   ->fetchColumn();
    $qSp = (int) $pdo->query(
        "SELECT COUNT(*) FROM permissions p JOIN modules md ON md.id = p.module_id
          WHERE md.link = 'products'")->fetchColumn();
    ok($q > 0, 'Da cap quyen cho module moi (' . $q . ' dong)');
    ok($q === $qSp, 'Cap dung bang so quyen cua `products` (' . $qSp . ' dong)',
       'Chep thieu la co nhom vao man hinh thay "khong co quyen"');
}

// ---------------------------------------------------------------------------
section('Cong thuc ra DUNG con so trong so do khach ve');

/* Bình quân gia quyền, đúng phép tính khách viết:
     gạt mưa  (5tr + 12tr) / (5 + 10) = 17/15  = 1.133.333
     dầu nhớt (6tr + 11tr) / (3 + 5)  = 17/8   = 2.125.000  */
$bqGat  = round((5000000 + 12000000) / 15, 2);
$bqDau  = round((6000000 + 11000000) / 8,  2);
ok(abs($bqGat - 1133333.33) < 0.01, 'Binh quan gat mua = 17/15 (1.133.333)', 'Ra: ' . $bqGat);
ok(abs($bqDau - 2125000.0)  < 0.01, 'Binh quan dau nhot = 17/8  (2.125.000)', 'Ra: ' . $bqDau);

/* Dùng CHÍNH hàm của model, không chép lại công thức vào test. */
require_once $goc . 'app/models/PartPricesModel.php';
$pp = new PartPricesModel();

/* Giá vốn GẦN NHẤT theo sơ đồ: gạt mưa 1,2tr — lẻ 20% -> 1.440.000 */
ok((int) $pp->tinhGiaBan(1200000, 0, 20) === 1440000,
   'Gan nhat + khach le 20%: 1,2tr -> 1.440.000  (dung so do khach)');
ok((int) $pp->tinhGiaBan(1200000, 0, 15) === 1380000,
   'Gan nhat + dai ly 15%:   1,2tr -> 1.380.000');
ok((int) $pp->tinhGiaBan(1200000, 0, 10) === 1320000,
   'Gan nhat + vip 10%:      1,2tr -> 1.320.000');
ok((int) $pp->tinhGiaBan(2200000, 0, 20) === 2640000,
   'Gan nhat + khach le 20%: dau nhot 2,2tr -> 2.640.000');

/* Giá vốn BÌNH QUÂN theo sơ đồ */
ok((int) $pp->tinhGiaBan($bqGat, 0, 20) === 1360000,
   'Binh quan + khach le 20%: 1.133.333 -> 1.360.000');
ok((int) $pp->tinhGiaBan($bqDau, 0, 20) === 2550000,
   'Binh quan + khach le 20%: 2.125.000 -> 2.550.000');
ok((int) $pp->tinhGiaBan($bqDau, 0, 15) === 2443750,
   'Binh quan + dai ly 15%:   2.125.000 -> 2.443.750');

/* TRUONG CHI PHI: de 0 thi ra dung so trong so do; co so thi cong vao gia von
   TRUOC khi nhan he so. */
ok((int) $pp->tinhGiaBan(1200000, 0, 20) === (int) $pp->tinhGiaBan(1200000, 0.0, 20),
   'Chi phi 0 va chi phi khong khai la mot');
ok((int) $pp->tinhGiaBan(1000000, 200000, 20) === 1440000,
   'Chi phi cong vao gia von TRUOC khi nhan he so (1tr + 200k) x 1,2 = 1.440.000');

/* HAI CHO DE SAI */
ok((int) $pp->tinhGiaBan(0, 0, 20) === 0,
   'Gia von 0 -> gia ban 0 (chua tung nhap kho)',
   'Man hinh phai hien "chua co", va mapMoiNhom() phai bo dong nay');
ok((int) $pp->tinhGiaBan(-500000, 0, 20) === 0,
   'Gia von am bi keo ve 0',
   'Gia von am la gia ban am — khong con la gia');
ok((int) $pp->tinhGiaBan(1000000, 0, 0) === 1000000,
   'He so 0 -> ban dung gia von (nhom chua khai he so lai)');

/* mapMoiNhom() va mapTheoNhom() PHAI BO dong gia 0 */
ok(preg_match('~if \(\$gia <= 0\) continue;~', $mdl) === 1,
   'mapMoiNhom() bo dong gia 0',
   'De lot xuong man bao gia la don gia 0 dong, khong ai nhin ra');
ok(preg_match('~if \(\$gia > 0\) \$map\[\(int\) \$r\[.part_id.\]\] = \$gia;~', $mdl) === 1,
   'mapTheoNhom() cung bo dong gia 0');

// ---------------------------------------------------------------------------
section('Hai kieu gia von PHAI ra hai con so khac nhau');

/* VI SAO PHAI THU BANG DU LIEU THAT: du lieu mau hien co moi mat hang chi co
   MOT phieu nhap, nen binh quan va gan nhat tinh cho ra cung mot so. Hai
   duong tinh hoan toan khac nhau van "dung" het — ke ca khi mot trong hai bi
   noi nham vao duong kia. Phai co lan nhap THU HAI voi gia khac thi moi tach
   duoc chung ra.

   LAP MOT PHIEU NHAP TAM roi xoa ngay. Phieu nay CO TINH khong dong vao
   `stocks`: nho the binh quan giu nguyen so cu con gan nhat nhay sang so moi
   — dung cai can do. Xoa o cuoi muc nay nen `stocks` chua bao gio sai. */

$gara  = 2;                 // Gara mau Sai Gon — co san ton kho trong du lieu mau
$maThu = 'ZZ-TEST-GIA';

/* Don dep tan du cua lan chay truoc (neu lan do dut giua chung) */
$pdo->exec("DELETE gri FROM goods_receipt_items gri
              JOIN goods_receipts gr ON gr.id = gri.receipt_id
             WHERE gr.receipt_no = " . $pdo->quote($maThu));
$pdo->exec("DELETE FROM goods_receipts WHERE receipt_no = " . $pdo->quote($maThu));

$mon = $pdo->query(
    "SELECT s.part_id,
            ROUND(SUM(s.quantity * s.avg_cost) / NULLIF(SUM(s.quantity), 0), 2) AS bq
       FROM stocks s JOIN warehouses w ON w.id = s.warehouse_id
      WHERE w.garage_id = $gara AND s.quantity > 0
   GROUP BY s.part_id
     HAVING bq > 0 ORDER BY s.part_id LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$kho = $pdo->query("SELECT id FROM warehouses WHERE garage_id = $gara ORDER BY id LIMIT 1")
           ->fetchColumn();

if (empty($mon) || empty($kho)){
    echo "\n[SKIP] Gara $gara chua co ton kho de thu hai kieu gia von.\n";
    exit(summary());
}

$partId = (int) $mon['part_id'];
$bqCu   = (float) $mon['bq'];
$giaMoi = round($bqCu * 2 + 7777, 2);   // lech han, va khong tron so -> kho trung ngau nhien

require_once $goc . 'app/models/PartPricesModel.php';
$pp2 = new PartPricesModel();

$doCaHai = function () use ($pp2, $gara, $partId){
    return PartPricesModel::trongGara($gara, function () use ($pp2, $partId){
        return ['bq' => $pp2->giaVon($partId, 'binh_quan'),
                'gn' => $pp2->giaVon($partId, 'gan_nhat')];
    });
};

$truoc = $doCaHai();
ok(abs($truoc['bq'] - $bqCu) < 0.01,
   'binh_quan doc dung `stocks`.`avg_cost` cua gara (' . $bqCu . ')',
   'Model tra: ' . $truoc['bq']);
ok($truoc['gn'] > 0, 'gan_nhat doc duoc gia tu phieu nhap da ghi so');

/* `parts`.`price` la GIA BAN niem yet. Neu gan_nhat lo roi ve no thi con so
   se bang dung gia ban — day la cho bat loi do. */
$giaNiemYet = (float) $pdo->query("SELECT price FROM parts WHERE id = $partId")->fetchColumn();
ok(abs($truoc['gn'] - $giaNiemYet) > 0.01 || abs($giaNiemYet - $truoc['bq']) < 0.01,
   'gan_nhat KHONG phai `parts`.`price` (gia ban niem yet)',
   'gan_nhat=' . $truoc['gn'] . ' trong khi parts.price=' . $giaNiemYet);

try {
    /* Phieu nhap tam: DA GHI SO (status = 1), ngay hom nay -> la phieu moi nhat */
    $ins = $pdo->prepare(
        "INSERT INTO goods_receipts
                (receipt_no, receipt_type, warehouse_id, receipt_date, reason,
                 total_amount, status, create_at, garage_id)
         VALUES (?, 'nhap_mua', ?, CURDATE(), 'Phieu tam cua BangTinhGiaTest', ?, 1, NOW(), ?)");
    $ins->execute([$maThu, (int) $kho, $giaMoi, $gara]);
    $prId = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO goods_receipt_items (receipt_id, part_id, quantity, unit_cost, amount)
                   VALUES (?, ?, 1, ?, ?)")->execute([$prId, $partId, $giaMoi, $giaMoi]);

    $sau = $doCaHai();

    ok(abs($sau['gn'] - $giaMoi) < 0.01,
       'Nhap them mot phieu gia khac -> gan_nhat NHAY sang gia moi (' . $giaMoi . ')',
       'Model tra: ' . $sau['gn']);
    ok(abs($sau['bq'] - $bqCu) < 0.01,
       'Binh quan KHONG doi (phieu tam khong dong vao `stocks`)',
       'Model tra: ' . $sau['bq']);
    ok(abs($sau['gn'] - $sau['bq']) > 0.01,
       'HAI KIEU RA HAI CON SO KHAC NHAU — khong phai hai ten goi cua cung mot duong',
       'gan_nhat=' . $sau['gn'] . ' binh_quan=' . $sau['bq']);

    /* Va gia ban di theo dung kieu dang chon */
    ok((int) $pp2->tinhGiaBan($sau['gn'], 0, 20) !== (int) $pp2->tinhGiaBan($sau['bq'], 0, 20),
       'Gia ban cua hai kieu cung khac nhau');

} finally {
    $pdo->prepare("DELETE FROM goods_receipt_items WHERE receipt_id IN
                   (SELECT id FROM goods_receipts WHERE receipt_no = ?)")->execute([$maThu]);
    $pdo->prepare("DELETE FROM goods_receipts WHERE receipt_no = ?")->execute([$maThu]);
}

$con = (int) $pdo->query("SELECT COUNT(*) FROM goods_receipts WHERE receipt_no = "
                         . $pdo->quote($maThu))->fetchColumn();
ok($con === 0, 'Da xoa sach phieu nhap tam', 'Con lai ' . $con . ' phieu');

$lai = $doCaHai();
ok(abs($lai['gn'] - $truoc['gn']) < 0.01 && abs($lai['bq'] - $truoc['bq']) < 0.01,
   'Giá vốn trở lại đúng như trước khi thử',
   'gan_nhat ' . $truoc['gn'] . ' -> ' . $lai['gn'] . ' | binh_quan ' . $truoc['bq'] . ' -> ' . $lai['bq']);

// ---------------------------------------------------------------------------
section('Nut "Cap nhat gia" phai doi quyen that, khong chi an di');

/* ⚠️ MIDDLEWARE KHONG CHAY CHO TUYEN NAY — da thu that 11/11/2026.
 *
 * `admin/*` trong configs/app.php duoc App::updateConfigRouteMiddleware() bung
 * ra thanh tung tuyen mot, ma danh sach tuyen cua nhom CHI do Route::get() ghi
 * vao — Route::post() thi khong (core/Route.php). Bang middleware danh khoa
 * theo CHUOI DUONG DAN chu khong theo phuong thuc, nen tuyen POST nao trung
 * ten voi mot tuyen GET thi duoc bao ve ke. `part-prices/edit/tinh-lai` chi co
 * POST, nen khong co AuthMiddleware lan RoleMiddleware.
 *
 * Lan chay dau tien cua test nay (truoc khi va) tai khoan Staff gui POST thang
 * vao day va bang gia BI TINH LAI THAT. Khang dinh duoi la cai giu cho chuyen
 * do khong quay lai. */

$ctlQuyen = codeOnly($goc . 'app/controllers/admin/Partprices.php');
ok(preg_match('~function postTinhlai\(\)\{\s*if \(!\$this->coQuyenTinh\(\)\)~s', $ctlQuyen) === 1,
   'postTinhlai() kiem quyen NGAY DONG DAU, truoc moi viec khac',
   'Middleware khong chay cho tuyen chi-co-POST — controller phai tu giu minh');
ok(strpos($ctlQuyen, "redirect('admin/khong-co-quyen')") !== false,
   'Khong du quyen thi da ve trang "khong co quyen"');

$base = web_goc();
$MK   = 'Gara@2026';
$jars = [];
register_shutdown_function(function() use (&$jars){ foreach ($jars as $j) @unlink($j); });

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
    return ['code' => (int) $info['http_code'], 'loc' => (string) $info['redirect_url'], 'text' => $body];
};
$token  = function($h){ return preg_match('~name="_token" value="([^"]+)"~', $h, $m) ? $m[1] : ''; };
$moJar  = function() use (&$jars){ $j = tempnam(sys_get_temp_dir(), 'zzgia'); $jars[] = $j; return $j; };
$mocGia = function() use ($pdo){
    return (string) $pdo->query("SELECT MAX(calculated_at) FROM part_prices")->fetchColumn();
};

/* Gui lenh tinh lai duoi danh nghia mot tai khoan (rong = chua dang nhap).
   @return [ket qua HTTP, moc tinh gia co doi khong] */
$guiLenh = function($email) use ($http, $token, $moJar, $base, $MK, $mocGia){
    $jar = $moJar();
    $r   = $http('GET', "$base/dang-nhap", $jar);
    if ($r['code'] === 0) return null;                 // Apache khong chay
    $tk = $token($r['text']);

    if ($email !== ''){
        $http('POST', "$base/dang-nhap", $jar,
              ['email' => $email, 'password' => $MK, '_token' => $tk]);
        /* Token moi SAU khi dang nhap: phien doi thi token cu khong con dung */
        $tk = $token($http('GET', "$base/admin/customers/add", $jar)['text']);
    }

    $truoc = $mocGia();
    $kq    = $http('POST', "$base/admin/part-prices/edit/tinh-lai", $jar, ['_token' => $tk]);
    return ['http' => $kq, 'doi' => $mocGia() !== $truoc];
};

$thuAnDanh = $guiLenh('');
if ($thuAnDanh === null){
    echo "\n[SKIP] Apache khong chay (localhost:88) — bo qua phan goi HTTP that.\n";
    exit(summary());
}

/* CHAN O DAU CUNG DUOC, MIEN LA CHAN. Noi long khang dinh 11/11/2026 sau khi
   va Route::post(): truoc day tuyen nay khong co middleware nen khach an danh
   di thang vao controller, va chinh coQuyenTinh() day ve 'khong-co-quyen'. Nay
   AuthMiddleware chan ngay o cua va day ve 'dang-nhap' — chan SOM HON, tot hon.
   Dieu phai giu nguyen la phan sau: bang gia KHONG bi tinh lai. */
ok(($biChanO = (strpos($thuAnDanh['http']['loc'], 'khong-co-quyen') !== false
                || strpos($thuAnDanh['http']['loc'], 'dang-nhap') !== false))
   && !$thuAnDanh['doi'],
   'CHUA DANG NHAP: bi chan, bang gia KHONG bi tinh lai',
   'HTTP ' . $thuAnDanh['http']['code'] . ' -> ' . $thuAnDanh['http']['loc']
   . ' | moc tinh gia doi: ' . ($thuAnDanh['doi'] ? 'CO' : 'khong'));

$thuStaff = $guiLenh('covan.sg@gara-mau.test');
ok($thuStaff !== null && strpos($thuStaff['http']['loc'], 'khong-co-quyen') !== false && !$thuStaff['doi'],
   'STAFF (chi co quyen `view`): bi chan, bang gia KHONG bi tinh lai',
   'HTTP ' . $thuStaff['http']['code'] . ' -> ' . $thuStaff['http']['loc']
   . ' | moc tinh gia doi: ' . ($thuStaff['doi'] ? 'CO' : 'khong'));

$thuManager = $guiLenh('chugara.sg@gara-mau.test');
ok($thuManager !== null && strpos($thuManager['http']['loc'], 'khong-co-quyen') === false
   && $thuManager['doi'],
   'MANAGER (co quyen `edit`): chay duoc, bang gia DUOC tinh lai',
   'Khong co khang dinh nay thi ca muc nay xanh chi vi moi thu deu bi chan. HTTP '
   . ($thuManager ? $thuManager['http']['code'] . ' -> ' . $thuManager['http']['loc'] : '-'));

exit(summary());

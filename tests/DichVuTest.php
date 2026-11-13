<?php
/**
 * Test MAN HINH DICH VU + TAB "Hang hoa / Dich vu" trong bao gia (19/08/2026).
 *
 * Chay:  C:\xampp\php\php.exe tests\DichVuTest.php
 *
 * Trong tam:
 *   - Dich vu KHONG co bang rieng: van la dong trong `parts` voi
 *     item_type='service'. Nho vay bao gia / hoa don van tro part_id nhu cu.
 *   - Man hinh Dich vu chi duoc dung toi dong dich vu. Khong chan thi
 *     /admin/services/delete/<id phu tung> xoa duoc hang hoa that.
 *   - Hai bang dong hang trong bao gia phai dung HAI bo ten o khac nhau.
 *     Dung chung ten thi thu tu phan tu phu thuoc thu tu DOM — doi cho tab
 *     mot cai la so luong nhay sang mat hang khac ma khong bao loi gi.
 *   - Tong bao gia = tien hang hoa + tien dich vu.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

$goc = __DIR__ . '/../';

try {
    $pdo = new PDO(
        'mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
        _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n";
    exit(0);
}

echo 'PHP ' . PHP_VERSION . "\n";

// ---------------------------------------------------------------------------
section('Module `services` da dang ky (thieu la RoleMiddleware da ve khong-co-quyen)');

$mod = $pdo->query("SELECT * FROM `modules` WHERE `link` = 'services'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($mod), 'Co dong `services` trong bang modules');

if (!empty($mod)){
    ok($mod['name'] === 'Dịch vụ', 'Ten hien tren menu la "Dịch vụ"', 'dang la: ' . $mod['name']);

    $st = $pdo->prepare("SELECT p.role FROM `permissions` p
                         JOIN `groups` g ON g.id = p.group_id
                         WHERE p.module_id = ? AND g.name = 'Admin'");
    $st->execute([$mod['id']]);
    $roles = $st->fetchAll(PDO::FETCH_COLUMN);
    foreach (['view', 'add', 'edit', 'delete'] as $r){
        ok(in_array($r, $roles, true), "Nhom Admin co quyen `$r`");
    }
}

// ---------------------------------------------------------------------------
section('Dich vu van nam trong bang `parts`, khong tach bang rieng');

$bang = $pdo->query("SHOW TABLES LIKE 'services'")->fetchAll();
ok(empty($bang), 'KHONG tao bang `services` rieng',
   'Tach bang la moi bang dong hang phai mang them cap (loai, id)');

$col = $pdo->query("SHOW COLUMNS FROM `parts` LIKE 'item_type'")->fetch(PDO::FETCH_ASSOC);
ok(!empty($col), 'Cot `parts`.`item_type` van con');
ok(!empty($col) && strpos($col['Type'], "'service'") !== false, "item_type co gia tri 'service'");

// ---------------------------------------------------------------------------
section('Controller Services');

$f = $goc . 'app/controllers/admin/Services.php';
ok(is_file($f), 'Co file app/controllers/admin/Services.php');
$code = codeOnly($f);

ok(strpos($code, "'item_type'   => PartsModel::LOAI_DICH_VU") !== false
   || strpos($code, 'PartsModel::LOAI_DICH_VU') !== false,
   'Luon ghi item_type = service');

// Quan trong: KHONG duoc doc item_type tu form
ok(strpos($code, "\$f['item_type']") === false,
   'KHONG nhan item_type tu POST',
   'Nhan tu POST la mo duong cho mot dong "part" khong co ton lot vao bang cua sau');

ok(strpos($code, 'layDichVu') !== false, 'Co ham chan theo loai (layDichVu)');
ok(preg_match('/item_type\'\]\s*!==\s*PartsModel::LOAI_DICH_VU/', $code) === 1,
   'layDichVu() tu choi ban ghi khong phai dich vu');

foreach (['index', 'add', 'postAdd', 'edit', 'postEdit', 'delete'] as $h){
    ok(preg_match('/function\s+' . $h . '\s*\(/', $code) === 1, "Co ham $h()");
}

// Sua / xoa deu phai qua cua chan loai
foreach (['edit', 'postEdit', 'delete'] as $h){
    ok(preg_match('/function\s+' . $h . '\s*\([^)]*\)\s*\{\s*\$item\s*=\s*\$this->layDichVu/', $code) === 1,
       "$h() goi layDichVu() ngay dau ham");
}

// ---------------------------------------------------------------------------
section('Route + menu');

$routes = file_get_contents($goc . 'routes/web.php');
foreach (['services', 'services/add', 'services/edit/(\d+)', 'services/delete/(\d+)'] as $r){
    ok(strpos($routes, "'" . $r . "'") !== false, "Co route $r");
}
ok(strpos($routes, "Route::post('services/add'") !== false,  'Co route POST services/add');
ok(strpos($routes, "Route::post('services/edit/(\\d+)'") !== false, 'Co route POST services/edit');

$sidebar = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');
ok(preg_match("/'Hàng hoá'\s*=>\s*\[[^\]]*'services'/u", $sidebar) === 1,
   'Menu "Hang hoa" co muc `services`');

// ---------------------------------------------------------------------------
section('Man hinh Dich vu gon hon man hinh Hang hoa');

$add = file_get_contents($goc . 'app/views/admin/services/add.php');

foreach (['name', 'price'] as $o){
    ok(strpos($add, 'name="' . $o . '"') !== false, "Co o `$o`");
}
// Nhung o CUA HANG HOA khong duoc xuat hien o day
foreach (['oem_code', 'brand_id', 'manufacturer_id', 'origin_id', 'sale_price', 'fitment'] as $o){
    ok(strpos($add, 'name="' . $o . '"') === false, "KHONG co o `$o` (khong thuoc dich vu)");
}
ok(strpos($add, 'name="item_type"') === false,
   'Khong co o chon Loai — man hinh nay chi tao dich vu');
ok(strpos($add, 'name="slug"') === false,
   'Khong bat nhap slug (tu sinh, tu ne trung)');

ok(preg_match('/name="price"[^>]*type="number"|type="number"[^>]*name="price"/', $add) === 1,
   'O Gia dung type="number"');

// ---------------------------------------------------------------------------
section('Ma va slug tu sinh');

$parts = codeOnly($goc . 'app/models/PartsModel.php');
ok(strpos($parts, 'function nextCode') !== false,  'PartsModel::nextCode()');
ok(strpos($parts, 'function slugTrong') !== false, 'PartsModel::slugTrong()');
ok(strpos($parts, 'findByCode($ma)') !== false,
   'nextCode() kiem tra lai ma da ton tai chua (phong ma nhap tay chen vao)');

require_once $goc . 'app/models/LookupModel.php';
require_once $goc . 'app/models/PartsModel.php';
require_once $goc . 'app/helpers/functions.php';

$pm = new PartsModel();
$ma = $pm->nextCode('DV-');
ok(preg_match('/^DV-\d{4,}$/', $ma) === 1, "nextCode('DV-') ra dang DV-0001", 'duoc: ' . $ma);
$st = $pdo->prepare('SELECT COUNT(*) FROM `parts` WHERE `code` = ?');
$st->execute([$ma]);
ok((int) $st->fetchColumn() === 0, 'Ma sinh ra chua ai dung');

// ---------------------------------------------------------------------------
section('Bao gia: hai tab dung HAI bo ten o');

foreach (['add', 'edit'] as $v){
    $s = file_get_contents($goc . 'app/views/admin/quotations/' . $v . '.php');

    ok(strpos($s, "data-pane=\"hang\"") !== false || strpos($s, "'hang'") !== false,
       "quotations/$v: co tab Hang hoa");
    ok(strpos($s, 'dichvu') !== false, "quotations/$v: co tab Dich vu");

    ok(strpos($s, "tienTo + 'part[]'") !== false,
       "quotations/$v: ten o dong hang dung theo tien to cua tab");
    ok(strpos($s, "'line_'") !== false && strpos($s, "'sv_'") !== false,
       "quotations/$v: hai tien to `line_` va `sv_` deu co");

    ok(strpos($s, 'LOAI_DICH_VU') !== false,
       "quotations/$v: chia mat hang ve tab theo item_type");

    /* Tong tien phai co du cac dong. Dung CLASS chu khong phai id: khoi tong
       ve o ca hai tab nen id se trung nhau, ma getElementById chi thay ban dau. */
    foreach (['tong-hang', 'tong-dichvu', 'sub-total', 'tax-total', 'grand-total'] as $o){
        ok(strpos($s, 'class="js-' . $o . '"') !== false, "quotations/$v: co o tong `$o`");
        ok(strpos($s, 'id="' . $o . '"') === false,
           "quotations/$v: `$o` khong dung id (ve 2 lan se trung id)");
    }
    ok(strpos($s, 'tHang + tDichVu') !== false,
       "quotations/$v: cong chua thue = tien hang hoa + tien dich vu");

    /* Khoi tong tien phai nam trong <tfoot> cua chinh bang dong hang, giong
       7 man hinh chung tu con lai (hoa don, phieu nhap, phieu xuat...).

       Ban dau viet thanh .card-footer voi <div class="col-md-6 offset-md-6">.
       offset-md-6 chua trong nua trai: do duoc 496 x 236 px nen trang tron
       ngay duoi bang — nhin nhu noi dung nap hong. Dat trong <tfoot> thi
       cot tien tu thang hang voi cot "Thanh tien" o tren, va khong con o trong. */
    // codeOnly(): chinh file nay co comment trich lai doan markup cu de giai
    // thich, grep thang vao text nguon se trung comment va bao fail oan.
    ok(strpos(codeOnly($goc . 'app/views/admin/quotations/' . $v . '.php'), 'offset-md-') === false,
       "quotations/$v: KHONG chua mot cot luoi rong canh khoi tong tien");
    ok(strpos($s, '<tfoot>') !== false,
       "quotations/$v: tong tien nam trong <tfoot> cua bang dong hang");
}

// ---------------------------------------------------------------------------
section('Doi mat hang thi gia phai di theo mat hang moi');

/* LOI THAT, khach bao 19/08/2026. Ban cu:
       if (p && !money(price.value)) price.value = p;
   "chi dien khi o gia dang trong", y la dung de gia nguoi dung tu go. Nhung
   no khong phan biet duoc gia NGUOI DUNG go voi gia do CHINH NO vua dien cho
   mat hang TRUOC DO. Chon Ac quy (1.380.000) roi doi dong do sang Bugi thi
   gia van nam nguyen 1.380.000 -> hoa don luu sai gia, khong co gi bao.
   Da xay ra that tren HD-000006. */
foreach (['quotations/add', 'quotations/edit',
          'sales-invoices/add', 'sales-invoices/edit'] as $v){
    $s = file_get_contents($goc . 'app/views/admin/' . $v . '.php');

    /* Phai bo comment JS truoc khi soi: codeOnly() chi bo comment PHP, ma
       chinh cho sua nay co comment trich lai nguyen doan code cu de giai
       thich — grep thang vao la trung comment, bao fail oan. */
    preg_match('~<script>(.*?)</script>~s', $s, $m);
    $ma = preg_replace('~/\*.*?\*/~s', '', isset($m[1]) ? $m[1] : '');
    $ma = preg_replace('~//[^
]*~', '', $ma);

    ok(strpos($ma, '!money(price.value)') === false,
       "$v: KHONG con chot 'chi dien khi o gia dang trong'",
       'Chot do giu lai gia cua mat hang truoc do khi doi hang');
    ok(preg_match('~if \(p\)\s*price\.value = p;~', $ma) === 1,
       "$v: doi mat hang -> gia luon lay theo mat hang moi");
}

// ---------------------------------------------------------------------------
section('Chon trung mot mat hang thi CONG SO LUONG vao dong da co (thay luat loc 19/08)');

/* Khach bao 19/08/2026: chon PT-0008 o mot dong roi van chon lai duoc chinh no o
   dong duoi. Luc do em LOC mat hang da chon khoi danh sach cua dong khac.

   13/11/2026 khach noi ve Don mua: "chon 2 san pham giong nhau phai tang so luong
   day chu lai thanh 2 dong", anh chot lam luon cho bao gia / hoa don. Cung dich
   (khong bao gio co hai dong trung) nhung nguoi dung chon lai la duoc cong so
   luong, khong phai di tim dong cu roi tu sua. Nen mat hang KHONG con bi loc khoi
   danh sach nua — loc thi khong the "chon lai de tang". */
foreach (['quotations/add', 'quotations/edit',
          'sales-invoices/add', 'sales-invoices/edit'] as $v){
    $s  = file_get_contents($goc . 'app/views/admin/' . $v . '.php');
    preg_match('~<script>(.*?)</script>~s', $s, $m);
    $ma = preg_replace('~/\*.*?\*/~s', '', isset($m[1]) ? $m[1] : '');
    $ma = preg_replace('~//[^\n]*~', '', $ma);

    ok(strpos($ma, 'function napOption') !== false,
       "$v: co ham nap lai danh sach o chon");
    ok(strpos($ma, 'oDongKhac') === false,
       "$v: KHONG con loc mat hang da co o dong khac ra khoi danh sach",
       'Loc thi khong chon lai duoc nua, ma khach muon chon lai de tang so luong');

    /* Ham gop + cho goi: dau handler change, TRUOC khi dien gia (gop roi thi dong
       nay khong con, khong dien gia cho no). */
    ok(strpos($ma, 'function gopNeuTrung(tr, sel)') !== false, "$v: co ham gop dong trung");
    ok(preg_match('~sel\.addEventListener\(\'change\', function \(\)\{\s*if \(gopNeuTrung\(tr, sel\)\) return;~', $ma) === 1,
       "$v: ham gop chay DAU TIEN trong handler doi o chon (gop xong thi dung ngay)");

    $than = substr($ma, (int) strpos($ma, 'function gopNeuTrung'), 2400);
    ok(strpos($than, "s.value === sel.value") !== false, "$v: tim dong da co CUNG mat hang");
    ok(strpos($than, 'qCu.value = String(nay)') !== false && strpos($than, 'num(qCu.value) + them') !== false,
       "$v: so luong cua dong vua chon duoc CONG vao dong cu");
    ok(strpos($than, 'Math.round') !== false, "$v: cong so le lam tron 3 so");
    ok(strpos($than, 'tr.remove()') !== false, "$v: dong vua chon bien mat (khong de hai dong)");
    ok(preg_match('~laDongCuoi && conHangDeChon\(\)\) addRow\(\)~', $than) === 1,
       "$v: gop o dong trong cuoi thi de lai mot dong trong de nhap tiep");
    ok(strpos($than, 'recompute()') !== false, "$v: tinh lai tong tien sau khi gop");
    ok(strpos($than, 'bao-gop') !== false, "$v: noi cho nguoi dung biet vi sao dong bien mat");

    /* Phieu lap truoc khi co luat nay co the da co dong trung (HD-000005 dang co 2
       dong PT-0006). Mo phieu ra khong duoc don dong sau lung: dong dung tu du lieu
       da luu khong di qua su kien change, va mat hang dang chon van duoc giu du
       khong co trong nguon. */
    ok(strpos($ma, 'String(op.id) === String(dangChon)') !== false,
       "$v: VAN chon dung mat hang chinh dong nay dang giu (phieu cu / dong da luu)");

    ok(strpos($ma, 'napLaiTatCa();') !== false, "$v: doi nguon / xoa dong thi nap lai o chon");
    ok(preg_match('~r\.remove\(\);\s*napLaiTatCa\(\);~', $ma) === 1,
       "$v: xoa dong thi nap lai danh sach");
    ok(strpos($ma, 'conHangDeChon()') !== false,
       "$v: het hang de chon thi thoi de dong trong");
}

// ---------------------------------------------------------------------------
section('Controller bao gia gop dong cua ca hai tab');

$q = codeOnly($goc . 'app/controllers/admin/Quotations.php');
ok(strpos($q, "docDong('line_')") !== false && strpos($q, "docDong('sv_')") !== false,
   'buildLines() doc CA HAI bo ten o');
ok(strpos($q, 'array_merge($this->docDong') !== false,
   'Hai bo duoc gop lai thanh mot danh sach dong');

// O chon hang cua bao gia phai kem item_type de chia tab
$qi = codeOnly($goc . 'app/models/QuotationItemsModel.php');
ok(strpos($qi, 'item_type') !== false,
   'getByQuotation() lay kem item_type (de sua bao gia cu xep dung tab)');

$pmCode = codeOnly($goc . 'app/models/PartsModel.php');
ok(strpos($pmCode, '`parts`.`item_type`') !== false,
   'getForSelect() tra ve item_type');

// ---------------------------------------------------------------------------
section('Danh muc dich vu');

$cat = codeOnly($goc . 'app/models/PartCategoriesModel.php');
ok(strpos($cat, 'function nhanhTheoSlug') !== false, 'PartCategoriesModel::nhanhTheoSlug()');

require_once $goc . 'app/models/PartCategoriesModel.php';
$cm     = new PartCategoriesModel();
$nhanh  = $cm->nhanhTheoSlug('dich-vu');
ok(!empty($nhanh), 'Lay duoc nhanh danh muc "dich-vu"');
if (!empty($nhanh)){
    ok((int) $nhanh[0]['depth'] === 0, 'Goc nhanh co depth = 0 (tinh lai tu goc nhanh)');
    ok($nhanh[0]['slug'] === 'dich-vu', 'Phan tu dau la chinh goc nhanh');

    $tenNhanh = array_column($nhanh, 'slug');
    ok(!in_array('he-thong-phanh', $tenNhanh, true),
       'Nhanh dich vu KHONG chua danh muc cua phu tung');
}

// ---------------------------------------------------------------------------
section('Loi cu: ham don truong theo loai bi bo qua vi return som');

$prod = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(preg_match('/\$data\s*=\s*\[/', $prod) === 1,
   'buildData() gan vao $data truoc khi tra ve');
ok(preg_match('/return\s+\$this->boTruongKhongThuocLoai\(\$data\);/', $prod) === 1,
   'buildData() THUC SU goi boTruongKhongThuocLoai()',
   'Truoc day co "return [...]" dung ngay tren nen ham don truong khong bao gio chay');

exit(summary());

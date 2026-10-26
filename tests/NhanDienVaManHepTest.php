<?php
/**
 * NHẬN DIỆN GARA TRÊN MENU + BẢNG DÒNG HÀNG TRÊN MÀN HẸP — 17/10/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\NhanDienVaManHepTest.php
 *
 * Hai việc khách yêu cầu khi chạy thử, và ba chỗ hỏng âm thầm quanh chúng:
 *
 *   1. LOGO ĐẶT Ở CẤU HÌNH MÀ KHÔNG CHỖ NÀO ĐỌC. Gara đặt logo được ở HAI nơi:
 *      Tân Phát khai hộ lúc mở gara (Quản lý gara → sửa, ghi vào cột
 *      `garages`.`logo`), và chính gara tải lên sau đó (Cấu hình, ghi vào
 *      `garage_settings`). cau_hinh_in_an() trước đây chỉ đọc chỗ đầu — gara
 *      tải logo lên ở màn Cấu hình, nhìn thấy nó nằm ngay đó, mà phiếu in ra
 *      vẫn trắng trơn. Không có lỗi nào báo.
 *
 *   2. NHẬN DIỆN TÂN PHÁT RƠI SANG GIẤY TỜ GARA KHÁC. Bản trộn của
 *      GarageSettingsModel::map() lấy Cấu hình chung làm NỀN — tiện cho trang
 *      bán hàng, nhưng dùng cho giấy tờ thì gara chưa điền tên sẽ in ra tờ báo
 *      giá mang tên Tân Phát. Phải đọc rieng() chứ không phải map().
 *
 *   3. CỘT TÊN HÀNG HOÁ TEO TRÊN ĐIỆN THOẠI. Bảng chia theo phần trăm, 30% của
 *      360px chỉ còn ~108px. .table-responsive có cuộn ngang nhưng bảng vẫn vừa
 *      khít 100% bề ngang thì KHÔNG CÓ GÌ ĐỂ CUỘN TỚI — phải cho bảng một bề
 *      rộng tối thiểu lớn hơn màn hình thì cuộn mới thành thật.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Menu trai — o nhan dien lay logo cua gara');

$sb = file_get_contents($goc . 'app/views/layouts/admin/sidebar.php');

ok(strpos($sb, "icon('house')") === false,
   'O nhan dien KHONG con cai nha dung chung',
   'Moi gara mot trang quan tri rieng — chu gara mo ra thay hinh khong phai cua minh');
ok(strpos($sb, 'cau_hinh_in_an()') !== false,
   'Lay logo qua cau_hinh_in_an()',
   'Dung chung mot nguon voi bieu mau in — logo tren menu va tren to bao gia phai la mot');
ok(strpos($sb, 'media_url(') !== false,
   'Dung media_url() cho duong dan anh');
/* KHONG dung logo_in_an(): ham do tra ve base64 de nhung vao file Word. Menu
   trai co o MOI trang quan tri — nhung ca tep anh vao tung trang la phinh
   moi trang len bang kich thuoc cai logo. */
ok(strpos($sb, 'logo_in_an(') === false,
   'KHONG nhung base64 vao menu',
   'logo_in_an() la cho file Word; menu co o moi trang, nhung anh vao la phinh moi trang');

/* Chua co logo thi phai con MOT THU GI DO trong o: o che do thu gon, ten va
   chuc danh bi an (.adm-collapsed), o nay la thu duy nhat con lai tren dau menu. */
ok(strpos($sb, 'adm-brand__tile--chu') !== false,
   'Chua co logo thi o nhan dien hien chu cai dau cua ten don vi',
   'De trong thi o che do thu gon dau menu khong con gi');

$css = file_get_contents($goc . 'public/assets/css/admin-theme.css');
foreach (['adm-brand__tile--chu', 'adm-brand__tile--logo'] as $c){
    ok(strpos($css, '.' . $c) !== false, "CSS co lop .$c");
}
ok(preg_match('~\.adm-brand__tile--logo img\b[^}]*object-fit:\s*contain~s', $css) === 1,
   'Logo dung object-fit: contain',
   'cover cat mat mep logo — logo bi xen mot ben nhin nhu anh hong');
/* icon() khong co duong lui, con :has() thi khong phai trinh duyet nao cung co.
   Lop dat tu PHP nen chac chan hon. */
ok(strpos($css, ':has(img)') === false,
   'Khong dua vao :has() de biet co logo hay khong',
   'Lop dat tu PHP chac chan hon mot bo chon CSS moi');

// ---------------------------------------------------------------------------
section('cau_hinh_in_an() — o gara tu khai de len bang `garages` (chot 1 + 2)');

$fn = file_get_contents($goc . 'app/helpers/functions.php');
if (preg_match('~function cau_hinh_in_an\(.*?\n\}~su', $fn, $m)){
    $than = $m[0];
    ok(strpos($than, '->rieng()') !== false,
       'Doc them cau hinh RIENG cua gara',
       'Khong doc thi gara tai logo len o man Cau hinh xong phieu in ra van trang');
    /* CHOT 2 — map() keo Cau hinh chung cua Tan Phat xuong lam nen. */
    ok(!preg_match('~GarageSettingsModel.\)\s*->\s*map\(\)~', $than),
       'KHONG dung map() (keo nhan dien Tan Phat xuong lam nen)',
       'Gara chua dien ten thi to bao gia cua ho mang ten Tan Phat');
    /* CHOT: ham nay nhan $garaId — in lai phieu cu cua gara khac. rieng() loc
       theo gara DANG LAM VIEC nen phai muon danh nghia gara cua phieu. */
    ok(strpos($than, 'trongGara(') !== false,
       'Muon danh nghia gara CUA PHIEU truoc khi doc cau hinh rieng',
       'Goi thang la dan nhan dien cua gara dang mo man hinh len phieu nguoi khac');
} else {
    ok(false, 'Doc duoc than ham cau_hinh_in_an()');
}

ok(method_exists('GarageSettingsModel', 'rieng')
   || strpos(file_get_contents($goc . 'app/models/GarageSettingsModel.php'), 'function rieng(') !== false,
   'GarageSettingsModel co rieng()');

// ---------------------------------------------------------------------------
section('Chay that — logo gara di duoc tu Cau hinh ra phieu in');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

const DAU = 'ZZND';
$donDep = function() use ($pdo){
    $pdo->exec("DELETE FROM `garage_settings` WHERE `svalue` LIKE '" . DAU . "%'");
    $pdo->exec("DELETE FROM `garages` WHERE `code` LIKE '" . DAU . "%'");
};
$donDep();

$pdo->exec("INSERT INTO `garages` (`code`,`name`,`address`,`phone`,`is_master`,`status`,`create_at`)
            VALUES ('" . DAU . "G','" . DAU . " Gara thu','So 1 Thu','0900000000',0,1,NOW())");
$gid = (int) $pdo->query("SELECT `id` FROM `garages` WHERE `code` = '" . DAU . "G'")->fetchColumn();
ok($gid > 0, "Tao duoc gara thu (#$gid)");

/* Gara CHUA khai gi o man Cau hinh: lay tu bang `garages`, va KHONG duoc
   thua huong ten / logo cua Tan Phat. */
$cf = cau_hinh_in_an($gid);
ok(isset($cf['site_name']) && $cf['site_name'] === DAU . ' Gara thu',
   'Chua khai Cau hinh: ten lay tu bang `garages`');
ok(empty($cf['logo']), 'Chua khai Cau hinh: khong co logo (khong muon logo Tan Phat)');
$chung = \App\core\Load::model('SettingsModel')->map();
ok(empty($chung['site_name']) || $cf['site_name'] !== $chung['site_name'],
   'Chua khai Cau hinh: KHONG thua huong ten cua Tan Phat (chot 2)',
   'Ten chung dang la: ' . (isset($chung['site_name']) ? $chung['site_name'] : '(trong)'));

/* Gara tu khai o man Cau hinh -> phai DE LEN bang `garages`. */
$now = date('Y-m-d H:i:s');
$st = $pdo->prepare("INSERT INTO `garage_settings` (`garage_id`,`skey`,`svalue`,`update_at`) VALUES (?,?,?,?)");
$st->execute([$gid, 'logo',      DAU . '/logo-gara.png', $now]);
$st->execute([$gid, 'site_name', DAU . ' Ten gara tu khai', $now]);
$st->execute([$gid, 'address',   DAU . ' Dia chi moi', $now]);

$cf2 = cau_hinh_in_an($gid);
ok(isset($cf2['logo']) && $cf2['logo'] === DAU . '/logo-gara.png',
   'Logo khai o man Cau hinh RA TOI phieu in (chot 1)',
   'Dang nhan duoc: ' . (isset($cf2['logo']) ? var_export($cf2['logo'], true) : '(khong co)'));
ok(isset($cf2['site_name']) && $cf2['site_name'] === DAU . ' Ten gara tu khai',
   'Ten gara tu khai de len cot trong bang `garages`');
ok(isset($cf2['address']) && $cf2['address'] === DAU . ' Dia chi moi',
   'Dia chi gara tu khai de len cot trong bang `garages`');

/* O DE TRONG KHONG DUOC DE: trong = "chua dat", khong phai "xoa". */
$st->execute([$gid, 'email', '', $now]);
$cf3 = cau_hinh_in_an($gid);
ok(isset($cf3['site_name']) && $cf3['site_name'] === DAU . ' Ten gara tu khai',
   'O de trong khong lam hong cac o khac');

$donDep();

// ---------------------------------------------------------------------------
section('Bang dong hang tren man hep (chot 3)');

foreach (['add', 'edit'] as $man){
    $v = file_get_contents($goc . 'app/views/admin/quotations/' . $man . '.php');
    ok(strpos($v, 'bang-dong-hang') !== false,
       "quotations/$man.php: bang dong hang co lop rieng de chinh tren man hep");
    /* Bang phai nam trong .table-responsive thi min-width moi thanh cuon ngang,
       khong thi no day ca trang rong ra. */
    ok(preg_match('~table-responsive[^>]*>\s*<table class="[^"]*bang-dong-hang~s', $v) === 1,
       "quotations/$man.php: bang nam trong .table-responsive",
       'Khong co khung cuon thi min-width day ca trang rong ra, cuon ca trang');
}

ok(preg_match('~@media\s*\(max-width:\s*767\.98px\)~', $css) === 1,
   'CSS co khoi rieng cho man dien thoai');
if (preg_match('~@media\s*\(max-width:\s*767\.98px\)\s*\{(.*?)\n\}~s', $css, $mQ)){
    $khoi = $mQ[1];
    ok(preg_match('~\.bang-dong-hang\s*\{[^}]*min-width:\s*(\d+)px~', $khoi, $mW) === 1
       && (int) $mW[1] >= 600,
       'Bang co be rong toi thieu lon hon man dien thoai ('
       . (isset($mW[1]) ? $mW[1] . 'px' : 'khong dat') . ')',
       'Bang vua khit 100% thi .table-responsive khong co gi de cuon toi');
    ok(preg_match('~th:first-child[^}]*min-width:\s*(\d+)px~s', $khoi, $mN) === 1
       && (int) $mN[1] >= 200,
       'Cot ten hang hoa co be rong toi thieu ('
       . (isset($mN[1]) ? $mN[1] . 'px' : 'khong dat') . ')',
       'Day la cot khach hang xin noi ra — 30% cua 360px chi con ~108px');
    /* KHONG an cot DU LIEU nao: khach da thu cuon va thay on, chi xin cot ten
       to ra. (tfoot bi an la chuyen khac — doi cho, xem muc duoi.) */
    ok(!preg_match('~\.bang-dong-hang\s*>\s*(thead|tbody)[^}]*display:\s*none~s', $khoi),
       'KHONG an cot du lieu nao tren man hep',
       'Khach chot "no truot san roi, cho cai ten hang hoa to ra la ok"');
}

// ---------------------------------------------------------------------------
section('Khoi tong phai o NGOAI vung cuon tren man hep');

/* NOI RA CHO HONG: noi bang rong ra thi <tfoot> bi day theo. Nhan "Tong cong"
   gop 4 cot dau roi canh phai, tuc no nam o moc 515px, ma dien thoai chi nhin
   thay 333px — nguoi dung thay NAM DONG TRONG TRON duoi bang va phai keo sang
   phai moi doc duoc tong. Ma tong la con so ho can nhat.

   Nen man hep doi sang mot khoi xep doc nam ngoai khung cuon. */
foreach (['add', 'edit'] as $man){
    $v = file_get_contents($goc . 'app/views/admin/quotations/' . $man . '.php');
    ok(strpos($v, 'tong-man-hep') !== false,
       "quotations/$man.php: co khoi tong rieng cho man hep");
    /* Phai nam NGOAI .table-responsive, khong thi cuon theo bang y nhu cu. */
    if (preg_match('~<div class="card-body table-responsive p-0">.*?\n(\s*)</div>~su', $v, $mW)){
        ok(strpos($mW[0], 'tong-man-hep') === false,
           "quotations/$man.php: khoi tong nam NGOAI khung cuon",
           'Nam trong thi no cuon theo bang, dung lai cho hong cu');
    }
    /* DUNG CHUNG class voi tfoot — recompute() ghi bang querySelectorAll nen
       moi o cung ten deu duoc cap nhat. Dat ten khac la them mot nguon so. */
    foreach (['js-grand-total', 'js-sub-total', 'js-khach-tra'] as $c){
        ok(substr_count($v, $c) >= 2,
           "quotations/$man.php: `$c` co o CA HAI khoi tong",
           'Khoi moi phai dung lai class cu, khong thi phai viet them JS cap nhat');
    }
    /* Hai dong bao hiem cung phai co o khoi moi, va cung do JS bat tat. */
    ok(substr_count($v, 'js-dong-bh') >= 4,
       "quotations/$man.php: hai dong bao hiem co o ca hai khoi tong");
}

if (isset($khoi)){
    ok(preg_match('~\.bang-dong-hang\s*>\s*tfoot\s*\{[^}]*display:\s*none~s', $khoi) === 1,
       'Man hep: an <tfoot> di');
    ok(preg_match('~\.tong-man-hep\s*\{[^}]*display:\s*block~s', $khoi) === 1,
       'Man hep: hien khoi tong xep doc');
}
/* THU TU TRONG FILE: hai luat cung do cu the (0,1,0), luat dung SAU thang.
   De `.tong-man-hep { display:none }` mac dinh o sau @media thi no de chet
   `display:block` ben trong — khoi tong bien mat tren CA HAI co man hinh. */
$viTriMacDinh = strpos($css, '.tong-man-hep { display: none; }');
$viTriMedia   = strrpos($css, '@media (max-width: 767.98px)');
ok($viTriMacDinh !== false && $viTriMedia !== false && $viTriMacDinh < $viTriMedia,
   'Luat mac dinh (display:none) dung TRUOC @media',
   'Cung do cu the thi luat sau thang — de sau la khoi tong bien mat han');

// ---------------------------------------------------------------------------
section('O chon hang hoa khong bi khung cuon xen mat (bao 26/10/2026)');

/* CHO HONG: .table-responsive co overflow-x:auto. Theo chuan CSS, overflow-y
   "visible" dung canh mot overflow-x khac "visible" bi tinh thanh "auto" — tuc
   khung do CAT CA CHIEU DOC. Danh sach position:absolute ben trong bi xen mat
   phan thoi ra: do tren dien thoai duoc danh sach cao 233px / khung cuon cao
   108px, chi con nhin thay 10px. Bam vao o chon ra mot vet trang, tuong hong.

   Chua bang position:fixed — fixed tinh theo khung nhin, khong khung cha nao
   cat duoc. Doi lai phai TU DAT CHO (datCho() trong admin.js). */
$js = file_get_contents($goc . 'public/assets/js/admin.js');

ok(preg_match('~\.ss__list\s*\{[^}]*position:\s*fixed~s', $css) === 1,
   'Danh sach dung position: fixed',
   'absolute thi bi .table-responsive xen mat');
ok(!preg_match('~\.ss__list\s*\{[^}]*position:\s*absolute~s', $css),
   'KHONG con position: absolute');

/* CSS khong duoc ghi cung cho: fixed ma de top:100% / left:0 / right:0 thi
   danh sach nhay ve goc khung nhin, khong dinh gi toi o nhap. */
if (preg_match('~\.ss__list\s*\{(.*?)\n\}~s', $css, $mL)){
    foreach (['top: 100%', 'left: 0', 'right: 0', 'margin-top'] as $cam){
        ok(strpos($mL[1], $cam) === false,
           "CSS .ss__list KHONG ghi cung `$cam`",
           'fixed thi cho do datCho() dat; ghi cung o day la da nhau');
    }
}

ok(strpos($js, 'function datCho(') !== false, 'admin.js co datCho() de tu dat cho');

/* BA CHO PHAI GOI datCho(), thieu mot la danh sach dung mot noi o nhap mot neo:
     - luc mo
     - luc go (so dong doi -> chieu cao doi)
     - luc cuon / doi co man hinh */
ok(substr_count($js, 'datCho()') >= 4,
   'datCho() duoc goi du o moi cho (mo / go / cuon / doi co): '
   . substr_count($js, 'datCho()') . ' lan');

/* CAI BAY TINH VI NHAT: scroll cua PHAN TU khong noi bot len window. Dang ky
   khong co capture thi cuon NGANG trong chinh bang dong hang se khong bao gio
   toi tay handler — ma do dung la kieu cuon man hep sinh ra nhieu nhat. */
ok(preg_match('~addEventListener\(\s*.scroll.[\s\S]{0,200}?\}\s*,\s*true\s*\)~', $js) === 1,
   'Bo lang nghe cuon dang ky o che do CAPTURE (true)',
   'Khong capture thi cuon ngang trong .table-responsive khong toi duoc handler');
ok(preg_match('~addEventListener\(\s*.resize.~', $js) === 1,
   'Co dat lai cho khi doi co man hinh');

/* LAT LEN TREN khi duoi khong du cho — dong hang cuoi cua phieu dai nam sat
   day man hinh, mo xuong thi danh sach thoi khoi man. */
ok(preg_match('~list\.style\.bottom\s*=\s*\(~', $js) === 1,
   'Co nhanh lat danh sach len tren');

/* MOT bo lang nghe cho CA TRANG, khong phai moi o mot bo: phieu hai chuc dong
   la hai chuc o chon, gan listener cuon cho tung o thi moi lan cuon chay hai
   chuc ham ma muoi chin cai dang dong. */
ok(substr_count($js, "addEventListener('scroll'") === 1,
   'Chi MOT bo lang nghe cuon cho ca trang',
   'Dang co ' . substr_count($js, "addEventListener('scroll'") . ' bo');

// ---------------------------------------------------------------------------
section('Anh phu tung trong o chon bao gia (yeu cau 26/10/2026)');

/* Man Lap hoa don von da co anh trong o chon, man Bao gia thi khong — cung mot
   o chon, cung mot danh muc, ma ben thay anh ben khong.

   Du lieu co san: theoNguon() da gan `image` cho tung dong (lay mot luot bang
   primaryImageMap() nen khong sinh N+1). Chi la hai view bao gia khong chuyen
   no xuong JS. */
foreach (['add', 'edit'] as $man){
    $v = file_get_contents($goc . 'app/views/admin/quotations/' . $man . '.php');
    ok(preg_match("~'img'\s*=>~", $v) === 1,
       "quotations/$man.php: o chon co truyen anh xuong JS");
    /* Rong thi de CHUOI RONG chu dung bo han khoa: admin.js xet
       option[data-img] de quyet dinh co chua cho anh hay khong, va mat hang
       chua co anh van can mot o trong de cac dong thang hang. */
    ok(preg_match("~'img'\s*=>\s*!empty\(\\\$p\['image'\]\)~", $v) === 1,
       "quotations/$man.php: mat hang chua co anh thi de chuoi rong");
}

/* Hai man bao gia va hai man hoa don phai dung CUNG mot duong dan anh. Lech
   nhau thi mot ben hien anh con mot ben ra o vo, va khong ai nhin ra vi sao. */
$duong = [];
foreach (['quotations/add', 'quotations/edit', 'sales-invoices/add', 'sales-invoices/edit'] as $m){
    $v = file_get_contents($goc . 'app/views/admin/' . $m . '.php');
    if (preg_match("~'img'\s*=>[^,]*?'(/public/[^']*)'~", $v, $mD)) $duong[$m] = $mD[1];
}
ok(count($duong) === 4 && count(array_unique($duong)) === 1,
   'Bon man dung cung mot duong dan anh phu tung',
   'Dang co: ' . json_encode($duong, JSON_UNESCAPED_SLASHES));

/* O chua anh da co san trong CSS tu truoc (man hoa don dung) — kiem lai de
   khong ai go nham khi don dep. */
foreach (['.ss__thumb', 'span.ss__thumb'] as $c){
    ok(strpos($css, $c) !== false, "CSS con lop `$c`");
}
ok(strpos($js, "option[data-img]") !== false,
   'admin.js van xet option[data-img] de chua cho anh');

exit(summary());

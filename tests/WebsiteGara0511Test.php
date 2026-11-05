<?php
/**
 * Test TÀI LIỆU WEBSITE 05/11/2026 — bốn đợt việc.
 *
 * Chạy:  C:\xampp\php\php.exe tests\WebsiteGara0511Test.php
 *
 * Khách gửi "Nội dung xây dựng website phụ tùng từ gara" và đã CHỐT BỎ hai
 * mục nặng nhất (kết nối sàn thương mại điện tử, đa ngôn ngữ). Còn lại chia
 * bốn đợt; file này canh những chỗ hỏng được mà không ai nhìn ra ngay:
 *
 *   1. BỘ LỌC HTML là chốt chặn duy nhất giữa ô soạn thảo và trang khách.
 *      Nội dung bài viết in ra bằng {!! !!} (không escape, bắt buộc thế thì
 *      <strong> mới in đậm). Bộ lọc thủng là mọi người xem trang ăn mã.
 *
 *   2. MENU trỏ bằng ĐÍCH chứ không bằng chuỗi URL. Dựng sai một kiểu là cả
 *      một nhánh menu dẫn vào trang trống — mà trang trống vẫn trả HTTP 200,
 *      không log nào báo.
 *
 *   3. LƯU ĐƠN MUA KHÔNG ĐƯỢC LÀM BAY SERIAL. Form lập đơn nay không còn ô
 *      serial, trong khi lưu đơn vẫn xoá sạch dòng hàng rồi ghi lại — mà
 *      serial neo vào dòng hàng theo CASCADE. Thiếu bước chép lại thì sửa
 *      một chữ trên đơn là mất hết tem đã quét. Đây là mục nguy hiểm nhất
 *      của cả đợt vì nó mất dữ liệu thật và không báo gì.
 *
 *   4. BẢNG BỎ DẤU của JS phải khớp bản PHP. Lệch nhau thì người dùng nhìn ô
 *      đường dẫn tự điền ra một chuỗi, bấm Lưu, và CSDL nhận một chuỗi khác.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;
use App\core\Load;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

// ---------------------------------------------------------------------------
section('Migration 000104');

$mg = glob($goc . 'database/migrations/*_000104_*.php');
ok(count($mg) === 1, 'Co dung mot migration 000104', 'Tim thay ' . count($mg));
$sqlMg = count($mg) === 1 ? file_get_contents($mg[0]) : '';

foreach ([['menus', 'link_type'], ['menus', 'target_id'], ['news', 'tags'],
          ['parts', 'meta_title'], ['parts', 'meta_description']] as $c){
    ok(strpos($sqlMg, '`' . $c[1] . '`') !== false, 'Migration them `' . $c[0] . '`.`' . $c[1] . '`');
}
ok(substr_count($sqlMg, 'hasColumn') >= 6, 'Co kiem cot truoc khi them — chay lai duoc');
ok(strpos($sqlMg, "DEFAULT 'url'") !== false,
   "link_type mac dinh 'url'",
   'Menu co tu truoc 05/11 deu la URL go tay — chung phai chay y nhu cu');

// ---------------------------------------------------------------------------
section('DOT 3 — bo loc HTML (chot chan duy nhat truoc trang khach)');

ok(function_exists('loc_html_an_toan'), 'Co ham loc_html_an_toan()');

$don = [
    'the script'        => '<p>a</p><script>alert(1)</script>',
    'onerror'           => '<img src="x" onerror="alert(1)" alt="a">',
    'onclick trong bang'=> '<table><tr><td onclick="x()">o</td></tr></table>',
    'href javascript'   => '<a href="javascript:alert(1)">b</a>',
    'href java\nscript' => "<a href=\"java\nscript:alert(1)\">b</a>",
    'href co khoang'    => '<a href="  javascript:alert(1)">b</a>',
    'iframe'            => '<iframe src="//evil.test"></iframe>',
    'form + input'      => '<form action="//evil.test"><input name="pw"></form>',
    'style'             => '<div style="position:fixed;inset:0">x</div>',
    'svg onload'        => '<svg onload="alert(1)"></svg>',
    'anh data:'         => '<img src="data:text/html,<script>alert(1)</script>">',
    'chu thich giau ma' => '<p>a</p><!-- <script>alert(1)</script> -->',
];
$xau = ['<script', 'onerror', 'onload', 'onclick', 'javascript:', '<iframe', '<form', 'style=', 'data:'];
foreach ($don as $ten => $vao){
    $ra = loc_html_an_toan($vao);
    $lot = false;
    foreach ($xau as $x){ if (stripos($ra, $x) !== false){ $lot = true; break; } }
    ok(!$lot, 'Chan duoc: ' . $ten, 'Ra: ' . $ra);
}

/* GIỮ ĐƯỢC THỨ TỬ TẾ. Bộ lọc chặn sạch mà xoá luôn định dạng thì trình soạn
   thảo thành vô dụng — người viết sẽ quay về dán HTML thô vào đâu đó. */
$dinhDang = loc_html_an_toan('<p><strong>Đậm</strong> và <em>nghiêng</em></p><ul><li>một</li></ul>');
ok(strpos($dinhDang, '<strong>') !== false && strpos($dinhDang, '<li>') !== false,
   'Giu dinh dang that (dam, nghieng, danh sach)', $dinhDang);
ok(strpos(loc_html_an_toan('<p>Phụ tùng ô tô — giá tốt</p>'), 'Phụ tùng ô tô') !== false,
   'Giu nguyen tieng Viet co dau');
ok(strpos(loc_html_an_toan('<a href="/tin-tuc/abc">x</a>'), 'href="/tin-tuc/abc"') !== false,
   'Giu link noi bo that');
ok(strpos(loc_html_an_toan('<a href="https://a.test" target="_blank">x</a>'), 'noopener') !== false,
   'Link mo tab moi duoc gan rel=noopener',
   'Thieu no thi trang duoc mo tu doi dia chi tab goc sang trang gia');
ok(loc_html_an_toan('') === '' && loc_html_an_toan(null) === '', 'Rong vao thi rong ra');
ok(strpos(loc_html_an_toan('<marquee>chạy</marquee>'), 'chạy') !== false,
   'The la thi BO THE nhung GIU CHU',
   'Xoa sach la nguoi viet mat nguyen doan van ma khong hieu vi sao');

$ctlNews = codeOnly($goc . 'app/controllers/admin/News.php');
ok(strpos($ctlNews, 'loc_html_an_toan($f[\'content\'])') !== false,
   'News::buildData() loc noi dung TRUOC KHI LUU',
   'Loc luc hien ra thi co nhieu cho (trang tin, trang chu, sitemap) — quen mot cho la thung');

// ---------------------------------------------------------------------------
section('DOT 2 — Menu chon kieu lien ket');

$M = Load::model('MenusModel');

ok(MenusModel::kieuHopLe('linh tinh') === MenusModel::KIEU_URL, "Kieu la -> ve 'url'");
ok(MenusModel::kieuHopLe('news_cat') === 'news_cat', 'Kieu that duoc giu');
ok(count(MenusModel::$kieuLienKet) === 4, 'Co du 4 kieu theo tai lieu (url, tin, san pham, thu vien)');

$urlGoTay = $M->urlCua(['link_type' => 'url', 'url' => 'gioi-thieu']);
ok($urlGoTay === 'gioi-thieu', "Kieu 'url' doc thang cot url", $urlGoTay);

/* ĐÍCH ĐÃ BỊ XOÁ -> chuỗi rỗng, KHÔNG phải URL hỏng. Rỗng đi qua nav_url()
   thành trang chủ; URL hỏng thì ném người xem vào trang 404. */
$matDich = $M->urlCua(['link_type' => 'news_cat', 'target_id' => 999999999]);
ok($matDich === '', 'Dich da bi xoa -> tra ve rong, khong phai URL hong', var_export($matDich, true));
ok($M->urlCua(['link_type' => 'news_cat', 'target_id' => 0]) === '', 'Chua chon dich -> rong');

$ctlMenu = codeOnly($goc . 'app/controllers/admin/Menus.php');
ok(strpos($ctlMenu, '$this->__model->dich($kieu, $id)') !== false,
   'Controller KIEM dich co that truoc khi luu',
   'Khong kiem thi id go bua van luu duoc, menu im lang tro ve trang chu');
ok(strpos($ctlMenu, 'MenusModel::KIEU_URL') !== false,
   'Dich khong hop le thi ha ve kieu url');

$mdlMenu = codeOnly($goc . 'app/models/MenusModel.php');
ok(strpos($mdlMenu, "'tin-tuc?cat='") !== false,   'Dung URL danh muc bai viet');
ok(strpos($mdlMenu, "'san-pham?category%5B%5D='") !== false,
   'Dung URL danh muc san pham (category[] — dung tham so man San pham doc)');
ok(strpos($mdlMenu, "'thu-vien/'") !== false,      'Dung URL album thu vien');
ok(strpos($mdlMenu, "\$r['url'] = \$this->urlCua(\$r);") !== false,
   'getActiveTree() dung san URL cho giao dien khach',
   'Khong dung thi muc kieu "danh muc" co url rong -> ve trang chu');

foreach (['add', 'edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/menus/' . $v . '.php');
    ok(strpos($f, 'id="o-kieu-lien-ket"') !== false, $v . '.php: co o chon kieu lien ket');
    ok(substr_count($f, 'name="target_id"') === 3, $v . '.php: co du 3 o chon dich');
    ok(substr_count($f, 'disabled') >= 3, $v . '.php: o chon dich mac dinh BI TAT',
       'Ba o trung ten target_id — o an ma van bat thi may chu nhan o cuoi cung, sai dich');
    ok(strpos($f, 'o.disabled = !hop') !== false, $v . '.php: JS bat/tat theo kieu dang chon');
}

$lstMenu = file_get_contents($goc . 'app/views/admin/menus/lists.php');
ok(strpos($lstMenu, "nhan_dich") !== false,
   'Danh sach menu hien DICH, khong phai cot url tho',
   'Muc kieu "danh muc" co url rong — truoc day hien ra dau "/" nhu tro ve trang chu');

// ---------------------------------------------------------------------------
section('DOT 4 — serial khai theo don mua, bang popup');

$web = file_get_contents($goc . 'routes/web.php');
ok(strpos($web, "purchase-orders/serial/(\\d+)',  'admin/purchaseorders/serial") !== false,
   'Co route GET man khai serial');
ok(strpos($web, "purchase-orders/serial/(\\d+)', 'admin/purchaseorders/postSerial") !== false,
   'Co route POST luu serial');

$vSn = file_get_contents($goc . 'app/views/admin/purchase-orders/serial.php');
ok(strpos($vSn, 'data-toggle="modal"') !== false, 'serial.php: khai bang POPUP (khach yeu cau)');
ok(strpos($vSn, 'name="serial[{{$poi}}][]"') !== false,
   'serial.php: o serial khoa theo poi_id',
   'Neo bang id dong hang chu khong phai thu tu — man nay khong ghi lai dong hang');
ok(substr_count($vSn, '<form') === 1,
   'serial.php: CHI MOT form bao ca bang lan popup',
   'Moi popup mot form thi bam Luu o popup nay la mat phan vua go o popup kia');
ok(strpos($vSn, "e.key !== 'Enter'") !== false,
   'serial.php: Enter nhay o duoi thay vi gui form',
   'May quet ma vach gui Enter sau moi tem — khong chan thi quet cai dau tien la trang nhay di mat');

foreach (['add', 'edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/purchase-orders/' . $v . '.php');
    ok(strpos($f, "serial[") === false, $v . '.php: KHONG con o serial nao trong form lap don');
    ok(strpos($f, 'veSerial') === false, $v . '.php: da bo ham ve o serial');
    ok(strpos($f, 'nut-xoa-dong') !== false, $v . '.php: nut xoa dong dung lop icon to hon');
}

$ctlPo = codeOnly($goc . 'app/controllers/admin/Purchaseorders.php');
ok(strpos($ctlPo, '$serialCu = $this->__serial->theoHang($id);') !== false,
   'Luu don: CHEP LAI serial truoc khi ghi lai dong hang',
   'Thieu buoc nay la sua mot chu tren don mat sach tem da quet (CASCADE)');
ok(strpos($ctlPo, 'function postSerial') !== false && strpos($ctlPo, '$hopLe[$poiId]') !== false,
   'postSerial() chi nhan dong THUOC CHINH don nay',
   'Khoa mang la poi_id tu trinh duyet — khong doi chieu thi gan duoc serial sang don gara khac');

// ---------------------------------------------------------------------------
section('DOT 1 — SEO, slug, nut ho tro, thao tac nhanh');

$js = file_get_contents($goc . 'public/assets/js/seo-slug.js');
ok(strpos($js, 'w.oSlugTuTieuDe') !== false, 'seo-slug.js: co oSlugTuTieuDe()');
ok(strpos($js, 'w.oDoDaiSeo') !== false,     'seo-slug.js: co oDoDaiSeo()');
ok(strpos($js, "boc.classList.add('la-trong')") !== false,
   'O trong KHONG bi to do', 'De trong van dung — trang tu lay tieu de lam the meta');

/* BẢNG BỎ DẤU CỦA JS PHẢI KHỚP BẢN PHP. Lệch một ký tự là ô đường dẫn hiện
   một chuỗi còn CSDL nhận chuỗi khác — không có thông báo nào. */
$lech = [];
foreach (['a' => 'áàảãạăắằẳẵặâấầẩẫậ', 'e' => 'éèẻẽẹêếềểễệ', 'i' => 'íìỉĩị',
          'o' => 'óòỏõọôốồổỗộơớờởỡợ', 'u' => 'úùủũụưứừửữự',
          'y' => 'ýỳỷỹỵ', 'd' => 'đ'] as $k => $v){
    if (strpos($js, $k . ": '" . $v . "'") === false) $lech[] = $k;
}
ok(empty($lech), 'Bang bo dau cua JS khop het voi slugify() ben PHP',
   'Lech o nhom: ' . implode(', ', $lech));
ok(slugify('Phụ tùng ô tô — Đèn pha') === 'phu-tung-o-to-den-pha',
   'slugify() PHP van chay dung', slugify('Phụ tùng ô tô — Đèn pha'));

$soan = file_get_contents($goc . 'public/assets/js/soan-thao.js');
ok(strpos($soan, 'w.oSoanThao') !== false, 'soan-thao.js: co oSoanThao()');
ok(strpos($soan, "b.type = 'button'") !== false,
   'Nut tren thanh cong cu la type=button',
   'Mac dinh cua <button> trong form la submit — bam In dam thanh luu bai');
ok(strpos($soan, "addEventListener('paste'") !== false,
   'Co loc noi dung DAN VAO', 'Dan tu web khac la duong pho bien nhat de ma lot vao');
ok(strpos($soan, 'cdn') === false && strpos($soan, 'http://') === false,
   'Khong nap gi tu ben ngoai',
   'Thu vien tu CDN trong man quan tri = nguoi khac quyet dinh ma nao chay o do');

$ctlPr = codeOnly($goc . 'app/controllers/admin/Products.php');
ok(strpos($ctlPr, "'meta_title'") !== false && strpos($ctlPr, "'meta_description'") !== false,
   'Products::buildData() ghi meta SEO');
foreach (['add', 'edit'] as $v){
    $f = file_get_contents($goc . 'app/views/admin/products/' . $v . '.php');
    ok(strpos($f, 'id="o-meta-title"') !== false, 'products/' . $v . '.php: co o meta title');
    ok(strpos($f, 'oDoDaiSeo') !== false,         'products/' . $v . '.php: co thanh mau SEO');
}
$vPrAdd  = file_get_contents($goc . 'app/views/admin/products/add.php');
$vPrEdit = file_get_contents($goc . 'app/views/admin/products/edit.php');
ok(strpos($vPrAdd, 'oSlugTuTieuDe') !== false,  'products/add.php: tu dien duong dan theo ten hang');
/* Tim LOI GOI chu khong tim CAI TEN: ghi chu trong file co nhac toi ten ham
   de giai thich vi sao khong goi, nen tim ten khong thi bat nham ghi chu. */
ok(strpos($vPrEdit, 'window.oSlugTuTieuDe(') === false,
   'products/edit.php: KHONG tu doi duong dan',
   'Hang da dang web roi, doi duong dan theo ten la link cu chet');

$shop = codeOnly($goc . 'app/controllers/Shop.php');
ok(strpos($shop, "!empty(\$part['meta_description'])") !== false,
   'Trang san pham uu tien meta da khai');
ok(strpos($shop, 'tại Tân Phát') === false,
   'Cau meta tu dung KHONG con ghi cung "Tan Phat"',
   'Viet cung la moi gara tren nen tang deu chao hang duoi ten Tan Phat tren Google');

$hdr = file_get_contents($goc . 'app/views/layouts/admin/header.php');
ok(strpos($hdr, 'adm-topbar__nhanh') !== false, 'Thanh tren co cum thao tac nhanh');
ok(substr_count($hdr, "['admin/") === 3, 'Du ba duong tat: ban hang, mua hang, cham soc');
ok(strpos($hdr, '$__u = route($__m[0]);') !== false,
   'Duong tat loc THEO QUYEN',
   'Khong loc thi nhan vien kho van thay "Ban hang", bam vao an trang tu choi');

$setCtl = codeOnly($goc . 'app/controllers/admin/Settings.php');
ok(strpos($setCtl, "'messenger'") !== false, 'Cau hinh co khoa `messenger`');
$mst = file_get_contents($goc . 'app/views/layouts/storefront/master.php');
ok(strpos($mst, 'ho-tro-noi') !== false, 'Trang khach co ba nut ho tro noi');
ok(strpos($mst, "\$settings['zalo']") !== false && strpos($mst, '$hotlineTel') !== false,
   'Nut ho tro lay tu cau hinh CUA GARA',
   'Viet cung la moi gara do cuoc goi ve Tan Phat');

$css = file_get_contents($goc . 'public/assets/css/admin-theme.css');
ok(strpos($css, '.seo-do-dai') !== false,        'CSS: co thanh mau SEO');
ok(strpos($css, '.nut-xoa-dong') !== false,      'CSS: nut xoa dong to hon');
ok(strpos($css, '.adm-topbar__nhanh') !== false, 'CSS: cum thao tac nhanh');
$cssSf = file_get_contents($goc . 'public/assets/storefront/css/theme.css');
ok(strpos($cssSf, 'prefers-reduced-motion') !== false,
   'Hieu ung nhay dung lai khi nguoi dung tat chuyen dong',
   'Thu nhap nhay lien tuc o mep mat gay chong mat voi mot so nguoi');

// ---------------------------------------------------------------------------
section('TEN MIEN RIENG — suy giao thuc http / https');

/* VI SAO QUAN TRONG. Nen tang nhieu gara: moi gara co the dung TEN MIEN RIENG,
 * ma ten mien rieng thi gan nhu chac chan chay HTTPS. Ghep cung 'http://' thi
 * moi duong dan sinh ra deu la http:// trong mot trang https — trinh duyet
 * chan vi noi dung lan, hoac keo nguoi dung tut xuong http.
 *
 * Va KHONG va duoc bang cach khai cung APP_URL: may chu nhieu gara phai de
 * APP_URL TRONG, neu khong moi gara bi keo het ve mot ten mien.
 *
 * Chay trong TIEN TRINH RIENG vi config.php dinh nghia hang so — dat lai
 * $_SERVER roi require lan nua trong cung tien trinh thi khong doi duoc. */
/* Qua FILE TAM chu khong qua `php -r`: trên Windows, escapeshellarg() bọc
   bằng dấu nháy kép, nên đoạn mã có dấu nháy kép bên trong vỡ cú pháp. */
$thuUrl = function(array $server) use ($goc){
    $ma = "<?php\n";
    foreach ($server as $k => $v){
        $ma .= '$_SERVER[' . var_export($k, true) . '] = ' . var_export($v, true) . ";\n";
    }
    $ma .= "require " . var_export(realpath($goc) . '/vendor/autoload.php', true) . ";\n"
         . "require " . var_export(realpath($goc) . '/config.php', true) . ";\n"
         . "echo _WEB_URL;\n";

    $tep = sys_get_temp_dir() . '/tp-thu-scheme-' . getmypid() . '.php';
    file_put_contents($tep, $ma);
    $ra = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tep) . ' 2>&1'));
    @unlink($tep);
    return $ra;
};

$u1 = $thuUrl(['HTTP_HOST' => 'gara-abc.test']);
ok(strpos($u1, 'http://') === 0, 'Chay http thuong -> sinh link http', $u1);

$u2 = $thuUrl(['HTTP_HOST' => 'gara-abc.test', 'HTTPS' => 'on']);
ok(strpos($u2, 'https://gara-abc.test') === 0,
   'Apache bat TLS ($_SERVER[HTTPS]) -> sinh link https', $u2);

$u3 = $thuUrl(['HTTP_HOST' => 'gara-abc.test', 'SERVER_PORT' => '443']);
ok(strpos($u3, 'https://') === 0, 'Cong 443 -> sinh link https', $u3);

/* HEADER PROXY KHONG DUOC TIN MAC DINH. Day la chot an toan, khong phai
   tien nghi: may chu dung thang ra Internet ma tin header nay thi BAT KY AI
   cung gui duoc "X-Forwarded-Proto: https" de ep ung dung sinh link https. */
$u4 = $thuUrl(['HTTP_HOST' => 'gara-abc.test', 'HTTP_X_FORWARDED_PROTO' => 'https']);
ok(strpos($u4, 'http://') === 0,
   'X-Forwarded-Proto BI BO QUA khi chua bat TRUST_PROXY', $u4);

$cfg = codeOnly($goc . 'config.php');
ok(strpos($cfg, "Env::get('TRUST_PROXY', false) === true") !== false,
   'Chi tin header proxy khi .env bat TRUST_PROXY');
ok(strpos($cfg, "'http://' . \$__host") === false,
   "Khong con ghep cung 'http://' trong config.php");
$envVd = file_get_contents($goc . '.env.example');
ok(strpos($envVd, 'TRUST_PROXY') !== false, '.env.example co huong dan TRUST_PROXY');
ok(strpos($envVd, 'DE TRONG') !== false || strpos($envVd, 'de trong') !== false,
   '.env.example nhac APP_URL phai de trong tren may chu nhieu gara');

// ---------------------------------------------------------------------------
section('Directive view — quet CA DU AN');

/* VI SAO QUET CA DU AN, khong chi may file cua dot nay.
 *
 * Bo bien dich view (core/Template.php) chi hieu @if / @else / @endif / @for /
 * @foreach / @while / @php. KHONG co @elseif. Va moi directive phai nam CUOI
 * DONG — bieu thuc cua no la `~@if\s*\((.+?)\)\s*$~im`.
 *
 * Viet sai mot trong hai dieu do thi:
 *   - @elseif bi bo lai thanh chu thuong, @endif thua ra mot cai
 *   - `@if (...)<th>@endif` tren mot dong: @if KHONG bien dich, @endif thi co
 *
 * Ca hai deu sinh ra "syntax error, unexpected token endif" va LAM CHET CA
 * TRANG. `php -l` khong bat duoc vi loi nam trong ma da bien dich, khong nam
 * trong file view. Dung ngay 05/11/2026 da mac ca hai loi nay o man Sua don
 * mua, va chi phat hien khi mo trang that ra xem.
 *
 * Quet tren codeOnly() nen ghi chu giai thich (nhu doan nay) khong bi tinh. */
$viLoi = [];
$duyet = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($goc . 'app/views'));
$soView = 0;
foreach ($duyet as $f){
    if ($f->isDir() || strtolower($f->getExtension()) !== 'php') continue;
    $soView++;
    $ten = str_replace('\\', '/', $f->getPathname());
    $ten = substr($ten, strpos($ten, 'app/views/') + 10);

    foreach (explode("\n", codeOnly($f->getPathname())) as $i => $d){
        $d = rtrim($d, "\r");

        if (stripos($d, '@elseif') !== false){
            $viLoi[] = "$ten:" . ($i + 1) . " dung @elseif (template khong ho tro)";
        }
        foreach (['if', 'foreach', 'for', 'while'] as $k){
            if (preg_match('~@' . $k . '\s*\((.+)\)(.*)$~i', $d, $m) && trim($m[2]) !== ''){
                $viLoi[] = "$ten:" . ($i + 1) . " @$k khong nam cuoi dong";
            }
        }
    }
}
ok($soView > 100, "Quet $soView file view");
ok(empty($viLoi), 'Moi directive view deu bien dich duoc',
   implode(' | ', array_slice($viLoi, 0, 8)));

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
$cm = $cot('menus');
ok(isset($cm['link_type']) && isset($cm['target_id']), 'CSDL co menus.link_type + target_id');
ok(isset($cm['link_type']) && $cm['link_type']['Default'] === 'url',
   "menus.link_type mac dinh 'url'");
ok(isset($cm['target_id']) && $cm['target_id']['Null'] === 'YES',
   'menus.target_id cho NULL', "Kieu 'url' khong tro vao dich nao");
ok(isset($cot('news')['tags']), 'CSDL co news.tags');
$cp = $cot('parts');
ok(isset($cp['meta_title']) && isset($cp['meta_description']), 'CSDL co parts.meta_*');

/* MOI MUC MENU DANG BAT PHAI RA MOT DUONG DAN DUNG. Dich bi xoa thi urlCua()
   tra ve rong (ve trang chu) — chap nhan duoc; nhung mot muc kieu "danh muc"
   ma ra chuoi khong co dang URL thi la dung sai. */
$hong = [];
foreach ($pdo->query("SELECT * FROM `menus` WHERE `status` = 1") as $r){
    $u = $M->urlCua($r);
    if ($u === '') continue;
    if (preg_match('~[\x00-\x1f\s]~', $u)) $hong[] = $r['label'] . ' -> ' . $u;
}
ok(empty($hong), 'Moi muc menu dang bat dung ra duong dan sach',
   implode('; ', $hong));

// ---------------------------------------------------------------------------
section('LUU DON MUA KHONG LAM BAY SERIAL (nguy hiem nhat)');

/* Dung thao tac that: tao don -> khai serial -> LUU LAI DON (ghi lai dong
   hang) -> dem lai serial. Day la duong di cua nguoi dung that sau 05/11:
   khai serial o man rieng, roi quay ra sua mot chu tren don. */
$gara = (int) $pdo->query("SELECT id FROM garages WHERE status = 1 ORDER BY id LIMIT 1")->fetchColumn();
$part = (int) $pdo->query("SELECT id FROM parts ORDER BY id LIMIT 1")->fetchColumn();
$ncc  = (int) $pdo->query("SELECT id FROM partners WHERE type IN ('supplier','both') ORDER BY id LIMIT 1")->fetchColumn();

if ($gara <= 0 || $part <= 0){
    echo "  [SKIP] Thieu gara hoac hang hoa de thu.\n";
    exit(summary());
}

$DAU = 'ZZW0511-';
$poId = 0;
$ketQua = Model::trongGara($gara, function() use ($part, $ncc, $DAU, &$poId){
    $PO = Load::model('PurchaseOrdersModel');
    $IT = Load::model('PurchaseOrderItemsModel');
    $SN = Load::model('PurchaseSerialsModel');

    $poId = (int) $PO->add([
        'po_no'        => $DAU . 'PO',
        'partner_id'   => $ncc > 0 ? $ncc : null,
        'partner_name' => $DAU . 'NCC',
        'order_date'   => date('Y-m-d'),
        'status'       => 0,
    ]);

    // 1. dong hang + serial, dung duong cua man Khai serial
    $dong = $IT->syncForPo($poId, [['part_id' => $part, 'quantity' => 3, 'unit_price' => 1000]]);
    $poi  = (int) array_key_first($dong);
    $SN->ghiChoDong($poId, $poi, $part, [$DAU . 'A1', $DAU . 'A2', $DAU . 'A3']);
    $truoc = $SN->demTheoPo($poId);

    // 2. "sua don": ghi lai dong hang y nhu controller lam, co chep serial
    $serialCu = $SN->theoHang($poId);
    $dong2 = $IT->syncForPo($poId, [['part_id' => $part, 'quantity' => 3, 'unit_price' => 1200]]);
    $poi2  = (int) array_key_first($dong2);
    $sn = [];
    foreach ($serialCu[$part] as $s) $sn[] = ['poi_id' => $poi2, 'part_id' => $part, 'serial' => $s];
    $SN->syncForPo($poId, $sn);
    $sau = $SN->demTheoPo($poId);

    // 3. neo lai dung dong hang MOI, khong phai dong da bi xoa
    $theoDong = $SN->theoDong($poId);
    $dungNeo  = isset($theoDong[$poi2]) && count($theoDong[$poi2]) === 3;

    return ['truoc' => $truoc, 'sau' => $sau, 'dungNeo' => $dungNeo, 'poi2' => $poi2];
});

ok($ketQua['truoc'] === 3, 'Khai duoc 3 serial qua ghiChoDong()', 'Dem: ' . $ketQua['truoc']);
ok($ketQua['sau'] === 3,
   'Luu lai don xong serial VAN CON du 3',
   'Con ' . $ketQua['sau'] . ' — day la mat du lieu that, khong bao gi');
ok($ketQua['dungNeo'], 'Serial neo lai dung dong hang MOI',
   'Neo vao dong da bi xoa thi man Khai serial mo ra thay trong tron');

/* Khai lai cho CHINH dong do: phai THAY THE, khong duoc cong don. */
$dem2 = Model::trongGara($gara, function() use ($poId, $part, $DAU, $ketQua){
    $SN = Load::model('PurchaseSerialsModel');
    $SN->ghiChoDong($poId, $ketQua['poi2'], $part, [$DAU . 'B1', $DAU . 'B2']);
    return $SN->demTheoPo($poId);
});
ok($dem2 === 2, 'Khai lai mot dong la THAY THE, khong cong don', 'Dem: ' . $dem2);

// --- don sach ---
$pdo->prepare("DELETE FROM purchase_serials WHERE po_id = ?")->execute([$poId]);
$pdo->prepare("DELETE FROM purchase_order_items WHERE po_id = ?")->execute([$poId]);
$pdo->prepare("DELETE FROM purchase_orders WHERE id = ?")->execute([$poId]);
$con = (int) $pdo->query("SELECT COUNT(*) FROM purchase_serials WHERE serial LIKE '" . $DAU . "%'")->fetchColumn();
ok($con === 0, 'Da don sach du lieu thu', "Con $con dong");

exit(summary());

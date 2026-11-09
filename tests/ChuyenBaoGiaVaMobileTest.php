<?php
/**
 * Test HAI VIỆC NGÀY 09/11/2026.
 *
 * Chạy:  C:\xampp\php\php.exe tests\ChuyenBaoGiaVaMobileTest.php
 *
 * VIỆC A — CHUYỂN BÁO GIÁ THÀNH HOÁ ĐƠN
 * Nút đã có từ trước nhưng thiếu chốt: bấm hai lần ra HAI hoá đơn cùng nội
 * dung. Ghi sổ cả hai là trừ tồn hai lần và doanh thu đếm đôi — không ai nhìn
 * ra cho tới lúc kiểm kho, lúc đó đã muộn vài tuần. Đây là mục nguy hiểm nhất
 * của cả đợt vì nó hỏng SỐ LIỆU, không phải hỏng giao diện.
 *
 * Kèm theo là một lỗi nuốt thông báo: controller ném `msgError` rồi chuyển
 * trang, nhưng trang đích không nhận biến đó nên lỗi biến mất không dấu vết.
 * Nguy ở chỗ nó làm MỌI chốt an toàn trở nên vô hình — kể cả "Tồn không đủ để
 * xuất bán" của màn hoá đơn. Người dùng bấm, không thấy gì, bấm lại.
 *
 * VIỆC B — MÀN HÌNH ĐIỆN THOẠI
 * Khách: "chỗ nhập liệu phải to để nhìn rõ, các icon text cũng vậy".
 * Chốt lại khối @media trong admin-theme.css. Hai con số dưới đây KHÔNG phải
 * chuyện thẩm mỹ:
 *   - ô nhập phải ≥ 16px, nếu không Safari iPhone tự phóng to cả trang mỗi
 *     lần con trỏ nhảy vào ô, và không tự thu lại;
 *   - khung soạn thảo (contenteditable) cũng vậy, mà nó KHÔNG ăn quy tắc
 *     .form-control nên rất dễ bị bỏ sót.
 */

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\core\Model;
use App\core\Load;

echo 'PHP ' . PHP_VERSION . "\n";
$goc = __DIR__ . '/../';

/** Bỏ comment trong CSS — comment của khối mobile có nhắc "16px", grep thẳng
 *  vào nguồn sẽ trúng comment và báo PASS oan dù quy tắc thật đã bị xoá. */
$cssKhongComment = function($path){
    return preg_replace('~/\*.*?\*/~s', '', file_get_contents($path));
};

// ===========================================================================
section('VIEC A — model biet bao gia nao da thanh hoa don');

$mHd = $goc . 'app/models/SalesInvoicesModel.php';
$codeHd = codeOnly($mHd);

ok(strpos($codeHd, 'function theoBaoGia') !== false,
   'SalesInvoicesModel::theoBaoGia() — tra ve hoa don sinh ra tu mot bao gia');
ok(strpos($codeHd, 'function theoNhieuBaoGia') !== false,
   'SalesInvoicesModel::theoNhieuBaoGia() — mot truy van cho ca trang danh sach');
ok(preg_match('~theoBaoGia.*?bangGara\(\)~s', $codeHd) === 1,
   'theoBaoGia() loc theo gara',
   'Khong loc thi gara A thay duoc hoa don cua gara B — thung da nguoi');
ok(preg_match("~theoBaoGia.*?orderBy\('id', 'ASC'\)~s", $codeHd) === 1,
   'theoBaoGia() lay ban CU NHAT',
   'Du lieu cu co the da lo co hai cai; cai dau moi la cai dang dung');

// ===========================================================================
section('VIEC A — hai chot an toan trong convert()');

$cQuote = codeOnly($goc . 'app/controllers/admin/Quotations.php');

/* Vi tri tuong doi moi la thu dang kiem: chot phai dung TRUOC loi goi tao hoa
   don, dat sau thi hoa don thu hai da sinh ra roi moi bao loi. */
$viConvert = strpos($cQuote, 'function convert');
$viDaCo    = strpos($cQuote, 'theoBaoGia', $viConvert);
$viRejected = strpos($cQuote, "'rejected'", $viConvert);
$viTao     = strpos($cQuote, '__invoice->add(', $viConvert);

ok($viDaCo !== false && $viTao !== false && $viDaCo < $viTao,
   'Chot "da chuyen roi" nam TRUOC loi goi tao hoa don',
   'Dat sau thi hoa don thu hai da duoc tao roi moi bao loi');
ok($viRejected !== false && $viTao !== false && $viRejected < $viTao,
   'Chot "khach da tu choi" nam TRUOC loi goi tao hoa don');

// ===========================================================================
section('VIEC A — loi NUOT THONG BAO (chot an toan vo hinh)');

/* View da co san khoi hien $msgError tu truoc; thieu la o controller. */
foreach ([['Quotations.php', 'quotations', 'bao gia'],
          ['Salesinvoices.php', 'sales-invoices', 'hoa don']] as $v){
    $src  = codeOnly($goc . 'app/controllers/admin/' . $v[0]);
    $view = file_get_contents($goc . 'app/views/admin/' . $v[1] . '/edit.php');

    $viEdit = strpos($src, 'function edit(');
    $viPost = strpos($src, 'function postEdit(');
    $doan   = $viEdit !== false
        ? substr($src, $viEdit, ($viPost !== false && $viPost > $viEdit ? $viPost - $viEdit : 2000))
        : '';

    ok(strpos($view, 'msgError') !== false,
       'Man sua ' . $v[2] . ' CO khoi hien loi');
    ok(strpos($doan, "'msgError'") !== false,
       'Controller ' . $v[0] . '::edit() TRUYEN msgError sang view',
       'Thieu dong nay thi moi loi bi nuot im lang — nguoi dung bam, khong thay gi');
}

// ===========================================================================
section('VIEC A — man hinh');

$vList = file_get_contents($goc . 'app/views/admin/quotations/lists.php');
$vEdit = file_get_contents($goc . 'app/views/admin/quotations/edit.php');
$vHd   = file_get_contents($goc . 'app/views/admin/sales-invoices/edit.php');

ok(strpos($vList, 'hoaDonTheoBaoGia') !== false,
   'Danh sach bao gia dung ban do hoa don (khong truy van tung dong)');
ok(strpos($vList, '/convert/') !== false,
   'Danh sach bao gia co nut Chuyen — khong phai mo tung phieu ra moi thay');
ok(substr_count($vList, 'colspan="7"') === 1 && strpos($vList, 'colspan="6"') === false,
   'Dong "chua co bao gia nao" da doi colspan sang 7',
   'Them cot ma quen colspan thi dong trong bi lech, nhin nhu bang vo');
ok(strpos($vEdit, '$hoaDon') !== false,
   'Man sua bao gia biet phieu da thanh hoa don chua');
ok(strpos($vHd, '$baoGia') !== false && strpos($vHd, 'admin/quotations/edit/') !== false,
   'Man hoa don co duong dan QUAY VE bao giá goc');

/* Bo bien dich view khong co @elseif (core/Template.php). Ba nhanh phai viet
   bang PHP thuan; dung @elseif thi trang vo luc chay chu khong phai luc lint,
   va `php -l` khong bat duoc vi loi nam o ban da bien dich.
   PHAI dung codeOnly(): ba file nay deu co comment GIAI THICH vi sao khong
   dung @elseif, grep thang vao nguon se trung comment va bao FAIL oan. */
foreach (['quotations/lists.php', 'quotations/edit.php',
          'sales-invoices/edit.php'] as $v){
    ok(strpos(codeOnly($goc . 'app/views/admin/' . $v), '@elseif') === false,
       $v . ' khong dung @elseif (bo bien dich khong ho tro)');
}

// ===========================================================================
section('VIEC B — khoi mobile trong admin-theme.css');

$css = $cssKhongComment($goc . 'public/assets/css/admin-theme.css');

ok(substr_count($css, '@media (max-width: 767.98px)') >= 2,
   'Co khoi @media cho man hinh dien thoai');

/* Lay rieng khoi mobile CUOI CUNG (khoi vua them) de kiem noi dung. */
$viKhoi = strrpos($css, '@media (max-width: 767.98px)');
$khoi   = $viKhoi !== false ? substr($css, $viKhoi) : '';

ok(preg_match('~\.form-control[^{]*\{[^}]*font-size:\s*16px~s', $khoi) === 1,
   'O nhap dat 16px tren dien thoai',
   'DUOI 16px thi Safari iPhone tu phong to ca trang moi lan bam vao o. '
   . 'Day la nguong tat hanh vi do, khong phai con so cho dep.');
ok(preg_match('~\.form-control-sm[^{]*\{[^}]*font-size:\s*16px~s', $khoi) === 1,
   'O loc (.form-control-sm) cung 16px',
   'O trong bang dong hang deu la form-control-sm — bo sot la van bi phong to');
ok(preg_match('~\.soan-thao__khung[^{]*\{[^}]*font-size:\s*16px~s', $khoi) === 1,
   'Khung soan thao 16px',
   'contenteditable KHONG an quy tac .form-control nen rat de bi bo sot, '
   . 'ma iPhone van phong to khi go vao no');

/* Bay specificity that su da dinh mot lan: `.table th` (0,0,1,1) thua
   `.table thead th` (0,0,1,2) cua muc 9, va thua IM LANG — do ra van 13px. */
ok(strpos($khoi, '.table thead th') !== false,
   'Chu trong bang dung bo chon `.table thead th`',
   'Viet `.table th` thi thua quy tac goc (0,0,1,2) va thua im lang');

ok(preg_match('~\.btn[^{]*\{[^}]*min-height:\s*4[0-9]px~s', $khoi) === 1,
   'Nut co vung bam >= 40px');
ok(preg_match('~input\[name\$="note\[\]"\]~', $khoi) === 1
   && preg_match('~input\[name\$="disc\[\]"\]~', $khoi) === 1,
   'O ghi chu / chiet khau nham theo DUOI TEN o',
   'Dung nth-child thi sai mot bang la sai am tham — thu tu cot khac nhau '
   . 'giua bao gia, hoa don, don mua, phieu nhap');
ok(strpos($khoi, 'min-width: 900px') !== false
   || strpos($css, '.bang-dong-hang { min-width: 900px; }') !== false,
   'Bang dong hang noi rong de chua chu 16px');

// ===========================================================================
section('Chay that tren MySQL');

try {
    $pdo = new PDO('mysql:host=' . _HOST . ';port=' . _PORT . ';dbname=' . _DB . ';charset=utf8mb4',
                   _USER, _PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (\PDOException $e){
    echo "\n[SKIP] Khong ket noi duoc MySQL.\n"; exit(summary());
}

$gara = (int) $pdo->query('SELECT id FROM garages WHERE status = 1 ORDER BY id LIMIT 1')->fetchColumn();
$kho  = $gara > 0
    ? (int) $pdo->query('SELECT id FROM warehouses WHERE garage_id = ' . $gara . ' ORDER BY id LIMIT 1')->fetchColumn()
    : 0;

if ($gara <= 0 || $kho <= 0){
    echo "  [SKIP] Thieu gara hoac kho de thu.\n";
    exit(summary());
}

$DAU  = 'ZZW0911-';
$bgId = 0; $hdId = 0;

$kq = Model::trongGara($gara, function() use ($DAU, $kho, &$bgId, &$hdId){
    $BG = Load::model('QuotationsModel');
    $HD = Load::model('SalesInvoicesModel');

    $bgId = (int) $BG->add([
        'quote_no'      => $DAU . 'BG',
        'customer_name' => $DAU . 'KH',
        'quote_date'    => date('Y-m-d'),
        'vat_rate'      => 10,
        'subtotal'      => 0, 'tax_amount' => 0, 'total_amount' => 0,
        'status'        => 'accepted',
    ]);

    // Chua chuyen -> khong tim thay gi
    $truoc = $HD->theoBaoGia($bgId);

    $hdId = (int) $HD->add([
        'invoice_no'   => $DAU . 'HD',
        'customer_name'=> $DAU . 'KH',
        'warehouse_id' => $kho,
        'quotation_id' => $bgId,
        'invoice_date' => date('Y-m-d'),
        'vat_rate'     => 10,
        'subtotal'     => 0, 'tax_amount' => 0, 'total_amount' => 0, 'cost_amount' => 0,
        'status'       => 0,
    ]);

    $sau    = $HD->theoBaoGia($bgId);
    $banDo  = $HD->theoNhieuBaoGia([$bgId, 0, -5]);
    $khongCo = $HD->theoBaoGia(0);

    return ['truoc' => $truoc, 'sau' => $sau, 'banDo' => $banDo, 'khongCo' => $khongCo];
});

ok(empty($kq['truoc']),
   'Bao gia chua chuyen -> theoBaoGia() tra ve rong',
   'Tra ve "co" nham thi nut Chuyen bien mat ngay tu dau, khong ai chuyen duoc');
ok(!empty($kq['sau']) && (int) $kq['sau']['id'] === $hdId,
   'Bao gia da chuyen -> tim dung hoa don do');
ok(isset($kq['banDo'][$bgId]) && (int) $kq['banDo'][$bgId]['id'] === $hdId,
   'theoNhieuBaoGia() tra ve ban do dung khoa');
ok(count($kq['banDo']) === 1,
   'Id rac (0, so am) bi bo qua, khong lam vo truy van',
   'Nhan duoc ' . count($kq['banDo']) . ' dong');
ok($kq['khongCo'] === null,
   'theoBaoGia(0) tra ve null, khong phai mot dong bat ky');

/* Khoa duy nhat cua ca dot: mot bao gia chi duoc co MOT hoa don. */
$dem = (int) $pdo->query('SELECT COUNT(*) FROM sales_invoices WHERE quotation_id = ' . $bgId)->fetchColumn();
ok($dem === 1, 'Mot bao gia -> dung MOT hoa don', 'Dem duoc ' . $dem);

// --- Don sach du lieu thu ---------------------------------------------------
$pdo->exec('DELETE FROM sales_invoices WHERE invoice_no LIKE ' . $pdo->quote($DAU . '%'));
$pdo->exec('DELETE FROM quotations     WHERE quote_no   LIKE ' . $pdo->quote($DAU . '%'));
$con = (int) $pdo->query('SELECT (SELECT COUNT(*) FROM sales_invoices WHERE invoice_no LIKE '
       . $pdo->quote($DAU . '%') . ') + (SELECT COUNT(*) FROM quotations WHERE quote_no LIKE '
       . $pdo->quote($DAU . '%') . ')')->fetchColumn();
ok($con === 0, 'Da don sach du lieu thu', 'Con lai ' . $con . ' dong');

exit(summary());
